<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Resource;

use Hampel\XenForo\Api\Generated\Schema\XFRM_ResourceUpdate;

/**
 * Resource updates - the `Resource updates` tag, from XenForo Resource Manager.
 *
 * An update is the post announcing a change to a resource. There is no list endpoint here -
 * updates are listed against the resource they belong to, ResourceItems::updates().
 *
 * An add-on's endpoints, absent from a forum without XFRM - see ResourceItems.
 */
final class ResourceUpdates extends Resource
{
    public function get(int $updateId): XFRM_ResourceUpdate
    {
        return XFRM_ResourceUpdate::fromArray(
            $this->apiGet('resource-updates/' . $updateId . '/')->array('update')
        );
    }

    public function find(int $updateId): ?XFRM_ResourceUpdate
    {
        return $this->apiFind(
            'resource-updates/' . $updateId . '/',
            [],
            'update',
            XFRM_ResourceUpdate::fromArray(...)
        );
    }

    /**
     * Post an update against a resource.
     *
     * `$attachmentKey` comes from Attachments::newKey('resource_update', ...) - files are
     * uploaded first and named here, as everywhere else in this API.
     */
    public function create(
        int $resourceId,
        string $title,
        string $message,
        ?string $attachmentKey = null,
    ): XFRM_ResourceUpdate {
        return XFRM_ResourceUpdate::fromArray($this->apiPost('resource-updates/', [
            'resource_id' => $resourceId,
            'title' => $title,
            'message' => $message,
            'attachment_key' => $attachmentKey,
        ])->array('update'));
    }

    /**
     * @param  array<string, mixed>  $payload  title, message, attachment_key,
     *         author_alert, author_alert_reason
     */
    public function update(int $updateId, array $payload): XFRM_ResourceUpdate
    {
        return XFRM_ResourceUpdate::fromArray(
            $this->apiPost('resource-updates/' . $updateId . '/', $payload)->array('update')
        );
    }

    /**
     * @param  array<string, scalar|null>  $options  author_alert, author_alert_reason
     */
    public function delete(int $updateId, bool $hard = false, ?string $reason = null, array $options = []): bool
    {
        return $this->apiDelete('resource-updates/' . $updateId . '/', array_filter([
            'hard_delete' => $hard ? '1' : null,
            'reason' => $reason,
        ] + $options, static fn ($value): bool => $value !== null))->isSuccess();
    }
}
