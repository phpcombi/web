<?php

namespace Combi\Web\View;

use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use Combi\Web as inner;

class Twig implements inner\Interfaces\TemplateEngineBridge
{
    protected $engine;

    public function __construct(string $template_dir, string $cache_dir,
        array $options = [])
    {
        $loader = new \Twig_Loader_Filesystem($template_dir);

        $options['cache'] = $cache_dir;
        $this->engine = new \Twig_Environment($loader, $options);
    }

    public function render(string $template, array $data): string {
        return $this->engine->render($template, $data);
    }

    public function getEngine() {
        return $this->engine;
    }

}