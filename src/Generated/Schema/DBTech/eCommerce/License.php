<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema\DBTech\eCommerce;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The License entity, as the XenForo API returns it.
 *
 * Generated from the DragonByte eCommerce API specification (version 5.4.2) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class License implements \JsonSerializable
{
    /** Only where the endpoint includes the product */
    public readonly ?Product $Product;

    /**
     * License field values, keyed by field ID
     *
     * @var array<string, mixed>
     */
    public readonly array $license_fields;

    public readonly ?int $license_id;

    public readonly ?int $product_id;

    public readonly ?int $user_id;

    public readonly ?int $purchase_date;

    public readonly ?int $transfer_count;

    public readonly ?int $last_transfer_date;

    /** 0 for a lifetime license */
    public readonly ?int $expiry_date;

    public readonly ?int $latest_download_id;

    public readonly ?string $license_key;

    /** visible, awaiting_payment, moderated or deleted */
    public readonly ?string $license_state;

    /** @var array<mixed> */
    public readonly array $required_user_group_ids;

    public readonly ?string $username;

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
        $this->license_fields = Cast::array($data['license_fields'] ?? null);
        $this->license_id = Cast::int($data['license_id'] ?? null);
        $this->product_id = Cast::int($data['product_id'] ?? null);
        $this->user_id = Cast::int($data['user_id'] ?? null);
        $this->purchase_date = Cast::int($data['purchase_date'] ?? null);
        $this->transfer_count = Cast::int($data['transfer_count'] ?? null);
        $this->last_transfer_date = Cast::int($data['last_transfer_date'] ?? null);
        $this->expiry_date = Cast::int($data['expiry_date'] ?? null);
        $this->latest_download_id = Cast::int($data['latest_download_id'] ?? null);
        $this->license_key = Cast::string($data['license_key'] ?? null);
        $this->license_state = Cast::string($data['license_state'] ?? null);
        $this->required_user_group_ids = Cast::array($data['required_user_group_ids'] ?? null);
        $this->username = Cast::string($data['username'] ?? null);
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
