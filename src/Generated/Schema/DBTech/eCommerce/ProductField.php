<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema\DBTech\eCommerce;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The ProductField entity, as the XenForo API returns it.
 *
 * Generated from the DragonByte eCommerce API specification (version 5.4.2) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class ProductField implements \JsonSerializable
{
    public readonly ?string $field_id;

    public readonly ?string $title;

    public readonly ?string $description;

    public readonly ?int $display_order;

    /** textbox, textarea, bbcode, select, radio, checkbox, multiselect, date, stars or color */
    public readonly ?string $field_type;

    /**
     * Choice fields only: a list of {option, name}
     *
     * @var array<mixed>
     */
    public readonly array $field_choices;

    public readonly ?string $match_type;

    /** @var array<string, mixed> */
    public readonly array $match_params;

    public readonly ?int $max_length;

    public readonly ?bool $required;

    public readonly ?string $display_group;

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

        $this->field_id = Cast::string($data['field_id'] ?? null);
        $this->title = Cast::string($data['title'] ?? null);
        $this->description = Cast::string($data['description'] ?? null);
        $this->display_order = Cast::int($data['display_order'] ?? null);
        $this->field_type = Cast::string($data['field_type'] ?? null);
        $this->field_choices = Cast::array($data['field_choices'] ?? null);
        $this->match_type = Cast::string($data['match_type'] ?? null);
        $this->match_params = Cast::array($data['match_params'] ?? null);
        $this->max_length = Cast::int($data['max_length'] ?? null);
        $this->required = Cast::bool($data['required'] ?? null);
        $this->display_group = Cast::string($data['display_group'] ?? null);
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
