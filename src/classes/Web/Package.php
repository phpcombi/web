<?php

namespace Combi\Web;

use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};

class Package extends \Combi\Package
{
    protected static $_pid = 'web';

    public function createAction(...$arguments): Action {
        return new Action(...$arguments);
    }

    public function createRouter(): Router {
        return new Router();
    }
}
