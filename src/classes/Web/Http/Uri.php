<?php

namespace Combi\Web\Http;


use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use Combi\Web as inner;

use Psr\Http\Message\{
    UriInterface
};

/**
 *
 *
 * @property string $scheme
 * @property string $host
 * @property string $port
 * @property string $path
 * @property string $query
 * @property string $fragment
 * @property string $user
 * @property string $password
 *
 */
class Uri extends core\Meta\Struct implements UriInterface
{
    use core\Meta\Extensions\Overloaded;

    protected static $_defaults = [
        'scheme'    => '',
        'host'      => '',
        'port'      => '',
        'path'      => '/',
        'query'     => '',
        'fragment'  => '',
        'user'      => '',
        'password'  => '',
    ];

    /**
     *
     */
    public function __construct($data = null)
    {
        if ($data) {
            if (is_array($data)) {
                $this->fill($data);
            } else {
                $this->fill(parse_url($data));
            }
        }
    }

    protected function _confirm_scheme(string $value): string {
        return strtolower($value);
    }

    protected function _confirm_port(int $value): int {
        if ($value && ($value < 1 || $value > 65535)) {
            throw new InvalidArgumentException(
                "Uri port must be an integer between 1 and 65535, now is $value");
        }
        return $value;
    }

    protected function _confirm_path(string $value): string {
        if (!$value) {
            return '/';
        }
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            }, $value);
    }

    protected function _confirm_query(string $value): string {
        return $this->pretreatQuery(ltrim($value, '?'));
    }

    protected function _confirm_fragment(string $value): string {
        return $this->pretreatQuery(ltrim($value, '#'));
    }

    private function pretreatQuery(string $value): string {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            }, $value);
    }


    /**
     * Retrieve the scheme component of the URI.
     *
     * @return string The URI scheme.
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * Return an instance with the specified scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * @return self A new instance with the specified scheme.
     */
    public function withScheme($scheme): self
    {
        $clone = clone $this;
        $clone->scheme = $scheme;

        return $clone;
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority(): string
    {
        $userInfo = $this->getUserInfo();
        $host = $this->getHost();
        $port = $this->getPort();

        return ($userInfo ? "$userInfo@" : '').
            $host.(!$port ? ":$port" : '');
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo(): string
    {
        return $this->user.($this->password ? ":$this->password" : '');
    }

    /**
     * Return an instance with the specified user information.
     *
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return self A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null): self
    {
        $clone = clone $this;
        $clone->user = $user;
        $clone->password = $password ? $password : '';

        return $clone;
    }

    /**
     * Retrieve the host component of the URI.
     *
     * @return string The URI host.
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Return an instance with the specified host.
     *
     * @param string $host The hostname to use with the new instance.
     * @return self A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host): self
    {
        $clone = clone $this;
        $clone->host = $host;

        return $clone;
    }

    /**
     * Retrieve the port component of the URI.
     *
     * @return null|int The URI port.
     */
    public function getPort(): ?int
    {
        return $this->port ?: null;
    }

    /**
     * Return an instance with the specified port.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     * @return self A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port): self
    {
        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    /**
     * Retrieve the path component of the URI.
     *
     * @return string The URI path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Return an instance with the specified path.
     *
     * @param string $path The path to use with the new instance.
     * @return self A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path): self
    {
        $clone = clone $this;
        $clone->path = $path;

        return $clone;
    }

    /**
     * Retrieve the query string of the URI.
     *
     * @return string The URI query string.
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Return an instance with the specified query string.
     *
     * @param string $query The query string to use with the new instance.
     * @return self A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query): self
    {
        $clone = clone $this;
        $clone->query = $query;

        return $clone;
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * @return string The URI fragment.
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
    *
     * @param string $fragment The fragment to use with the new instance.
     * @return self A new instance with the specified fragment.
     */
    public function withFragment($fragment): self
    {
        $clone = clone $this;
        $clone->fragment = $fragment;

        return $clone;
    }

    /**
     * Return the string representation as a URI reference.
     *
     * @return string
     */
    public function __toString(): string
    {
        $scheme     = $this->getScheme();
        $authority  = $this->getAuthority();
        $path       = $this->getPath();
        $query      = $this->getQuery();
        $fragment   = $this->getFragment();


        return ($scheme ? $scheme . ':' : '')
            . ($authority ? '//' . $authority : '')
            . $path
            . ($query ? '?' . $query : '')
            . ($fragment ? '#' . $fragment : '');
    }

}