<?php

namespace Combi;

use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

class Web
{
    use Core\Traits\StaticAgent;

    public static function instance(): Web\Package {
        return Web\Package::instance();
    }

}
