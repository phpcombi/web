<?php

namespace Combi\Web\Http;


use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use Combi\Web as inner;

use Psr\Http\Message\{
    HeadersInterface,
    StreamInterface,
    ResponseInterface
};
use Fig\Http\Message\StatusCodeInterface;

class Response extends Message implements ResponseInterface, StatusCodeInterface
{
    const STATUS_CODE_MESSAGES = [
        //Informational 1xx
        self::STATUS_CONTINUE => 'Continue',
        self::STATUS_SWITCHING_PROTOCOLS => 'Switching Protocols',
        self::STATUS_PROCESSING => 'Processing',
        //Successful 2xx
        self::STATUS_OK => 'OK',
        self::STATUS_CREATED => 'Created',
        self::STATUS_ACCEPTED => 'Accepted',
        self::STATUS_NON_AUTHORITATIVE_INFORMATION => 'Non-Authoritative Information',
        self::STATUS_NO_CONTENT => 'No Content',
        self::STATUS_RESET_CONTENT => 'Reset Content',
        self::STATUS_PARTIAL_CONTENT => 'Partial Content',
        self::STATUS_MULTI_STATUS => 'Multi-Status',
        self::STATUS_ALREADY_REPORTED => 'Already Reported',
        self::STATUS_IM_USED => 'IM Used',
        //Redirection 3xx
        self::STATUS_MULTIPLE_CHOICES => 'Multiple Choices',
        self::STATUS_MOVED_PERMANENTLY => 'Moved Permanently',
        self::STATUS_FOUND => 'Found',
        self::STATUS_SEE_OTHER => 'See Other',
        self::STATUS_NOT_MODIFIED => 'Not Modified',
        self::STATUS_USE_PROXY => 'Use Proxy',
        self::STATUS_RESERVED => '(Unused)',
        self::STATUS_TEMPORARY_REDIRECT => 'Temporary Redirect',
        self::STATUS_PERMANENT_REDIRECT => 'Permanent Redirect',
        //Client Error 4xx
        self::STATUS_BAD_REQUEST => 'Bad Request',
        self::STATUS_UNAUTHORIZED => 'Unauthorized',
        self::STATUS_PAYMENT_REQUIRED => 'Payment Required',
        self::STATUS_FORBIDDEN => 'Forbidden',
        self::STATUS_NOT_FOUND => 'Not Found',
        self::STATUS_METHOD_NOT_ALLOWED => 'Method Not Allowed',
        self::STATUS_NOT_ACCEPTABLE => 'Not Acceptable',
        self::STATUS_PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required',
        self::STATUS_REQUEST_TIMEOUT => 'Request Timeout',
        self::STATUS_CONFLICT => 'Conflict',
        self::STATUS_GONE => 'Gone',
        self::STATUS_LENGTH_REQUIRED => 'Length Required',
        self::STATUS_PRECONDITION_FAILED => 'Precondition Failed',
        self::STATUS_PAYLOAD_TOO_LARGE => 'Request Entity Too Large',
        self::STATUS_URI_TOO_LONG => 'Request-URI Too Long',
        self::STATUS_UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type',
        self::STATUS_RANGE_NOT_SATISFIABLE => 'Requested Range Not Satisfiable',
        self::STATUS_EXPECTATION_FAILED => 'Expectation Failed',
        self::STATUS_IM_A_TEAPOT => 'I\'m a teapot',
        self::STATUS_MISDIRECTED_REQUEST => 'Misdirected Request',
        self::STATUS_UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
        self::STATUS_LOCKED => 'Locked',
        self::STATUS_FAILED_DEPENDENCY => 'Failed Dependency',
        self::STATUS_UPGRADE_REQUIRED => 'Upgrade Required',
        self::STATUS_PRECONDITION_REQUIRED => 'Precondition Required',
        self::STATUS_TOO_MANY_REQUESTS => 'Too Many Requests',
        self::STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE => 'Request Header Fields Too Large',
        // 444 => 'Connection Closed Without Response',
        self::STATUS_UNAVAILABLE_FOR_LEGAL_REASONS => 'Unavailable For Legal Reasons',
        // 499 => 'Client Closed Request',
        //Server Error 5xx
        self::STATUS_INTERNAL_SERVER_ERROR => 'Internal Server Error',
        self::STATUS_NOT_IMPLEMENTED => 'Not Implemented',
        self::STATUS_BAD_GATEWAY => 'Bad Gateway',
        self::STATUS_SERVICE_UNAVAILABLE => 'Service Unavailable',
        self::STATUS_GATEWAY_TIMEOUT => 'Gateway Timeout',
        self::STATUS_VERSION_NOT_SUPPORTED => 'HTTP Version Not Supported',
        self::STATUS_VARIANT_ALSO_NEGOTIATES => 'Variant Also Negotiates',
        self::STATUS_INSUFFICIENT_STORAGE => 'Insufficient Storage',
        self::STATUS_LOOP_DETECTED => 'Loop Detected',
        self::STATUS_NOT_EXTENDED => 'Not Extended',
        self::STATUS_NETWORK_AUTHENTICATION_REQUIRED => 'Network Authentication Required',
        // 599 => 'Network Connect Timeout Error',
    ];


    /**
     * Status code
     *
     * @var int
     */
    protected $status = 200;

    /**
     *
     * @var string
     */
    protected $reason_phrase = self::STATUS_CODE_MESSAGES[self::STATUS_OK];

    public function __construct(int $status = self::STATUS_OK,
        ?HeadersInterface $headers = null,
        ?StreamInterface $body = null,
        ?string $protocol_version = null)
    {
        parent::__construct($body ?: new Stream(fopen('php://temp', 'r+')),
            $headers ?: new Headers(),
            $protocol_version);

        $this->checkStatusCode($status);
        $this->status = $status;
    }

    public function __clone() {
        $this->headers  = clone $this->headers;
    }

    /**
     * Gets the response status code.
     *
     * @return int Status code.
     */
    public function getStatusCode(): int {
        return $this->status;
    }

    private function checkStatusCode(int $code): void {
        if (!isset(static::STATUS_CODE_MESSAGES[$code])) {
            throw new \InvalidArgumentException(
                "The status code $code is not support.");
        }
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * @param int $code The 3-digit integer result code to set.
     * @param string $reason_phrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return static
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reason_phrase = ''): self {
        $this->checkStatusCode($code);

        if (!is_string($reason_phrase)
            && !method_exists($reason_phrase, '__toString'))
        {
            throw new \InvalidArgumentException('ReasonPhrase must be a string');
        }

        $clone = clone $this;
        $clone->status = $code;
        if ($reason_phrase === '' && isset(static::STATUS_CODE_MESSAGES[$code])) {
            $reason_phrase = static::STATUS_CODE_MESSAGES[$code];
        }

        if ($reason_phrase === '') {
            throw new \InvalidArgumentException(
                'ReasonPhrase must be supplied for this code');
        }

        $clone->reason_phrase = $reason_phrase;
        return $clone;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * @return string Reason phrase; must return an empty string if none present.
     */
    public function getReasonPhrase(): string {
        return $this->reason_phrase ?: static::STATUS_CODE_MESSAGES[$this->status];
    }

    /**
     *
     * @return string
     */
    public function __toString(): string
    {
        $output = 'HTTP/'.$this->getProtocolVersion().' '.
            $this->getStatusCode().' '.
            $this->getReasonPhrase().static::EOL;

        foreach ($this->getHeaders() as $name => $values) {
            $output .= $name.': '.$this->getHeaderLine($name).static::EOL;
        }
        $output .= static::EOL;
        $output .= $this->getBody();

        return $output;
    }

}