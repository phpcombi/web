<?php

namespace App;

use Combi\{
    Helper as helper,
    Abort as abort,
    Runtime as rt
};

class Package extends \Combi\Package
{
    protected static $_pid = 'app';

    public function runByCgi(callable $previous_action = null) {
        if ($previous_action) {
            $response = $previous_action();
            if (!$response->isNotFound()) {
                return $response;
            }
        }

        $this->action = $action = rt::web()->createAction();
        return $action($this->router);
    }
}
