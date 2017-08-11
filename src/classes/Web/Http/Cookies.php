<?php

namespace Combi\Web\Http;

use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use Combi\Web as inner;

/**
 *
 * @todo 暂不支持从header中解析cookie
 */
class Cookies extends core\Meta\Collection
{
    use core\Meta\Extensions\Overloaded;

    /**
     * @todo 暂不支持hostonly
     */
    const OPTION_EXPIRE     = 0;
    const OPTION_PATH       = 1;
    const OPTION_DOMAIN     = 2;
    const OPTION_SECURE     = 3;
    const OPTION_HTTPONLY   = 4;

    /**
     *
     * @var array
     */
    protected $_send_keys = [];

    /**
     * @var array
     */
    protected $_options = [
        0,
        '',
        '',
        false,
        false,
    ];

    protected $_is_filled = false;

    public function __construct(?array $data = null) {
        $data && $this->fill($data);

        // 置为初始化结束
        $this->_is_filled = true;
    }

    /**
     *
     */
    public function setOptions(int $expire = 0, string $path = '',
        string $domain = '', bool $secure = false, bool $httponly = false)
    {
        $this->_options = [
            $expire,
            $path,
            $domain,
            $secure,
            $httponly,
        ];
    }

    public function setOption(int $name, $value): self {
        $this->_options[$name] = $value;
        return $this;
    }

    public function set($key, $value, ...$options): self
    {
        $this->_is_filled && ($this->_send_keys[$key] = $options
            ? array_replace($this->_options, $options) : $this->_options);
        return parent::set($key, $value);
    }

    public function remove($key): self {
        unset($this->_send_keys[$key]);
        return parent::remove($key);
    }

    public function clear(): self {
        $this->_send_keys = [];
        return parent::clear();
    }

    public function send(): void {
        foreach ($this->_send_keys as $key => $options) {
            setcookie($key, $this->$key, ...$options);
        }
    }

}