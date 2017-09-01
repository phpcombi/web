<?php

namespace Combi\Web;

use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use Combi\Web as inner;

class Result extends core\Meta\Struct
{
    use core\Meta\Extensions\Overloaded;

    /**
     *
     * @var Response
     */
    protected $response;

    /**
     *
     * @var string
     */
    protected $template = '';

    /**
     *
     * @var View|null
     */
    protected $view = null;

    /**
     *
     * @var int
     */
    protected $code = 0;

    public function __construct(Response $response) {
        $this->response = $response;
    }

    public function setHttpStatus(int $code): self {
        $this->code = $code;
        return $this;
    }

    public function view(string $template, ?View $view = null): self {
        $this->template = $template;
        $this->view     = $view;
        return $this;
    }

    public function response(): Response {
        return $this->response;
    }

    public function regress(Action $action): Response {
        if (!$this->template) {
            // TODO: 暂时默认json输出
            return $this->response->withJson($this->toArray(), $this->code ?: null);
        }
        $view = $this->view ?: $action->getView();
        $response = $this->response->withView($view,$this->template, $this->toArray());
        return $this->code ? $response->withStatus($this->code) : $response;
    }

    public function __call(string $name, array $arguments) {
        if (strpos($name, 'response') === 0) {
            $name = lcfirst(substr($name, 8));
            $result = $this->response->$name(...$arguments);
            if ($result instanceof Response) {
                $this->response = $result;
            }
            return $result;
        }
        throw new \UnexpectedValueException("Try to call undefined method $name.");
    }
}