<?php

namespace Combi\Web;

use Combi\{
    Helper as helper,
    Abort as abort,
    Core,
    Runtime as rt
};

use Fig\Http\Message\{
    RequestMethodInterface
};

abstract class Controller implements Interfaces\HasRoutes
{
    use Core\Middleware\Aware;

    /**
     *
     * @var Action
     */
    protected $action;

    public static function routes(Router $r) {}

    public function __construct(Action $action) {
        $this->action = $action;
        $this->middlewares();
    }

    protected function middlewares() {}

    public function __get(string $name) {
        return $this->action->$name;
    }

    protected function setMiddlewareStackKernel($stack,
        ...$arguments): void
    {
        $stack->kernel($this);
    }

    public function __invoke(Request $request): Response {
        [$method, $path_vars] = $request->getRouteInfo();
        $arguments = [];

        // 生成params
        $params_class = $this->getParamsClass($method);
        if ($params_class && class_exists($params_class)) {
            $arguments[] = (new $params_class($request))->confirm();
        } else {
            $arguments[] = $request;
        }

        // 生成result
        $result_class = $this->getResultClass($method);
        if ($result_class && class_exists($result_class)) {
            $arguments[] = new $result_class($this->action->response);
        } else {
            $view = $this->action->getView();
            $arguments[] = $view
                ? $this->action->response->setView($view) : $this->action->response;
        }
        // TODO: protocol对所有返回情况都要做处理

        // 执行并返回
        $arguments[] = $path_vars;
        $result = $this->$method(...$arguments);
        !$result && $result = $arguments[1];

        if ($result instanceof Result) {
            return $result->confirm()->regress($this->action);
        }
        return $result;
    }

    protected function getParamsClass(string $method): string {
        return static::class.'\\'.ucfirst($method).'Params';
    }

    protected function getResultClass(string $method): string {
        return static::class.'\\'.ucfirst($method).'Result';
    }
}