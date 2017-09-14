<?php

namespace Combi\Web\Http;


use Combi\{
    Helper as helper,
    Abort as abort,
    Core,
    Runtime as rt
};

use Psr\Http\Message\{
    UploadFileInterface,
    UriInterface,
    StreamInterface,
    ServerRequestInterface
};

class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     *
     * @var array
     */
    protected $cookie_params;

    /**
     *
     * @var array
     */
    protected $server_params;

    /**
     *
     * @var array|null
     */
    protected $query_params = null;

    /**
     *
     * @var array
     */
    protected $upload_files;

    /**
     *
     * @var array|null
     */
    protected $parsed_body = null;

    /**
     *
     * @var string|null
     */
    protected $media_type = null;

    /**
     *
     * @var Core\Meta\Collection
     */
    protected $attributes;

    public function __construct(string $method = self::METHOD_GET,
        ?UriInterface $uri = null,
        array $cookie_params = [],
        array $server_params = [],
        array $upload_files  = [],
        ?StreamInterface $body = null,
        ?Headers $headers = null,
        ?string $protocol_version = null)
    {
        parent::__construct($method, $uri, $body, $headers, $protocol_version);

        $this->cookie_params = $cookie_params;
        $this->server_params = $server_params;
        $this->upload_files  = $upload_files;

        $this->attributes = new Core\Meta\Collection();
    }

    public function __clone() {
        parent::__clone();
        $this->attributes = clone $this->attributes;
    }

    /**
     * Retrieve server parameters.
     *
     * @return array
     */
    public function getServerParams(): array {
        return $this->server_params;
    }

    /**
     * Retrieve cookies.
     *
     * @return array
     */
    public function getCookieParams(): array {
        return $this->cookie_params;
    }

    /**
     * Return an instance with the specified cookies.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return static
     */
    public function withCookieParams(array $cookie): self
    {
        $clone = clone $this;
        $clone->cookie_params = $cookie;

        return $clone;
    }

    /**
     * Retrieve query string arguments.
     *
     * @return array
     */
    public function getQueryParams(): array {
        if ($this->query_params === null) {
            $query = $this->getUri()->getQuery();
            parse_str($this->getUri()->getQuery(), $this->query_params);
        }

        return $this->query_params;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * @param array $query_params Array of query string arguments, typically from
     *     $_GET.
     * @return static
     */
    public function withQueryParams(array $query_params): self {
        $clone = clone $this;
        $clone->query_params = $query_params;
        return $clone;
    }

    /**
     * Retrieve normalized file upload data.
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles(): array {
        return $this->upload_files;
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * @param array $upload_files An array tree of UploadedFileInterface instances.
     * @return static
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $upload_files): self {
        $clone = clone $this;
        $clone->upload_files = $upload_files;

        return $clone;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * @return null|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     */
    public function getParsedBody() {
        $this->parsed_body === null && $this->parsed_body = $_POST;
        return $this->parsed_body;
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * @param null|array|object $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return static
     * @throws \InvalidArgumentException if an unsupported argument type is
     *     provided.
     */
    public function withParsedBody($data): self {
        $clone = clone $this;
        $clone->parsed_body = $data;
        return $clone;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes(): array {
        return $this->attributes->all();
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null) {
        return $this->attributes->has($name)
            ? $this->attributes->get($name) : $default;
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return static
     */
    public function withAttribute($name, $value): self {
        $clone = clone $this;
        $clone->attributes->set($name, $value);

        return $clone;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return static
     */
    public function withoutAttribute($name): self {
        $clone = clone $this;
        $clone->attributes->remove($name);

        return $clone;
    }
}