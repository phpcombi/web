<?php

namespace App\Controllers\User;

use Combi\{
    Helper as helper,
    Abort as abort,
    Runtime as rt
};

use Combi\Web\{
    Response,
    Result
};

class GetListResult extends Result
{
    protected static $_defaults = [
        'cookies'   => [],
    ];
}
