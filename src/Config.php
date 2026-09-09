<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api;

use Hampel\XenForo\Api\Exception\InvalidArgumentException;

/**
 * Which forum we are talking to, and how its URLs are built.
 *
 * A XenForo API lives at `<board url>/api/`, and the board URL is whatever the
 * administrator chose - there is no vendor-hosted endpoint and no regional variation, so
 * unlike most API clients this one has no default host and cannot be constructed without
 * being told where to go.
 */
final class Config
{
    public readonly string $baseUri;

    /**
     * @param  string  $baseUri  the API root, e.g. https://forum.example.com/api - the
     *                           board URL with or without the /api suffix, either works
     * @param  int|null  $version  pin requests to an API version, producing /api/vN/...
     *                             URLs. Null uses whatever the forum considers current,
     *                             which is what the XF-Used-Api-Version response header
     *                             then reports.
     */
    public function __construct(string $baseUri, public readonly ?int $version = null)
    {
        $baseUri = trim($baseUri);

        if ($baseUri === '') {
            throw new InvalidArgumentException('A XenForo API base URI is required.');
        }

        if (!str_starts_with($baseUri, 'http://') && !str_starts_with($baseUri, 'https://')) {
            throw new InvalidArgumentException(sprintf(
                'The XenForo API base URI must be absolute, with a scheme: "%s" is not.',
                $baseUri
            ));
        }

        if ($version !== null && $version < 1) {
            throw new InvalidArgumentException('A XenForo API version must be 1 or greater.');
        }

        $baseUri = rtrim($baseUri, '/');

        // Accept the board URL as readily as the API URL. Both get typed, and getting it
        // wrong produces a 404 from the forum's front end rather than an API error, which
        // is a confusing thing to debug.
        if (!str_ends_with($baseUri, '/api')) {
            $baseUri .= '/api';
        }

        $this->baseUri = $baseUri;
    }

    /**
     * Turn a path into an absolute URI.
     *
     * Three shapes arrive here. A relative path - `users/1/` - is the usual one. An
     * absolute URI passes through untouched, which is what lets a link the API itself
     * returned be fed straight back in. And a path that already carries the /api prefix,
     * or a version segment we would otherwise add, is normalised rather than doubled.
     *
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    public function resolve(string $path, array $query = []): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $uri = $path;
        } else {
            $path = ltrim($path, '/');

            if (str_starts_with($path, 'api/')) {
                $path = substr($path, 4);
            }

            if ($this->version !== null && !preg_match('#^v\d+(/|$)#', $path)) {
                $path = 'v' . $this->version . '/' . $path;
            }

            $uri = $this->baseUri . '/' . $path;
        }

        $query = array_filter($query, static fn ($value): bool => $value !== null);

        if ($query !== []) {
            $uri .= (str_contains($uri, '?') ? '&' : '?') . self::buildQuery($query);
        }

        return $uri;
    }

    /**
     * The host the client will talk to, for anything that needs to name the forum - a log
     * line, an exception message, a settings screen.
     */
    public function host(): string
    {
        return parse_url($this->baseUri, PHP_URL_HOST) ?: $this->baseUri;
    }

    /**
     * XenForo reads input with parse_str(), so nested values are PHP's bracket notation -
     * `custom_fields[location]=Sydney`, `node_ids[]=2` - which is exactly what
     * http_build_query() emits. Encoding as RFC 3986 rather than the default RFC 1738 only
     * changes how a space is written; parse_str() accepts both.
     *
     * @param  array<array-key, scalar|array<mixed>|null>  $query
     */
    public static function buildQuery(array $query): string
    {
        return http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
