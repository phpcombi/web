<?php

namespace Combi\Web\Protocol;

use Combi\{
    Helper as helper,
    Abort as abort,
    Runtime as rt
};

class Middleware extends \Combi\Web\Middleware
{
    public function handle(callable $next, Request $request): Response
    {

    }
}