<?php

namespace Combi\Web\Http;


use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use Combi\Web as inner;

class Environment extends core\Meta\Collection
    implements core\Interfaces\Confirmable
{
    private $_protocol_version = '1.1';

    public function set($key, $value): self {
        return parent::set(strtolower($key), $value);
    }

    public function confirm(): self {
        if ($this->has('server_protocol')) {
            $value = $this->get('server_protocol');
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