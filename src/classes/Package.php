<?php

namespace Combi\Web;

use Combi\Facades\Runtime as rt;
use Combi\Facades\Tris as tris;
use Combi\Facades\Helper as helper;
use Combi\Package as core;
use Combi\Web\Package as inner;
use Combi\Core\Abort as abort;

class Package extends \Combi\Facades\Package
{
    protected static $pid = 'web';
}
