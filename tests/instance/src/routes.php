<?php

namespace App;

use Combi\{
    Helper as helper,
    Abort as abort,
    Runtime as rt
};

$app = Package::instance();

$router = $app->router;

$router->addPsr4(__DIR__.'/classes/Controllers', 'App\\Controllers');
$router->addDir(__DIR__.'/classes/Controllers');
$router->addController(Controllers\User::class);

