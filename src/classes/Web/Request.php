<?php

namespace Combi\Web;

use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use Combi\Web as inner;

class Request extends Http\ServerRequest
    implements \ArrayAccess, core\Interfaces\Arrayable
{
    /**
     *
     * @var array
     */
    protected $route_info;

    /**
     *
     * @var callable|null
     */
    protected static $body_parsers = [];

    /**
     *
     * @param callable $parser
     * @return void
     */
    public static function registerBodyParser(callable $parser,
        ...$media_types): void
    {
        foreach ($media_types as $media_type) {
            self::$body_parsers[$media_type] = $parser;
        }
    }

    /**
     * 当 Content-Type 未定义解析器时，使用 $_POST
     *
     * @return null|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     */
    public function getParsedBody() {
        if ($this->parsed_body === null) {
            $media_type = $this->getMediaType();
            if (isset(self::$body_parsers[$media_type])) {
                $this->parsed_body
                    = self::$body_parsers[$media_type]($this->getBody());
            } else {
                return parent::getParsedBody();
            }
        }
        return $this->parsed_body;
    }

    public function getMediaType(): string {
        if ($this->media_type === null) {
            $content_type = $this->getHeader('Content-Type');

            if (isset($content_type[0])
                && ($parts = preg_split('/\s*[;,]\s*/', $content_type[0])))
            {
                $this->media_type = strtolower($parts[0]);
            } else {
                $this->media_type = 'application/x-www-form-urlencoded';
            }
        }
        return $this->media_type;
    }

    public function setRouteInfo(string $method, array $path_vars = []): self {
        $this->route_info = [
            $method,
            $path_vars,
        ];
        return $this;
    }

    public function getRouteInfo(): array {
        return $this->route_info;
    }

    public function isXhr(): bool
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    public function toArray(callable $filter = null): array {
        if ($filter) {
            $result = [];
            foreach ($this->getQueryParams() as $name => $value) {
                [$name, $value] = $filter($name, $value, $result);
                $result[$name] = $value;
            }
            foreach ($this->getParsedBody() as $name => $value) {
                [$name, $value] = $filter($name, $value, $result);
                $result[$name] = $value;
            }
        } else {
            $result = $this->getParsedBody() + $this->getQueryParams();
        }
        return $result;

    }

    public function offsetSet($offset, $value): void {
        $this->query_params[$offset] = $value;
    }

    public function offsetGet($offset) {
        return $this->getParsedBody()[$offset]
            ?? $this->query_params[$offset] ?? null;
    }

    public function offsetExists($offset): bool {
        return isset($this->getParsedBody()[$offset])
            ?: isset($this->query_params[$offset]);
    }

    public function offsetUnset($offset): void {
        unset($this->query_params[$offset]);
    }
}