<?php

namespace Combi\Web\Http;


use Combi\{
    Helper as helper,
    Abort as abort,
    Core,
    Runtime as rt
};

class Headers extends Core\Meta\Collection
{
    use Core\Meta\Extensions\Overloaded;

    public function set($key, $value): self {
        return parent::set($this->transformKey($key),
            is_array($value) ? $value : [$value]);
    }

    public function add($key, $value): self {
        $key = $this->transformKey($key);
        if ($this->has($key)) {
            $this->_data[$key][] = $value;
            return $this;
        }
        return parent::set($key,
            is_array($value) ? $value : [$value]);
    }

    /**
     *
     *
     * @param string $key
     * @return string
     *
     * @todo 是仅在从$_SERVER变量接收数据时才做Key转换，还是每次设置都做转换待评估
     */
    protected function transformKey(string $key): string {
        return ucwords(strtolower(strtr($key, '_', '-')), '-');
    }
}