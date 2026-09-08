<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * A successful status carrying a body that is not the API's.
 *
 * The forum is not the only thing that answers on its URL. A maintenance page, a WAF
 * challenge, a CDN interstitial and a truncated response all arrive as a 200 with HTML or
 * nothing in it - and on a forum behind Cloudflare that is several distinct states, none
 * of them rare. Decoded as "no data" they would read as an ordinary negative answer:
 * find() returning null, a list coming back empty. That is the same hazard apiFind()'s
 * 404 handling is careful about, one layer down, and it has to raise rather than return.
 *
 * An ApiException rather than a sibling of one, so a caller catching "the forum did not
 * give me what I asked for" gets it without having to know it exists. It carries no error
 * codes, since there was no JSON to carry them in; the body is on ->body as usual.
 */
final class MalformedResponseException extends ApiException
{
    public static function forResponse(
        string $method,
        string $uri,
        ResponseInterface $response,
        string $body,
    ): self {
        $contentType = $response->getHeaderLine('Content-Type');
        $summary = self::summarise($body);

        return new self(
            sprintf(
                'The XenForo API answered %s %s with HTTP %d but not with JSON (%s)%s',
                $method,
                $uri,
                $response->getStatusCode(),
                $contentType === '' ? 'no Content-Type' : $contentType,
                $summary === '' ? ': the body was empty' : ': ' . $summary
            ),
            $response->getStatusCode(),
            [],
            $body
        );
    }
}
