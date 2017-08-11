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

    public function run(?core\Action $service = null) {
        !$service && $service = $this->getDefaultService();
        $this->service = $service;
        $service();
    }

    private function getDefaultService(): Service {
        return new Service();
    }
}
