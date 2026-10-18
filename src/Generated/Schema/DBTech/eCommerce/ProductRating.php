<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema\DBTech\eCommerce;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The ProductRating entity, as the XenForo API returns it.
 *
 * Generated from the DragonByte eCommerce API specification (version 5.4.2) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class ProductRating implements \JsonSerializable
{
    /** Only where the endpoint includes the product */
    public readonly ?Product $Product;

    /** Only when the review is not anonymous */
    public readonly ?\Hampel\XenForo\Api\Generated\Schema\User $User;

    /** Only for an anonymous review the visitor may see the author of */
    public readonly ?\Hampel\XenForo\Api\Generated\Schema\User $AnonymousUser;

    /** Only for an anonymous review the visitor may see the author of */
    public readonly ?int $anonymous_user_id;

    /**
     * Review field values, keyed by field ID
     *
     * @var array<string, mixed>
     */
    public readonly array $custom_fields;

    public readonly ?bool $can_soft_delete;

    public readonly ?bool $can_hard_delete;

    public readonly ?bool $can_author_reply;

    public readonly ?int $product_rating_id;

    public readonly ?int $product_id;

    /** Only when the review is not anonymous */
    public readonly ?int $user_id;

    /** 1 to 5 */
    public readonly ?int $rating;

    public readonly ?int $rating_date;

    public readonly ?string $message;

    public readonly ?string $version_string;

    public readonly ?string $author_response;

    /** visible or deleted */
    public readonly ?string $rating_state;

    public readonly ?bool $is_anonymous;

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

        $this->Product = is_array($data['Product'] ?? null) ? Product::fromArray($data['Product']) : null;
        $this->User = is_array($data['User'] ?? null) ? \Hampel\XenForo\Api\Generated\Schema\User::fromArray($data['User']) : null;
        $this->AnonymousUser = is_array($data['AnonymousUser'] ?? null) ? \Hampel\XenForo\Api\Generated\Schema\User::fromArray($data['AnonymousUser']) : null;
        $this->anonymous_user_id = Cast::int($data['anonymous_user_id'] ?? null);
        $this->custom_fields = Cast::array($data['custom_fields'] ?? null);
        $this->can_soft_delete = Cast::bool($data['can_soft_delete'] ?? null);
        $this->can_hard_delete = Cast::bool($data['can_hard_delete'] ?? null);
        $this->can_author_reply = Cast::bool($data['can_author_reply'] ?? null);
        $this->product_rating_id = Cast::int($data['product_rating_id'] ?? null);
        $this->product_id = Cast::int($data['product_id'] ?? null);
        $this->user_id = Cast::int($data['user_id'] ?? null);
        $this->rating = Cast::int($data['rating'] ?? null);
        $this->rating_date = Cast::int($data['rating_date'] ?? null);
        $this->message = Cast::string($data['message'] ?? null);
        $this->version_string = Cast::string($data['version_string'] ?? null);
        $this->author_response = Cast::string($data['author_response'] ?? null);
        $this->rating_state = Cast::string($data['rating_state'] ?? null);
        $this->is_anonymous = Cast::bool($data['is_anonymous'] ?? null);
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * What json_encode() emits: the payload as it arrived, and nothing else.
     *
     * Not the typed fields. Every field here is nullable, so serialising them
     * would render a field the credential was not allowed to see as null - and
     * XenForo omits those rather than blanking them, a distinction this package
     * keeps everywhere else. It would also drop any field an add-on added, which
     * $raw exists to keep. $raw round-trips through fromArray(); the typed set
     * does not.
     *
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->raw;
    }
}
