<?php

namespace Combi\Web;

use Combi\{
    Helper as helper,
    Abort as abort,
    Runtime as rt
};

rt::register(Package::instance(__DIR__),
    'helpers', 'hooks');
