<?php

namespace Combi;

use Combi\{
    Helper as helper,
    Abort as abort
};

class Web
{
    use Core\Traits\StaticAgent;

    public static function instance(): Core\Package {
        return Core\Package::instance('web');
    }

}
