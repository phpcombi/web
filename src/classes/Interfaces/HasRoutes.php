<?php

namespace Combi\Web\Interfaces;

use Combi\{
    Helper as helper,
    Abort as abort,
    Runtime as rt
};

use Combi\Web\Router;

interface HasRoutes
{
    public static function routes(Router $r);
}