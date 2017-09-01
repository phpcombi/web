<?php

namespace App\Controllers\User;

use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use App as inner;

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
