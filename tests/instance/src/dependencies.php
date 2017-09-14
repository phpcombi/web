<?php

namespace App;

use Combi\{
    Helper as helper,
    Abort as abort,
    Runtime as rt
};

$app = Package::instance();

$app->view = new \Combi\Web\View(new \Combi\Web\View\Twig(
    $app->path('src', 'views'), $app->path('tmp', 'view.cache')
));

$app->router = rt::web()->createRouter();

$app->protocol = new Protocol();