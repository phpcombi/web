<?php

namespace Combi\Web\Http;


use Combi\{
    Helper as helper,
    Abort as abort,
    Core,
    Runtime as rt
};

class Environment extends Core\Meta\Collection
    implements Core\Interfaces\Confirmable
{
    use Core\Meta\Extensions\Overloaded;

    private $_protocol_version = '1.1';

    public function confirm(): self {
        if ($this->has('SERVER_PROTOCOL')) {
            $value = $this->get('SERVER_PROTOCOL');
            $this->setProtocolVersion(substr($value, strpos($value, '/') + 1));
        }
        return $this;
    }

    public function setProtocolVersion(string $version): self {
        $this->_protocol_version = $version;
        return $this;
    }

    public function getProtocolVersion(): string {
        return $this->_protocol_version;
    }
}