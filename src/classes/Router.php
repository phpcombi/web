<?php

namespace Combi\Web;

use Combi\{
    Helper as helper,
    Abort as abort,
    Runtime as rt
};

use FastRoute\{
    RouteCollector as FastRouter,
    Dispatcher as FastDispatcher
};

class Router
{
    const ANY = [
        Request::METHOD_HEAD,
        Request::METHOD_GET,
        Request::METHOD_POST,
        Request::METHOD_PUT,
        Request::METHOD_PATCH,
        Request::METHOD_DELETE,
        Request::METHOD_PURGE,
        Request::METHOD_OPTIONS,
        Request::METHOD_TRACE,
        Request::METHOD_CONNECT,
    ];

    /**
     *
     * @var FastRouter
     */
    protected $router      = null;
    protected $psr4data    = [];
    protected $directories = [];
    protected $controllers = [];
    protected $customs     = [];

    protected $current_controller = '';
    protected $current_prefix     = '';
    protected $added_controllers;

    public function controller(string $class): self {
        $this->current_controller = $class;
        return $this;
    }

    public function prefix(string $prefix = ''): self {
        $this->current_prefix = $prefix;
        return $this;
    }

    public function any(string $pattern, string $method): self {
        $this->router->addRoute(self::ANY,
            $this->current_prefix.$pattern,
            $this->makeRouteHandler($method));
        return $this;
    }

    public function __call(string $name, array $arguments): self {
        $this->router->addRoute(explode('_', strtoupper($name)),
            $this->current_prefix.$arguments[0],
            $this->makeRouteHandler($arguments[1]));
        return $this;
    }

    protected function makeRouteHandler(string $method) {
        $class = $this->current_controller;
        return [$class, $method];
    }

    public function addPsr4(string $dir, $namespace): self {
        $this->psr4data[$dir] = $namespace;
        return $this;
    }

    public function addDir(...$dirs): self {
        foreach ($dirs as $dir) {
            $this->directories[] = $dir;
        }
        return $this;
    }

    public function addController(...$classes): self {
        foreach ($classes as $class) {
            $this->controllers[] = $class;
        }
        return $this;
    }

    public function addCustom(...$functions): self {
        foreach ($functions as $func) {
            $this->customs[] = $func;
        }
        return $this;
    }

    public function __invoke(Action $action) {
        $method = $action->request->getMethod();
        $path   = rawurldecode($action->request->getUri()->getPath());

        [
            $status,
            $handler,
            $path_vars,
        ] = $this->makeDispatcher()->dispatch($method, $path) + [null, null, null];

        switch ($status) {
            case FastDispatcher::NOT_FOUND:
                $response = $action->response->withStatus(Response::STATUS_NOT_FOUND);
                break;
            case FastDispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                $response = $action->response->withStatus(Response::STATUS_METHOD_NOT_ALLOWED);
                break;
            case FastDispatcher::FOUND:
                $response = $this->call($action, $handler, $path_vars);
                break;
        }
        return $response;
    }

    protected function call($action, $handler, $path_vars) {
        [$class, $method] = $handler;
        $controller = new $class($action);

        $stack = $controller->callMiddlewareStack();
        return $stack($action->request->setRouteInfo($method, $path_vars));
    }

    protected function makeDispatcher() {
        $dispatcher = \FastRoute\cachedDispatcher(function(FastRouter $r) {
            $this->added_controllers = [];
            $this->router = $r;

            $this->applyByCustom($this->customs);
            $this->applyByPsr4($this->psr4data);
            $this->applyByDir($this->directories);
            $this->applyByControllers($this->controllers);

            $this->current_controller = '';
        }, [
            'cacheFile'     => rt::core()->path('tmp', 'route.cache'),
            'cacheDisabled' => !rt::isProd(),
        ]);

        // reset
        $this->customs      =
        $this->psr4data     =
        $this->directories  =
        $this->controllers  = [];

        return $dispatcher;
    }

    protected function applyByCustom(array $data) {
        foreach ($data as $func) {
            $func($this->router);
        }
    }

    protected function applyByPsr4(array $data) {
        foreach ($data as $dir => $namespace) {
            foreach ((new \RecursiveDirectoryIterator($dir)) as $fileinfo) {
                if ($fileinfo->isDir()) {
                    continue;
                }
                $class = $fileinfo->getBasename('.php');
                if (!$class) {
                    continue;
                }
                $this->applyControllerRoutes($namespace.'\\'.$class);
            }
        }
    }

    protected function applyByDir(array $data) {
        foreach ($data as $dir) {
            foreach ((new \RecursiveDirectoryIterator($dir)) as $fileinfo) {
                if ($fileinfo->isDir()) {
                    continue;
                }
                $filename = $fileinfo->getRealPath();
                if (!$filename) {
                    continue;
                }
                if ($class = $this->parseControllerClass($filename)) {
                    $this->applyControllerRoutes($class);
                }
            }
        }
    }

    protected function applyByControllers() {
        foreach ($this->controllers as $class) {
            $this->applyControllerRoutes($class);
        }
    }

    protected function applyControllerRoutes(string $class): bool {
        if (isset($this->added_controllers[$class])
            || !method_exists($class, 'routes'))
        {
            return false;
        }

        $this->current_controller   = $class;
        $this->added_controllers[$class] = 1;
        $class::routes($this);

        // prefix 复位
        $this->prefix();
        return true;
    }

    protected function parseControllerClass(string $filename): ?string {
        $result = null;
        $handle = fopen($filename, 'r');
        if (!$handle) {
            return $result;
        }
        $namespace  = '';
        $class      = '';
        $count      = 0;
        while (($line = fgets($handle, 512)) !== false) {
            if (!$namespace
                && preg_match('/\s*namespace ([\S]+)\s*;\s*/', $line, $matches))
            {
                $namespace = $matches[1];
                continue;
            }

            if (!$class
                && preg_match('/\s*class ([\S]+)\s+.*/', $line, $matches))
            {
                $class = $matches[1];
                continue;
            }

            if ($class
                && preg_match('/\s*public(\s|static)+function\s+([^\s\(]+)\(/', $line, $matches))
            {
                $method = $matches[2];
                if ($method == 'routes') {
                    $result = "$namespace\\$class";
                }
                $count++;
            }
            if ($count > 5) {
                break;
            }
        }
        fclose($handle);
        return $result;
    }
}