<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Generated\Schema\FeaturedContent;
use Hampel\XenForo\Api\Generated\Schema\XFRM_ResourceItem;
use Hampel\XenForo\Api\Generated\Schema\XFRM_ResourceRating;
use Hampel\XenForo\Api\Generated\Schema\XFRM_ResourceUpdate;
use Hampel\XenForo\Api\Generated\Schema\XFRM_ResourceVersion;
use Hampel\XenForo\Api\Result\Page;
use Hampel\XenForo\Api\Upload;

/**
 * Resources - the `Resources` tag, from XenForo Resource Manager.
 *
 * XFRM IS AN ADD-ON. A forum without it answers 404 for everything here, which looks
 * exactly like a resource that does not exist - `Index::get()->hasScope('resource:read')`
 * tells the two apart before you go looking.
 */
final class Resources extends Endpoint
{
    /**
     * One page of resources.
     *
     * @param  array<string, scalar|array<mixed>|null>  $filters  prefix_id, type
     *         ('local', 'external', 'commercial_external', 'fileless'), creator_id, order,
     *         direction
     * @return Page<XFRM_ResourceItem>
     */
    public function list(int $page = 1, array $filters = []): Page
    {
        return $this->apiPaginate('resources/', 'resources', XFRM_ResourceItem::fromArray(...), $page, $filters);
    }

    /**
     * @param  array<string, scalar|array<mixed>|null>  $filters
     * @return \Generator<int, XFRM_ResourceItem>
     */
    public function each(array $filters = []): \Generator
    {
        yield from $this->apiEach('resources/', 'resources', XFRM_ResourceItem::fromArray(...), $filters);
    }

    public function get(int $resourceId): XFRM_ResourceItem
    {
        return XFRM_ResourceItem::fromArray(
            $this->apiGet('resources/' . $resourceId . '/')->array('resource')
        );
    }

    public function find(int $resourceId): ?XFRM_ResourceItem
    {
        return $this->apiFind('resources/' . $resourceId . '/', [], 'resource', XFRM_ResourceItem::fromArray(...));
    }

    /**
     * Every version of a resource. Not paginated - XFRM returns the lot.
     *
     * @return list<XFRM_ResourceVersion>
     */
    public function versions(int $resourceId): array
    {
        return array_values(array_map(
            XFRM_ResourceVersion::fromArray(...),
            array_filter($this->apiGet('resources/' . $resourceId . '/versions')->array('versions'), 'is_array')
        ));
    }

    /**
     * @return Page<XFRM_ResourceUpdate>
     */
    public function updates(int $resourceId, int $page = 1): Page
    {
        return $this->apiPaginate(
            'resources/' . $resourceId . '/updates',
            'updates',
            XFRM_ResourceUpdate::fromArray(...),
            $page
        );
    }

    /**
     * @return \Generator<int, XFRM_ResourceUpdate>
     */
    public function eachUpdate(int $resourceId): \Generator
    {
        yield from $this->apiEach(
            'resources/' . $resourceId . '/updates',
            'updates',
            XFRM_ResourceUpdate::fromArray(...)
        );
    }

    /**
     * @return Page<XFRM_ResourceRating>
     */
    public function reviews(int $resourceId, int $page = 1): Page
    {
        return $this->apiPaginate(
            'resources/' . $resourceId . '/reviews',
            'reviews',
            XFRM_ResourceRating::fromArray(...),
            $page
        );
    }

    /**
     * @return \Generator<int, XFRM_ResourceRating>
     */
    public function eachReview(int $resourceId): \Generator
    {
        yield from $this->apiEach(
            'resources/' . $resourceId . '/reviews',
            'reviews',
            XFRM_ResourceRating::fromArray(...)
        );
    }

    /**
     * Create a resource.
     *
     * `resource_category_id`, `title`, `tag_line`, `description` and `resource_type` are
     * all required. The type decides which of the rest apply: a `local` resource wants a
     * `version_attachment_key` from Attachments, an `external` one an
     * `external_download_url`, a `commercial_external` one that plus `currency` and
     * `external_purchase_url`, and a `fileless` one none of them. The category also has to
     * allow the type, or the create is refused - see ResourceCategories.
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): XFRM_ResourceItem
    {
        return XFRM_ResourceItem::fromArray($this->apiPost('resources/', $payload)->array('resource'));
    }

    /**
     * @param  array<string, mixed>  $payload  prefix_id, title, tag_line, description,
     *         version_string, custom_fields[...], add_tags, remove_tags, external_url,
     *         alt_support_url, attachment_key, author_alert, author_alert_reason
     */
    public function update(int $resourceId, array $payload): XFRM_ResourceItem
    {
        return XFRM_ResourceItem::fromArray(
            $this->apiPost('resources/' . $resourceId . '/', $payload)->array('resource')
        );
    }

    /**
     * @param  array<string, scalar|null>  $options  author_alert, author_alert_reason
     */
    public function delete(int $resourceId, bool $hard = false, ?string $reason = null, array $options = []): bool
    {
        return $this->apiDelete('resources/' . $resourceId . '/', array_filter([
            'hard_delete' => $hard ? '1' : null,
            'reason' => $reason,
        ] + $options, static fn ($value): bool => $value !== null))->isSuccess();
    }

    /**
     * Feature a resource, optionally with an image. Same shape as Threads::feature().
     *
     * @param  array<string, mixed>  $options  title, snippet, date, unfeature_days,
     *         always_visible
     */
    public function feature(int $resourceId, array $options = [], ?Upload $image = null): FeaturedContent
    {
        return FeaturedContent::fromArray(
            $this->apiUpload(
                'resources/' . $resourceId . '/feature',
                $options,
                $image === null ? [] : ['image' => $image]
            )->array('feature')
        );
    }

    public function unfeature(int $resourceId): bool
    {
        return $this->apiPost('resources/' . $resourceId . '/unfeature')->isSuccess();
    }
}
