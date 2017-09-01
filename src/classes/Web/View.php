<?php

namespace Combi\Web;

use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use Combi\Web as inner;

class View
{
    protected $bridge;

    protected $content_type = 'text/html';

    public function __construct(Interfaces\TemplateEngineBridge $bridge)
    {
        $this->bridge = $bridge;
    }

    public function render(string $template, array $data = []): string {
        return $this->bridge->render($template, $data);
    }

    public function setContentType(string $content_type): self {
        $this->content_type = $content_type;
        return $this;
    }

    public function getContentType(): string {
        return $this->content_type;
    }
}