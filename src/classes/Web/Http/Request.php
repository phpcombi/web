<?php

namespace Combi\Web\Http;


use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use Combi\Web as inner;

use Psr\Http\Message\{
    UriInterface,
    StreamInterface,
    RequestInterface
};
use Fig\Http\Message\RequestMethodInterface;


/**
 *
 *  -   支持扩展头定义：
 *      -   X-Http-Method-Override
 *
 */
class Request extends Message
    implements RequestInterface, RequestMethodInterface
{
    protected $method;

    protected $uri;

    protected $target = null;

    public function __construct(string $method = self::METHOD_GET,
        UriInterface $uri,
        StreamInterface $body,
        ?Headers $headers = null,
        ?string $protocol_version = null)
    {
        parent::__construct($body, $headers, $protocol_version);

        // set method
        $this->method   = $this->pretreatMethod(
            $this->getHeaderLine('X-Http-Method-Override') ?: $method);
        $this->uri      = $uri ?: (new Uri('http://localhost'))->confirm();
    }

    protected function pretreatMethod(string $method): string {
        return strtoupper($method);
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * @param string $method Case-sensitive method.
     * @return self
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method): self
    {
        $method = $this->pretreatMethod($method);
        $clone = clone $this;
        $clone->method = $method;

        return $clone;
    }

    /**
     * Retrieves the message's request target.
     *
     * It's like REQUEST_URI.
     *
     * @return string
     */
    public function getRequestTarget(): string
    {
        if (!$this->target) {
            if ($this->uri) {
                $this->target = $this->uri->getPath().'?'.$this->uri->getQuery();
            } else {
                $this->target = '/';
            }
        }
        return $this->target;
    }

    /**
     * Return an instance with the specific request-target.
     *
     * @param mixed $target
     * @return self
     * @throws \InvalidArgumentException if the request target is invalid
     */
    public function withRequestTarget($target): self
    {
        if (preg_match('#\s#', $target)) {
            throw new \InvalidArgumentException(
                "Request target must be a string and cannot contain whitespace");
        }
        $clone = clone $this;
        $clone->target = (string)$target;

        return $clone;
    }

    /**
     * Retrieves the URI instance.
     *
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return self
     */
    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        $clone = clone $this;
        $clone->uri = $uri;

        if ($preserveHost) {
            if ($uri->getHost() && !$this->getHeader('Host')) {
                $clone->headers->set('Host', $uri->getHost());
            }
        } else {
            $uri->getHost() && $clone->headers->set('Host', $uri->getHost());
        }

        return $clone;
    }

    public function __clone() {
        $this->headers  = clone $this->headers;
        $this->body     = clone $this->body;
    }
}