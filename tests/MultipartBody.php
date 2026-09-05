<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Psr\Http\Message\RequestInterface;

/**
 * A multipart body, taken apart again.
 *
 * The tests assert against this rather than against the raw string, because splitting the
 * body back into parts is itself the assertion that matters: a body that cannot be parsed
 * on the boundary its own header declares is one PHP would hand XenForo as an empty
 * $_POST, with no error anywhere.
 */
final class MultipartBody
{
    /**
     * @param  array<string, array{value: string, filename: string|null, type: string|null}>  $parts
     */
    private function __construct(
        public readonly string $boundary,
        public readonly array $parts,
    ) {
    }

    public static function fromRequest(RequestInterface $request): self
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if (preg_match('/^multipart\/form-data;\s*boundary="?([^";]+)"?$/', $contentType, $matches) !== 1) {
            throw new \LogicException(sprintf('Not a multipart request: Content-Type is "%s".', $contentType));
        }

        $boundary = $matches[1];
        $segments = explode('--' . $boundary, (string) $request->getBody());

        array_shift($segments); // whatever precedes the first boundary, which is nothing

        $parts = [];

        foreach ($segments as $segment) {
            if (str_starts_with($segment, '--')) {
                break; // the closing delimiter
            }

            [$headers, $content] = array_pad(explode("\r\n\r\n", ltrim($segment, "\r\n"), 2), 2, '');

            $name = self::header($headers, 'name');

            if ($name === null) {
                throw new \LogicException('A part with no name: ' . $headers);
            }

            $parts[$name] = [
                // every part's content is followed by the CRLF that precedes its boundary
                'value' => substr($content, 0, -2),
                'filename' => self::header($headers, 'filename'),
                'type' => self::contentType($headers),
            ];
        }

        return new self($boundary, $parts);
    }

    /**
     * The parts as PHP would see them in $_POST, which is what the assertions are really
     * about: `context[post_id]` is only correct if parse_str() makes it nest.
     *
     * @return array<mixed>
     */
    public function asInput(): array
    {
        $query = [];

        foreach ($this->parts as $name => $part) {
            if ($part['filename'] !== null) {
                continue; // a file is not $_POST input
            }

            $query[] = rawurlencode($name) . '=' . rawurlencode($part['value']);
        }

        parse_str(implode('&', $query), $parsed);

        return $parsed;
    }

    private static function header(string $headers, string $parameter): ?string
    {
        return preg_match('/;\s*' . $parameter . '="([^"]*)"/', $headers, $matches) === 1
            ? $matches[1]
            : null;
    }

    private static function contentType(string $headers): ?string
    {
        return preg_match('/^Content-Type:\s*(.+)$/mi', $headers, $matches) === 1
            ? trim($matches[1])
            : null;
    }
}
