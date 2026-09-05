<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Result\SiteInfo;

/**
 * `GET /index/` - what this forum is, and what the credential in use may do.
 *
 * The first call worth making against a forum you have just been given the URL and key
 * for. It answers three questions no other endpoint does: whether the base URI reaches an
 * API at all, what XenForo version is behind it, and whether the key is a guest, user or
 * super key with which scopes. A client that checks this at startup fails with something
 * legible instead of a 404 from the first real call.
 */
final class Index extends Endpoint
{
    public function get(): SiteInfo
    {
        return SiteInfo::fromArray($this->connection->get('index/')->data);
    }

    /**
     * A cheap reachability and credential check. Returns false rather than throwing for
     * anything the forum answered, so it can be used in a health check without a try.
     */
    public function ping(): bool
    {
        try {
            $this->get();

            return true;
        } catch (\Hampel\XenForo\Api\Exception\ExceptionInterface $e) {
            $this->logger->warning('XenForo API ping failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
