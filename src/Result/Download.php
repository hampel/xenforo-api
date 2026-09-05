<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Result;

use Hampel\XenForo\Api\Exception\RuntimeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * A file coming back from the forum: the counterpart of Upload, and read as lazily.
 *
 * The stream is the response's own, unread, so a 40MB attachment can go to disk without
 * ever being a PHP string. Nothing here buffers unless you ask it to.
 *
 * The filename and the type come from the response rather than from the Attachment entity,
 * and they are not the same answers. XenForo decides at download time whether the file is
 * safe to display inline: an image gets its real type and `Content-Disposition: inline`,
 * and anything else gets `application/octet-stream` and `attachment`, whatever the file
 * really is. So the type here is what the forum is willing to serve it as - which, if you
 * are passing the file on to a browser, is the more useful of the two answers.
 */
final class Download
{
    private const CHUNK = 8192;

    public function __construct(
        public readonly StreamInterface $stream,
        public readonly ?string $filename = null,
        public readonly ?string $contentType = null,
        public readonly ?int $size = null,
    ) {
    }

    public static function fromResponse(ResponseInterface $response): self
    {
        $length = $response->getHeaderLine('Content-Length');

        return new self(
            $response->getBody(),
            self::filename($response->getHeaderLine('Content-Disposition')),
            self::type($response->getHeaderLine('Content-Type')),
            ctype_digit($length) ? (int) $length : null,
        );
    }

    /**
     * The whole file as a string. Fine for an avatar; think before doing it to a video.
     */
    public function contents(): string
    {
        return (string) $this->stream;
    }

    /**
     * Copy the file to disk, a chunk at a time, and return how many bytes were written.
     *
     * Overwrites an existing file, as any other write does. Pass a full path including the
     * name you want - `$download->filename` is the forum's suggestion and is not to be
     * trusted as a path component, being chosen by whoever uploaded the file.
     */
    public function saveTo(string $path): int
    {
        // Silenced because the failure is being reported as an exception naming the path:
        // a warning as well is the same news twice, and in a test run it is noise.
        $handle = @fopen($path, 'wb');

        if ($handle === false) {
            throw new RuntimeException(sprintf('Could not open "%s" for writing.', $path));
        }

        $written = 0;

        try {
            while (!$this->stream->eof()) {
                $chunk = $this->stream->read(self::CHUNK);

                if ($chunk === '') {
                    break;
                }

                $bytes = fwrite($handle, $chunk);

                if ($bytes === false) {
                    throw new RuntimeException(sprintf('Writing to "%s" failed part way through.', $path));
                }

                $written += $bytes;
            }
        } finally {
            fclose($handle);
        }

        return $written;
    }

    /**
     * RFC 6266: `filename*` wins where both are present, being the one that can carry a
     * name outside ASCII. XenForo strips quotes from the name before writing either, so
     * there is no escaping to undo.
     */
    private static function filename(string $disposition): ?string
    {
        if ($disposition === '') {
            return null;
        }

        if (preg_match("/;\s*filename\*=UTF-8''([^;]+)/i", $disposition, $matches) === 1) {
            return rawurldecode($matches[1]);
        }

        return preg_match('/;\s*filename="([^"]*)"/i', $disposition, $matches) === 1 && $matches[1] !== ''
            ? $matches[1]
            : null;
    }

    private static function type(string $contentType): ?string
    {
        if ($contentType === '') {
            return null;
        }

        // `image/png; charset=UTF-8` - the parameter is noise to a caller asking what the
        // file is, and XenForo sends an empty charset on these responses anyway.
        $type = trim(explode(';', $contentType, 2)[0]);

        return $type === '' ? null : $type;
    }
}
