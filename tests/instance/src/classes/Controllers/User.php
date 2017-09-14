<?php

namespace App\Controllers;

use Combi\{
    Helper as helper,
    Abort as abort,
    Runtime as rt
};

use Combi\Web\{
    Request,
    Response,
    Router,
    Controller
};
use App\{
    Middlewares,
    ControllerGroups as Groups
};

class User extends Groups\Standard
{
    public static function routes(Router $r) {
        $r->prefix('/user')
            ->get('/list/{page}', 'getList')
            ->any('/signin', 'signIn')
            ->post_put('/signup', 'signUp')
            ->get('/info', 'getInfo');
    }

    protected function middlewares() {
        $this->addMiddlewares(new Middlewares\Throttle());
        parent::middlewares();
    }

    public function getList(User\GetListParams $params, User\GetListResult $result,
        array $path_vars)
    {
        helper::debug("call controller");
        $result->cookies = $this->cookies->all() + ['c' => $params->category];
        // $result->cookies = $bbcc;
        return $result->view('user/list.html');
    }

    public function signIn() {

    }

    public function getInfo() {

    }
}
