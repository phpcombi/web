<?php

namespace Combi\Web;

use Combi\{
    Helper as helper,
    Abort as abort,
    Runtime as rt
};

abstract class Middleware
{
    abstract public function handle(callable $next, Request $request): Response;

    public function __invoke(callable $next, Request $request): Response
    {
        return $this->handle($next, $request);
    }
}