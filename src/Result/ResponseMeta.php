<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Result;

use Psr\Http\Message\ResponseInterface;

/**
 * The four headers XenForo puts on every API response, core and add-on alike.
 *
 * They are the only way a client finds out that the forum has moved on underneath it.
 * `latestVersion` above `usedVersion` means this forum now offers an API version newer
 * than the one being spoken - which is not an error and will never be reported as one, so
 * a long-lived integration that never looks at these will keep working against an old
 * version until the day it stops.
 *
 * `requestUser` is who the forum decided the request was acting as, which is worth logging
 * for a super-user key: it is the difference between "acted as the intended user" and
 * "silently acted as a guest".
 */
final class ResponseMeta
{
    /**
     * @param  list<string>  $requestUserExtras
     */
    public function __construct(
        public readonly ?int $usedVersion = null,
        public readonly ?int $latestVersion = null,
        public readonly ?int $requestUser = null,
        public readonly array $requestUserExtras = [],
    ) {
    }

    public static function fromResponse(ResponseInterface $response): self
    {
        $extras = trim($response->getHeaderLine('XF-Request-User-Extras'));

        return new self(
            self::intHeader($response, 'XF-Used-Api-Version'),
            self::intHeader($response, 'XF-Latest-Api-Version'),
            self::intHeader($response, 'XF-Request-User'),
            $extras === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $extras))))
        );
    }

    /**
     * True when the forum offers a newer API version than the one this request used.
     */
    public function isOutdated(): bool
    {
        return $this->usedVersion !== null
            && $this->latestVersion !== null
            && $this->latestVersion > $this->usedVersion;
    }

    /**
     * @return array<string, mixed>  for a log context
     */
    public function toArray(): array
    {
        return [
            'used_version' => $this->usedVersion,
            'latest_version' => $this->latestVersion,
            'request_user' => $this->requestUser,
        ];
    }

    private static function intHeader(ResponseInterface $response, string $name): ?int
    {
        $value = trim($response->getHeaderLine($name));

        return ctype_digit($value) ? (int) $value : null;
    }
}
