<?php

namespace Combi\Web\Http;


use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use Combi\Web as inner;

use Psr\Http\Message\{
    StreamInterface,
    MessageInterface
};

abstract class Message implements MessageInterface
{
    const HTTP_PROTOCOL_VERSION_ALLOW = [
        '1.0' => 1,
        '1.1' => 1,
        '2.0' => 1,
    ];

    /**
     * @var StreamInterface
     */
    protected $body;

    /**
     * @var Headers
     */
    protected $headers;

    /**
     * @var string
     */
    protected $protocol_version;

    public function __construct(?StreamInterface $body = null,
        ?Headers $headers = null, ?string $protocol_version = null)
    {
        $this->body     = $body ?: new Stream(fopen('php://temp', 'r+'));
        $this->headers  = $headers ?: inner::get('headers');
        $this->protocol_version  = $protocol_version
            ?: inner::get('environment')->getProtocolVersion();
    }

    public function getProtocolVersion(): string
    {
        return $this->protocol_version;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * @param string $version HTTP protocol version
     * @return static
     * @throws Abort|InvalidArgumentException if the http version is an invalid number
     */
    public function withProtocolVersion($version): self
    {
        if (!isset(self::HTTP_PROTOCOL_VERSION_ALLOW[$version])) {
            throw abort::invalidArgument('Invalid HTTP version "%error%". Must be one of: %allow%')
                ->set('error', $version)
                ->set('allow', implode(',', array_keys(self::$HTTP_PROTOCOL_VERSION_ALLOW)));
        }
        $clone = clone $this;
        $clone->protocol_version = $version;

        return $clone;
    }

    /**
     * Retrieves all message header values.
     *
     * @return array Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders(): array
    {
        return $this->headers->all();
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($name): bool
    {
        return $this->headers->has($name);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method MUST
     *    return an empty array.
     */
    public function getHeader($name): array
    {
        return $this->headers->get($name) ?: [];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name): string
    {
        return implode(',', $this->headers->get($name) ?: []);
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     *
     * @todo uncomply throws func to valid names or values.
     */
    public function withHeader($name, $value): self
    {
        $clone = clone $this;
        $clone->headers->set($name, $value);

        return $clone;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value): self
    {
        $clone = clone $this;
        $clone->headers->add($name, $value);

        return $clone;
    }

    /**
     * Return an instance without the specified header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader($name): self
    {
        $clone = clone $this;
        $clone->headers->remove($name);

        return $clone;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * Return an instance with the specified message body.
     *
     * @param StreamInterface $body Body.
     * @return static
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body): self
    {
        // TODO: Test for invalid body?
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }
}