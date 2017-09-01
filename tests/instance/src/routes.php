<?php

namespace App;

use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

$app = Package::instance();

$router = $app->router;

$router->addPsr4(__DIR__.'/classes/', 'App\\Controllers');
$router->addDir(__DIR__.'/classes/App/Controllers');
$router->addController(Controllers\User::class);

// use FastRoute\RouteCollector;
// $router->addCustom(function(RouteCollector $r) {
//     $r->addRoute('GET', '/custom', [Controllers\User::class, 'getList']);
// });

