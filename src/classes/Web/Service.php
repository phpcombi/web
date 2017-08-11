<?php

namespace Combi\Web;

use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use Combi\Web as inner;

class Service extends core\Action implements core\Interfaces\LinkPackage
{
    use core\Traits\LinkPackage;

    protected function handle(?Http\Request $request = null,
        ?array $server_vars = null)
    {
        $this->prepare();

        $this->parseServerVars($server_vars ?: $_SERVER);
        $cookies = new Http\Cookies($_COOKIE);
        $cookies->setOption(Http\Cookies::OPTION_EXPIRE, time() + 1000);
        $cookies->cccc = mt_rand(22,55);
        $cookies->send();

        !$request && $request = $this->makeRequest();


        helper::du($_COOKIE, 'cookie');
        helper::du(inner::get('environment'), '环境变量');
        helper::du($request->getHeaders(), 'Headers');
        helper::du($request);

        $this->afterHandling();
    }

    protected function prepare() {}
    protected function afterHandling() {}

    private function makeRequest(): Http\Request {


        $request = new Http\Request();
        return $request;
    }

    private function parseServerVars(array $vars): void {
        if (inner::get('environment') && inner::get('headers')) {
            return;
        }

        $appenders = [];
        if (!inner::get('environment')) {
            inner::set('environment', $environment = new Http\Environment());
            $appenders[] = function($key, $value) use ($environment) {
                if (strpos($key, 'HTTP_') === 0) {
                    return;
                }
                $environment->set($key, $value);
            };
        }
        if (!inner::get('headers')) {
            inner::set('headers', $headers = new Http\Headers());
            $appenders[] = function($key, $value) use ($headers) {
                if (strpos($key, 'HTTP_') === 0) {
                    $headers->set(substr($key, 5), $value);
                }
            };
        }

        foreach ($vars as $key => $value) {
            foreach ($appenders as $appender) {
                $appender($key, $value);
            }
        }

        helper::confirm(inner::get('environment'));
    }
}