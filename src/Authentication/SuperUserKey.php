<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Authentication;

use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Psr\Http\Message\RequestInterface;

/**
 * A super-user key, optionally acting as a particular user.
 *
 * Two capabilities belong to this key type and no other, which is why it is a class of its
 * own rather than a flag on ApiKey:
 *
 *   - XF-Api-User names the user to act as. XenForo validates it against the key
 *     (\XF\Api\App::validateRequest()) and answers 403 user_id_not_allowed if a non-super
 *     key sends one, so this cannot be moved onto ApiKey without inviting that error.
 *   - api_bypass_permissions=1 runs the request without permission checks at all. It is a
 *     query parameter rather than a header, and XenForo ignores it for every other key
 *     type.
 *
 * Omitting the user - `new SuperUserKey($key)` - acts as a guest, which is the XenForo
 * default and usually not what a super-user key was created for. It is spelled out here
 * rather than defaulted to anything, because "acting as nobody" and "acting as user 1" are
 * both reasonable and the wrong one is silent.
 */
final class SuperUserKey extends ApiKey
{
    /**
     * @param  int|null  $actingAs  the user_id to act as, or null to act as a guest
     * @param  bool  $bypassPermissions  run without permission checks. Off by default:
     *                                   a super-user key can turn this on, and a client
     *                                   that always had it on would hide every genuine
     *                                   permission problem until something else met it.
     */
    public function __construct(
        string $key,
        public readonly ?int $actingAs = null,
        public readonly bool $bypassPermissions = false,
    ) {
        parent::__construct($key);

        if ($actingAs !== null && $actingAs < 1) {
            throw new InvalidArgumentException(
                'A XenForo user to act as must be a positive user_id, or null to act as a guest.'
            );
        }
    }

    /**
     * The same key, acting as somebody else. Immutable, so a request made on behalf of one
     * user cannot leak into the next one.
     */
    public function actingAs(?int $userId): self
    {
        return new self($this->key, $userId, $this->bypassPermissions);
    }

    public function withBypassPermissions(bool $bypass = true): self
    {
        return new self($this->key, $this->actingAs, $bypass);
    }

    public function applyTo(RequestInterface $request): RequestInterface
    {
        $request = parent::applyTo($request);

        if ($this->actingAs !== null) {
            $request = $request->withHeader('XF-Api-User', (string) $this->actingAs);
        }

        if ($this->bypassPermissions) {
            // A query parameter rather than a header, so it has to go on the URI - which
            // is why this lives in the credential rather than in Connection: the parameter
            // is only meaningful for this key type, and putting it anywhere else would
            // mean every call site deciding whether it applies.
            $uri = $request->getUri();
            $query = $uri->getQuery();

            $request = $request->withUri($uri->withQuery(
                ($query === '' ? '' : $query . '&') . 'api_bypass_permissions=1'
            ));
        }

        return $request;
    }

    public function describe(): string
    {
        return $this->actingAs === null
            ? 'super-user API key (acting as guest)'
            : sprintf('super-user API key (acting as user %d)', $this->actingAs);
    }
}
