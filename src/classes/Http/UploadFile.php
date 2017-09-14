<?php

namespace Combi\Web\Http;


use Combi\{
    Helper as helper,
    Abort as abort,
    Runtime as rt
};

use Psr\Http\Message\{
    StreamInterface,
    UploadFileInterface
};

class UploadFile implements UploadFileInterface
{
    /**
     *
     * @var string
     */
    public $file;

    /**
     *
     * @var string
     */
    protected $name;

    /**
     *
     * @var string
     */
    protected $type;

    /**
     *
     * @var int
     */
    protected $size;

    /**
     *
     * @var int
     */
    protected $error;

    /**
     *
     * @var bool
     */
    protected $sapi;

    /**
     *
     * @var StreamInterface
     */
    protected $stream;

    /**
     *
     * @var bool
     */
    protected $moved = false;

    public function __construct(string $file, ?string $name = null,
        ?string $type = null, ?int $size = null,
        int $error = UPLOAD_ERR_OK, bool $sapi = false)
    {
        $this->file = $file;
        $this->name = $name;
        $this->type = $type;
        $this->size = $size;
        $this->error = $error;
        $this->sapi = $sapi;
    }

    /**
     * Retrieve a stream representing the uploaded file.
     *
     * @return StreamInterface Stream representation of the uploaded file.
     * @throws \RuntimeException in cases when no stream is available or can be
     *     created.
     */
    public function getStream(): StreamInterface {
        if ($this->moved) {
            throw new \RuntimeException(
                "Uploaded file $this->name has already been moved");
        }
        if ($this->stream === null) {
            $this->stream = new Stream(fopen($this->file, 'r'));
        }

        return $this->stream;
    }

    /**
     * Move the uploaded file to a new location.
     *
     * @param string $target_path Path to which to move the uploaded file.
     * @throws \InvalidArgumentException if the $target_path specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    public function moveTo($target_path): void {
        if ($this->moved) {
            throw new \RuntimeException('Uploaded file already moved');
        }

        $is_stream = strpos($target_path, '://') > 0;
        if (!$is_stream && !is_writable(dirname($target_path))) {
            throw new \InvalidArgumentException('Upload target path is not writable');
        }

        if ($is_stream) {
            if (!copy($this->file, $target_path)) {
                throw new \RuntimeException(
                    "Error moving uploaded file $this->name to $target_path");
            }
            if (!unlink($this->file)) {
                throw new \RuntimeException(
                    "Error removing uploaded file $this->name");
            }
        } elseif ($this->sapi) {
            if (!is_uploaded_file($this->file)) {
                throw new \RuntimeException(
                    "$this->file is not a valid uploaded file");
            }

            if (!move_uploaded_file($this->file, $target_path)) {
                throw new \RuntimeException(
                    "Error moving uploaded file $this->name to $target_path");
            }
        } else {
            if (!rename($this->file, $target_path)) {
                throw new \RuntimeException(
                    "Error moving uploaded file $this->name to $target_path");
            }
        }

        $this->moved = true;
    }

    /**
     * Retrieve the file size.
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize(): ?int {
        return $this->size;
    }

    /**
     * Retrieve the error associated with the uploaded file.
     *
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError(): int {
        return $this->error;
    }

    /**
     * Retrieve the filename sent by the client.
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getClientFilename(): ?string {
        return $this->name;
    }

    /**
     * Retrieve the media type sent by the client.
     *
     * @return string|null The media type sent by the client or null if none
     *     was provided.
     */
    public function getClientMediaType(): ?string {
        return $this->name;
    }
}