<?php

namespace App\Controllers\User;

use Combi\{
    Helper as helper,
    Abort as abort,
    Runtime as rt
};

use Combi\Web\{
    Response,
    Params
};

class GetListParams extends Params
{
    protected static $_defaults = [
        'category'   => 0,
    ];
}
