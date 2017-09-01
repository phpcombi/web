<?php

namespace Combi\Web;

use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use Combi\Web as inner;

class Action extends core\Action
{
    protected function handle(callable $func = null,
        ?Request $request = null,
        ?Response $response = null,
        ?Http\Cookies $cookies = null,
        ?Http\Headers $headers = null,
        ?Http\Environment $environment = null)
    {
        // 环境获取
        if (!$environment || !$headers) {
            $upload_files = $this->parseServerVars($_SERVER, $environment, $headers);
            !$environment && $environment = $upload_files[0];
            !$headers     && $headers     = $upload_files[1];
        }
        $this->environment  = $environment;
        $this->headers      = $headers;

        !$cookies && $cookies = $this->makeCookies();
        $this->cookies = $cookies;

        !$request && $request = $this->makeRequest($cookies, $environment, $headers);
        $this->request  = $request;
        !$response && $response = $this->makeResponse($environment);
        $this->response = $response;

        // 这段为测试代码
        $cookies->setOption(Http\Cookies::OPTION_EXPIRE, time()+3600);
        $cookies->aabbcc = mt_rand(10000, 99999);

        // 处理请求
        $func && $response = $func($this);
        $this->respond($response);

        // 这段也是测试代码
        // helper::du($_COOKIE, 'cookie');
        // helper::du($abc, '环境变量');
        // helper::du($environment, '环境变量');
        // helper::du($request->getQueryParams(), 'query params');
        // helper::du($request->getParsedBody(), 'parsed body');
        // helper::du($request['eee'], 'eee');
        // $response = $response->withJson(['abc'=> 333]);
        // helper::du((string)$response, 'output json');
        // helper::du($controller->request);
    }

    public function respond(Response $response, Http\Cookies $cookies = null): void {
        // stop PHP sending a Content-Type automatically
        ini_set('default_mimetype', '');

        // prepare
        $is_empty = ($response instanceof Resquest && $response->isEmpty())
            || in_array($response->getStatusCode(), [204, 205, 304]);
        $body = $response->getBody();
        $body->isSeekable() && $body->rewind();
        $size = $body->getSize();

        // send header
        if (!headers_sent()) {
            // Status
            header('HTTP/'.$response->getProtocolVersion().
                ' '.$response->getStatusCode().
                ' '.$response->getReasonPhrase()
            );

            // Headers
            // content type & content lenght process
            if ($is_empty) {
                $response = $response
                    ->withoutHeader('Content-Type')
                    ->withoutHeader('Content-Length');
            } else {
                if (ob_get_length() > 0) {
                    ob_flush();
                    throw new \RuntimeException(
                        "Unexpected data in output buffer. Maybe you have characters before an opening <?php tag?");
                }
                if ($size !== null && !$response->hasHeader('Content-Length')) {
                    $response = $response->withHeader('Content-Length', "$size");
                }
            }
            // output header
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header("$name: $value", false);
                }
            }

            // cookies
            $cookies && $cookies->send();
        }

        // send body
        if ($is_empty)
        {
            return;
        }

        $chunk_size = inner::config('settings')->http['response_chunk_size'];
        if ($size !== null) {
            $remain = $size;
            while ($remain > 0 && !$body->eof()) {
                $data = $body->read(min($chunk_size, $remain));
                echo $data;

                $remain -= strlen($data);
                if (connection_status() != CONNECTION_NORMAL) {
                    break;
                }
            }
        } else {
            while (!$body->eof()) {
                echo $body->read($chunk_size);
                if (connection_status() != CONNECTION_NORMAL) {
                    break;
                }
            }
        }
    }

    public function getView(): ?View {
        return $this->p()->view;
    }

    private function makeCookies(): Http\Cookies {
        $cookies = new Http\Cookies($_COOKIE);
        return $cookies;
    }

    private function parseServerVars(array $vars,
        $environment, $headers): array
    {
        $environment = $environment ? null : (new Http\Environment());
        $headers     = $headers     ? null : (new Http\Headers());
        foreach ($vars as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers && $headers->set(substr($key, 5), $value);
            } else {
                $environment && $environment->set($key, $value);
            }
        }

        return [$environment, $headers];
    }

    private function makeResponse(Http\Environment $environment): Response
    {
        $protocol_version = $environment->getProtocolVersion();
        $response = new Response(Response::STATUS_OK, null, null, $protocol_version);
        return $response;
    }

    private function makeRequest(Http\Cookies $cookies,
        Http\Environment $environment,
        Http\Headers $headers): Request
    {
        $method = $environment->REQUEST_METHOD;
        $uri    = $this->makeUriByEnv($environment);
        $body   = new Http\Stream($this->makeBodyStream());

        $protocol_version = $environment->getProtocolVersion();

        $upload_files = $this->makeUploadFiles($_FILES);

        $request = new Request($method, $uri,
            $cookies->all(), $environment->all(), $upload_files,
            $body, $headers, $protocol_version);
        return $request;
    }

    private function makeBodyStream() {
        $stream = fopen('php://temp', 'w+');
        stream_copy_to_stream(fopen('php://input', 'r'), $stream);
        rewind($stream);
        return $stream;
    }

    private function makeUploadFiles(array $files): array {
        $upload_files = [];
        foreach ($files as $key => $file) {
            if (!isset($file['error']) && is_array($file)) {
                $upload_files[$key] = $this->makeUploadFiles($file);
                continue;
            }

            $upload_files[$key] = [];
            if (!is_array($file['error'])) {
                $upload_files[$key] = new static(
                    $file['tmp_name'],
                    $file['name'] ?? null,
                    $file['type'] ?? null,
                    $file['size'] ?? null,
                    $file['error'],
                    true
                );
            } else {
                $subs = [];
                foreach ($file['error'] as $sub_key => $error) {
                    $subs[$sub_key]['name']     = $file['name'][$sub_key];
                    $subs[$sub_key]['type']     = $file['type'][$sub_key];
                    $subs[$sub_key]['tmp_name'] = $file['tmp_name'][$sub_key];
                    $subs[$sub_key]['error']    = $file['error'][$sub_key];
                    $subs[$sub_key]['size']     = $file['size'][$sub_key];

                    $upload_files[$key] = $this->makeUploadFiles($subs);
                }
            }
        }

        return $upload_files;
    }

    private function makeUriByEnv(Http\Environment $env): Http\Uri {
        $host = $env->HTTP_HOST  ?: $env->SERVER_NAME ?: '127.0.0.1';
        $port = $env->SERVE_PORT ?: 80;

        // 兼容ipv6
        if (preg_match('/^(\[[a-fA-F0-9:.]+\]):(\d+)?$/', $host, $matches)) {
            $host = $matches[1];
            $matches[2] && $port = $matches[2];
        } else {
            $pos = strpos($host, ':');
            if ($pos !== false) {
                $port = substr($host, ++$pos);
                $host = strstr($host, ':', true);
            }
        }

        return new Http\Uri([
            'scheme'    => ($env->HTTPS && $env->HTTPS == 'on') ? 'https' : 'http',
            'host'      => $host,
            'port'      => $port,
            'path'      => $env->PATH_INFO,
            'query'     => $env->QUERY_STRING,
            'fragment'  => '',
            'user'      => $env->PHP_AUTH_USER  ?: '',
            'password'  => $env->PHP_AUTH_PW    ?: '',
        ]);
    }
}