<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The XFMG_Album entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class XFMG_Album
{
    public readonly ?string $username;

    /** If the album belongs to a category. */
    public readonly ?XFMG_Category $Category;

    /**
     * If view privacy is "shared", the user IDs who can view.
     *
     * @var array<mixed>
     */
    public readonly array $view_users;

    /**
     * If add privacy is "shared", the user IDs who can add.
     *
     * @var array<mixed>
     */
    public readonly array $add_users;

    /** If accessing as a user, true if they are watching this album */
    public readonly ?bool $is_watching;

    public readonly ?string $thumbnail_url;

    public readonly ?bool $can_add;

    public readonly ?bool $can_edit;

    public readonly ?bool $can_soft_delete;

    public readonly ?bool $can_hard_delete;

    public readonly ?bool $can_react;

    public readonly ?string $view_url;

    /** True if the viewing user has reacted to this content */
    public readonly ?bool $is_reacted_to;

    /** If the viewer reacted, the ID of the reaction they used */
    public readonly ?int $visitor_reaction_id;

    public readonly ?int $album_id;

    public readonly ?int $category_id;

    public readonly ?string $title;

    public readonly ?string $description;

    public readonly ?int $create_date;

    public readonly ?int $last_update_date;

    public readonly ?string $view_privacy;

    public readonly ?string $add_privacy;

    public readonly ?string $album_state;

    public readonly ?int $user_id;

    public readonly ?int $media_count;

    public readonly ?int $view_count;

    public readonly ?string $warning_message;

    public readonly ?int $last_comment_date;

    public readonly ?int $last_comment_id;

    public readonly ?int $last_comment_user_id;

    public readonly ?string $last_comment_username;

    public readonly ?int $comment_count;

    public readonly ?int $rating_count;

    public readonly ?float $rating_avg;

    public readonly ?float $rating_weighted;

    public readonly ?int $reaction_score;

    public readonly ?User $User;

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

        $this->username = Cast::string($data['username'] ?? null);
        $this->Category = is_array($data['Category'] ?? null) ? XFMG_Category::fromArray($data['Category']) : null;
        $this->view_users = Cast::array($data['view_users'] ?? null);
        $this->add_users = Cast::array($data['add_users'] ?? null);
        $this->is_watching = Cast::bool($data['is_watching'] ?? null);
        $this->thumbnail_url = Cast::string($data['thumbnail_url'] ?? null);
        $this->can_add = Cast::bool($data['can_add'] ?? null);
        $this->can_edit = Cast::bool($data['can_edit'] ?? null);
        $this->can_soft_delete = Cast::bool($data['can_soft_delete'] ?? null);
        $this->can_hard_delete = Cast::bool($data['can_hard_delete'] ?? null);
        $this->can_react = Cast::bool($data['can_react'] ?? null);
        $this->view_url = Cast::string($data['view_url'] ?? null);
        $this->is_reacted_to = Cast::bool($data['is_reacted_to'] ?? null);
        $this->visitor_reaction_id = Cast::int($data['visitor_reaction_id'] ?? null);
        $this->album_id = Cast::int($data['album_id'] ?? null);
        $this->category_id = Cast::int($data['category_id'] ?? null);
        $this->title = Cast::string($data['title'] ?? null);
        $this->description = Cast::string($data['description'] ?? null);
        $this->create_date = Cast::int($data['create_date'] ?? null);
        $this->last_update_date = Cast::int($data['last_update_date'] ?? null);
        $this->view_privacy = Cast::string($data['view_privacy'] ?? null);
        $this->add_privacy = Cast::string($data['add_privacy'] ?? null);
        $this->album_state = Cast::string($data['album_state'] ?? null);
        $this->user_id = Cast::int($data['user_id'] ?? null);
        $this->media_count = Cast::int($data['media_count'] ?? null);
        $this->view_count = Cast::int($data['view_count'] ?? null);
        $this->warning_message = Cast::string($data['warning_message'] ?? null);
        $this->last_comment_date = Cast::int($data['last_comment_date'] ?? null);
        $this->last_comment_id = Cast::int($data['last_comment_id'] ?? null);
        $this->last_comment_user_id = Cast::int($data['last_comment_user_id'] ?? null);
        $this->last_comment_username = Cast::string($data['last_comment_username'] ?? null);
        $this->comment_count = Cast::int($data['comment_count'] ?? null);
        $this->rating_count = Cast::int($data['rating_count'] ?? null);
        $this->rating_avg = Cast::float($data['rating_avg'] ?? null);
        $this->rating_weighted = Cast::float($data['rating_weighted'] ?? null);
        $this->reaction_score = Cast::int($data['reaction_score'] ?? null);
        $this->User = is_array($data['User'] ?? null) ? User::fromArray($data['User']) : null;
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
