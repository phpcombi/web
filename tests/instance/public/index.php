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

core::rt()->web->run();

/**

流程是：

提供服务provider
provider注入action
调用action
provider构建环境变量
路由（注入系统）
调用controller，返回输出

其中controller层与action允许中间件

*/