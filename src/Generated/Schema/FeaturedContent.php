<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The FeaturedContent entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class FeaturedContent
{
    public readonly ?string $content_username;

    public readonly ?string $title;

    public readonly ?string $image;

    public readonly ?string $snippet;

    public readonly ?string $view_url;

    public readonly ?bool $can_view_content;

    /** Present for members with permission to feature or unfeature the content */
    public readonly ?int $feature_user_id;

    /** Present for members with permission to feature or unfeature the content */
    public readonly ?int $unfeature_date;

    /** Present for members with permission to feature or unfeature the content */
    public readonly ?bool $auto_featured;

    /** Present for members with permission to feature or unfeature the content */
    public readonly ?bool $always_visible;

    /** Present for members with permission to feature or unfeature the content */
    public readonly ?int $unfeature_days;

    /** Present for members with permission to feature or unfeature the content */
    public readonly ?bool $is_customized;

    public readonly ?int $featured_content_id;

    public readonly ?string $content_type;

    public readonly ?int $content_id;

    public readonly ?int $content_container_id;

    public readonly ?int $content_user_id;

    public readonly ?int $content_date;

    public readonly ?bool $content_visible;

    public readonly ?int $feature_date;

    public readonly ?int $image_date;

    public readonly ?User $ContentUser;

    /**
     * The response data this entity was built from, exactly as it arrived.
     *
     * @var array<mixed>
     */
    public readonly array $raw;

    /**
     * @param  array<mixed>  $data
     */
    public function __construct(array $data)
    {
        $this->raw = $data;

        $this->content_username = Cast::string($data['content_username'] ?? null);
        $this->title = Cast::string($data['title'] ?? null);
        $this->image = Cast::string($data['image'] ?? null);
        $this->snippet = Cast::string($data['snippet'] ?? null);
        $this->view_url = Cast::string($data['view_url'] ?? null);
        $this->can_view_content = Cast::bool($data['can_view_content'] ?? null);
        $this->feature_user_id = Cast::int($data['feature_user_id'] ?? null);
        $this->unfeature_date = Cast::int($data['unfeature_date'] ?? null);
        $this->auto_featured = Cast::bool($data['auto_featured'] ?? null);
        $this->always_visible = Cast::bool($data['always_visible'] ?? null);
        $this->unfeature_days = Cast::int($data['unfeature_days'] ?? null);
        $this->is_customized = Cast::bool($data['is_customized'] ?? null);
        $this->featured_content_id = Cast::int($data['featured_content_id'] ?? null);
        $this->content_type = Cast::string($data['content_type'] ?? null);
        $this->content_id = Cast::int($data['content_id'] ?? null);
        $this->content_container_id = Cast::int($data['content_container_id'] ?? null);
        $this->content_user_id = Cast::int($data['content_user_id'] ?? null);
        $this->content_date = Cast::int($data['content_date'] ?? null);
        $this->content_visible = Cast::bool($data['content_visible'] ?? null);
        $this->feature_date = Cast::int($data['feature_date'] ?? null);
        $this->image_date = Cast::int($data['image_date'] ?? null);
        $this->ContentUser = is_array($data['ContentUser'] ?? null) ? User::fromArray($data['ContentUser']) : null;
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
