<?php

namespace Combi\Web\Http;


use Combi\{
    Helper as helper,
    Abort as abort,
    Runtime as rt
};

use Psr\Http\Message\{
    StreamInterface
};

class Stream implements StreamInterface
{
    const FSTAT_MODE_PIPE = 0x1000;
    const MODES_READABLE  = ['r', 'r+', 'w+', 'a+', 'x+', 'c+'];
    const MODES_WRITABLE  = ['r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'];

    /**
     *
     * @var resource
     */
    private $resource;

    /**
     * Stream metadata
     *
     * @var array
     */
    protected $meta;

    /**
     * Is this stream readable?
     *
     * @var bool
     */
    protected $readable;

    /**
     * Is this stream writable?
     *
     * @var bool
     */
    protected $writable;

    /**
     * Is this stream seekable?
     *
     * @var bool
     */
    protected $seekable;

    /**
     * The size of the stream if known
     *
     * @var null|int
     */
    protected $size = -1;

    /**
     * Is this stream a pipe?
     *
     * @var bool
     */
    protected $is_pipe = null;

    /**
     * Create a new Stream.
     *
     * @param  resource $stream A PHP resource handle.
     * @throws \InvalidArgumentException If argument is not a resource.
     */
    public function __construct($resource)
    {
        if (is_resource($resource) === false) {
            throw new \InvalidArgumentException(
                "Need a valid resoure to create stream");
        }
        $this->resource = $resource;
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * @return string
     */
    public function __toString(): string
    {
        try {
            $this->rewind();
            return $this->getContents();
        } catch (\RuntimeException $e) {
            return '';
        }
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close(): void
    {
        $resource = $this->getResource();
        if ($this->isPipe()) {
            pclose($resource);
        } else {
            fclose($resource);
        }
        $this->detach();
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $oldResource = $this->resource;
        $this->resource = null;
        $this->meta = null;
        $this->readable = null;
        $this->writable = null;
        $this->seekable = null;
        $this->size = -1;
        $this->is_pipe = null;

        return $oldResource;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize(): ?int
    {
        if ($this->size === -1) {
            $stats = fstat($this->getResource());
            $this->size = (isset($stats['size']) && !$this->isPipe())
                ? $stats['size'] : null;
        }

        return $this->size;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell(): int
    {
        if (($position = ftell($this->getResource())) === false || $this->isPipe()) {
            throw new \RuntimeException(
                "Could not get the position of the pointer in the stream");
        }
        return $position;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof(): bool
    {
        return feof($this->getResource());
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable(): bool
    {
        if ($this->seekable === null) {
            $meta = $this->getMetadata();
            $this->seekable = !$this->isPipe() && $meta['seekable'];
        }

        return $this->seekable;
    }

    /**
     * Seek to a position in the stream.
     *
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @return void
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()
            || fseek($this->getResource(), $offset, $whence) === -1)
        {
            throw new \RuntimeException("Could not seek in the stream");
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * @return void
     * @throws \RuntimeException on failure.
     */
    public function rewind(): void
    {
        if (!$this->isSeekable() || rewind($this->getResource()) === false) {
            throw new \RuntimeException("Could not rewind stream");
        }
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable(): bool
    {
        if ($this->readable === null) {
            if ($this->isPipe()) {
                $this->readable = true;
            } else {
                $this->readable = false;
                $meta = $this->getMetadata();
                foreach (self::MODES_READABLE as $mode) {
                    if (strpos($meta['mode'], $mode) === 0) {
                        $this->readable = true;
                        break;
                    }
                }
            }
        }

        return $this->readable;
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        if ($this->writable === null) {
            $this->writable = false;
            $meta = $this->getMetadata();
            foreach (self::MODES_WRITABLE as $mode) {
                if (strpos($meta['mode'], $mode) === 0) {
                    $this->writable = true;
                    break;
                }
            }
        }

        return $this->writable;
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length): string
    {
        if (!$this->isReadable()
            || ($data = fread($this->getResource(), $length)) === false)
        {
            throw new \RuntimeException("Could not read from the stream");
        }

        return $data;
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string): int
    {
        if (!$this->isWritable()
            || ($written = fwrite($this->getResource(), $string)) === false)
        {
            throw new \RuntimeException("Could not write to the stream");
        }
        $this->size = -1;
        return $written;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        $this->meta = stream_get_meta_data($this->getResource());
        if ($key === null) {
            return $this->meta;
        }

        return $this->meta[$key] ?? null;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents()
    {
        if (!$this->isReadable()
            || ($contents = stream_get_contents($this->getResource())) === false)
        {
            throw new \RuntimeException("Could not get contents of the stream");
        }

        return $contents;
    }

    /**
     *
     * @return resource
     */
    protected function getResource() {
        if (!$this->resource) {
            throw new \RuntimeException("The stream is unuseable now");
        }
        return $this->resource;
    }

    /**
     *
     * @return bool
     */
    protected function isPipe(): bool
    {
        if ($this->is_pipe === null) {
            $mode = fstat($this->getResource())['mode'];
            $this->is_pipe = ($mode & self::FSTAT_MODE_PIPE) !== 0;
        }

        return $this->is_pipe;
    }
}