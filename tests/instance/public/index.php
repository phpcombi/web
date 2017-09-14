<?php

use Combi\{
    Helper as helper,
    Abort as abort,
    Runtime as rt
};

$loader = include __DIR__.'/../../../vendor/autoload.php';

// 这是模拟包载入composer autoload
include __DIR__.'/../../init_instance.php';

rt::up('app', require __DIR__.'/../env.run.php');

rt::app()->runByCgi();

