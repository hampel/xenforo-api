<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api;

use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Hampel\XenForo\Api\Exception\RuntimeException;
use Hampel\XenForo\Api\Support\Payload;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * A multipart/form-data body: ordinary fields, and files.
 *
 * Written here rather than taken from an HTTP library, because taking it from one would
 * undo the point of the package - Guzzle's multipart stream is excellent and requiring it
 * would mean a consumer with its own PSR-18 client had to install Guzzle anyway, to upload
 * an avatar.
 *
 * The body is assembled into a php://temp stream, which spills to disk past a couple of
 * megabytes, and each file is copied through in chunks. So a forum's attachment limit is
 * the only thing bounding an upload here, not this process's memory.
 */
final class Multipart
{
    /**
     * Small enough that a handful of concurrent uploads cannot exhaust memory, large
     * enough that an avatar never touches the disk.
     */
    private const MEMORY_LIMIT = 2 * 1024 * 1024;

    private const CHUNK = 8192;

    public readonly string $boundary;

    /** @var array<string, Upload> */
    private readonly array $files;

    /**
     * @param  array<string, mixed>  $fields  ordinary input, named exactly as a
     *                                        form-encoded body would name it
     * @param  array<string, mixed>  $files  keyed by the input name the endpoint reads -
     *        `attachment`, `avatar`, `image` - and every value an Upload. Checked here
     *        rather than merely documented, because this is the point at which an array of
     *        unknown provenance becomes a request body, and the likely mistake - passing
     *        the path instead of Upload::fromPath($path) - is worth naming.
     */
    public function __construct(
        private readonly array $fields = [],
        array $files = [],
        ?string $boundary = null,
    ) {
        $uploads = [];

        foreach ($files as $name => $file) {
            if (!$file instanceof Upload) {
                throw new InvalidArgumentException(sprintf(
                    'The file "%s" must be a %s, not %s - see %s::fromPath().',
                    (string) $name,
                    Upload::class,
                    get_debug_type($file),
                    Upload::class
                ));
            }

            $uploads[Payload::assertHeaderSafe((string) $name, 'field name')] = $file;
        }

        $this->files = $uploads;

        // A boundary must not appear in any part's content. 32 hex characters of
        // randomness is how every other implementation makes that certain rather than
        // scanning the payload, and the odds are the same odds a UUID rests on.
        $this->boundary = $boundary ?? '----XenForoApi' . bin2hex(random_bytes(16));
    }

    /**
     * The Content-Type header this body must be sent with.
     *
     * Unlike the form-encoded type - see Connection::FORM_CONTENT_TYPE, which must be
     * written bare - this one is required to carry a parameter, because the boundary is
     * the only way the far end can find where the parts begin.
     */
    public function contentType(): string
    {
        return Connection::MULTIPART_CONTENT_TYPE . '; boundary="' . $this->boundary . '"';
    }

    public function stream(StreamFactoryInterface $factory): StreamInterface
    {
        $handle = fopen('php://temp/maxmemory:' . self::MEMORY_LIMIT, 'w+b');

        if ($handle === false) {
            throw new RuntimeException('Could not open a temporary stream to build the upload body.');
        }

        foreach (Payload::flatten($this->fields) as [$name, $value]) {
            fwrite($handle, sprintf(
                "--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n%s\r\n",
                $this->boundary,
                Payload::assertHeaderSafe($name, 'field name'),
                $value
            ));
        }

        foreach ($this->files as $name => $file) {
            fwrite($handle, sprintf(
                "--%s\r\nContent-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\nContent-Type: %s\r\n\r\n",
                $this->boundary,
                (string) $name,
                $file->filename,
                $file->contentType
            ));

            $source = $file->stream($factory);

            while (!$source->eof()) {
                $chunk = $source->read(self::CHUNK);

                if ($chunk === '') {
                    // A stream that has run out without saying so. Believing eof() over
                    // this would spin here forever on somebody else's implementation.
                    break;
                }

                fwrite($handle, $chunk);
            }

            fwrite($handle, "\r\n");
        }

        fwrite($handle, sprintf("--%s--\r\n", $this->boundary));

        rewind($handle);

        return $factory->createStreamFromResource($handle);
    }
}
