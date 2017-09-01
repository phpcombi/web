<?php

namespace Combi\Web;

use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use Combi\Web as inner;

use Combi\Core\Utils\Pack;

class Response extends Http\Response
{
    const EOL = "\r\n";

    /**
     *
     * @param string $data
     * @return static
     */
    public function __invoke(string $data): self
    {
        return $this->write($data);
    }

    /**
     *
     * @param string $data
     * @return static
     */
    public function write(string $data): self {
        $this->getBody()->write($data);
        return $this;
    }

    /**
     *
     *
     * @param  string|UriInterface $url    The redirect destination.
     * @param  int|null            $status The redirect HTTP status code.
     * @return static
     */
    public function withRedirect(string $url, ?int $status = null): self
    {
        $with_redirect = $this->withHeader('Location', $url);

        if ($status === null && $this->getStatusCode() === static::STATUS_OK) {
            $status = static::STATUS_FOUND;
        }

        if ($status !== null) {
            return $with_redirect->withStatus($status);
        }

        return $with_redirect;
    }

    /**
     *
     * @param  mixed  $data   The data
     * @param  int    $status The HTTP status code.
     * @throws \RuntimeException
     * @return static
     */
    public function withJson($data, $status = null): self
    {
        $json = Pack::encode('json', $data);
        if ($json === false) {
            throw new \RuntimeException(\json_last_error_msg(),
                \json_last_error());
        }

        $body     = new Http\Stream(fopen('php://temp', 'r+'));
        $response = $this->withBody($body)
            ->write($json)
            ->withHeader('Content-Type', 'application/json;charset=utf-8');
        if ($status !== null) {
            return $response->withStatus($status);
        }
        return $response;
    }

    public function withView(View $view, string $template, array $data): self {
        $body     = new Http\Stream(fopen('php://temp', 'r+'));
        return $this->withBody($body)
            ->write($view->render($template, $data))
            ->withHeader('Content-Type', $view->getContentType());
    }

    /**
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return in_array($this->getStatusCode(), [
            static::STATUS_NO_CONTENT,
            static::STATUS_RESET_CONTENT,
            static::STATUS_NOT_MODIFIED,
        ]);
    }

    /**
     *
     * @return bool
     */
    public function isInformational(): bool
    {
        return $this->getStatusCode() >= static::STATUS_CONTINUE
            && $this->getStatusCode() < static::STATUS_OK;
    }

    /**
     *
     * @return bool
     */
    public function isOk(): book
    {
        return $this->getStatusCode() === static::STATUS_OK;
    }

    /**
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->getStatusCode() >= static::STATUS_OK
            && $this->getStatusCode() < static::STATUS_MULTIPLE_CHOICES;
    }

    /**
     *
     * @return bool
     */
    public function isRedirect(): bool
    {
        return in_array($this->getStatusCode(), [
            static::STATUS_MOVED_PERMANENTLY,
            static::STATUS_FOUND,
            static::STATUS_SEE_OTHER,
            static::STATUS_TEMPORARY_REDIRECT,
        ]);
    }

    /**
     *
     * @return bool
     */
    public function isRedirection()
    {
        return $this->getStatusCode() >= static::STATUS_MULTIPLE_CHOICES
            && $this->getStatusCode() < static::STATUS_BAD_REQUEST;
    }

    /**
     *
     * @return bool
     * @api
     */
    public function isForbidden()
    {
        return $this->getStatusCode() === static::STATUS_FORBIDDEN;
    }

    /**
     *
     * @return bool
     */
    public function isNotFound()
    {
        return $this->getStatusCode() === static::STATUS_NOT_FOUND;
    }

}