<?php

namespace Combi\Web\Interfaces;

use Combi\{
    Helper as helper,
    Abort as abort,
    Runtime as rt
};

interface TemplateEngineBridge
{
    public function render(string $template, array $data): string;
    public function getEngine();
}