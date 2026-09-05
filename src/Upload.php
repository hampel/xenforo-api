<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api;

use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Hampel\XenForo\Api\Support\Payload;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * A file on its way to the forum: an attachment, an avatar, a featured-content image.
 *
 * Nothing is read here. A path is checked and remembered, and the bytes are not touched
 * until Multipart asks for them at send time, through the PSR-17 factory the client was
 * constructed with - so an attachment larger than memory is streamed rather than loaded,
 * and a caller that already has a stream can hand it straight over.
 */
final class Upload
{
    /**
     * XenForo does not read this.
     *
     * \XF\Http\Request::getFile() builds its \XF\Http\Upload from the temporary file and
     * the FILENAME, and the part's own Content-Type is consulted in exactly one case: a
     * part whose filename is literally `blob`, where it is used to invent an extension for
     * a file the browser did not name. Everywhere else the extension decides what the
     * forum will accept and the file's own contents decide whether it is really an image.
     *
     * So the honest default is the unspecific one, and naming the file correctly matters a
     * great deal more than naming its type. Pass a type if you have a reason to.
     */
    public const DEFAULT_CONTENT_TYPE = 'application/octet-stream';

    private function __construct(
        public readonly string $filename,
        public readonly string $contentType,
        private readonly ?string $path,
        private readonly ?string $contents,
        private readonly ?StreamInterface $source,
    ) {
    }

    /**
     * A file on disk, streamed at send time.
     *
     * @param  string|null  $filename  the name the forum will see, defaulting to the file's
     *                                 own - which is the name whose extension decides
     *                                 whether the forum accepts the upload at all
     */
    public static function fromPath(string $path, ?string $filename = null, ?string $contentType = null): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot upload "%s": there is no readable file there.',
                $path
            ));
        }

        return new self(
            Payload::assertHeaderSafe($filename ?? basename($path), 'filename'),
            $contentType ?? self::DEFAULT_CONTENT_TYPE,
            $path,
            null,
            null
        );
    }

    /**
     * Content this process already holds - an image it generated, a document it rendered.
     */
    public static function fromString(string $contents, string $filename, ?string $contentType = null): self
    {
        return new self(
            Payload::assertHeaderSafe($filename, 'filename'),
            $contentType ?? self::DEFAULT_CONTENT_TYPE,
            null,
            $contents,
            null
        );
    }

    /**
     * An already-open PSR-7 stream - a download being passed through, a temporary stream.
     *
     * Read once, like any stream: an Upload built this way cannot be sent twice unless the
     * stream is seekable, and Multipart does not rewind it for you.
     */
    public static function fromStream(StreamInterface $stream, string $filename, ?string $contentType = null): self
    {
        return new self(
            Payload::assertHeaderSafe($filename, 'filename'),
            $contentType ?? self::DEFAULT_CONTENT_TYPE,
            null,
            null,
            $stream
        );
    }

    /**
     * The bytes, as a stream, made through the client's own PSR-17 factory.
     */
    public function stream(StreamFactoryInterface $factory): StreamInterface
    {
        if ($this->source !== null) {
            return $this->source;
        }

        if ($this->path !== null) {
            return $factory->createStreamFromFile($this->path, 'rb');
        }

        return $factory->createStream((string) $this->contents);
    }
}
