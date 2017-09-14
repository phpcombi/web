<?php

namespace Combi\Web;

use Combi\{
    Helper as helper,
    Abort as abort,
    Core,
    Runtime as rt
};

class Params extends Core\Meta\Struct
{
    use Core\Meta\Extensions\Overloaded;

    protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
        $this->fill($request->toArray());
    }

    public function request(): Request {
        return $this->request;
    }

}