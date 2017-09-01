<?php

namespace Combi\Web;

use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use Combi\Web as inner;

class Params extends core\Meta\Struct
{
    use core\Meta\Extensions\Overloaded;

    protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function request(): Request {
        return $this->request;
    }

}