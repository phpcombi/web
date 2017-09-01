<?php

use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};
use App as inner;

$loader = include __DIR__.'/../../../vendor/autoload.php';

// 这是模拟包载入composer autoload
include __DIR__.'/../../init_instance.php';

core::up('app', require __DIR__.'/../env.run.php');

inner::runByCgi();

