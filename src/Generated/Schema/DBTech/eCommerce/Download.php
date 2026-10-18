<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema\DBTech\eCommerce;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The Download entity, as the XenForo API returns it.
 *
 * Generated from the DragonByte eCommerce API specification (version 5.4.2) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class Download implements \JsonSerializable
{
    /** Only where the endpoint includes the product */
    public readonly ?Product $Product;

    /** Only where the endpoint includes the user */
    public readonly ?\Hampel\XenForo\Api\Generated\Schema\User $User;

    /**
     * Only where the endpoint includes the versions
     *
     * @var array<mixed>
     */
    public readonly array $Versions;

    /**
     * Verbose results only, when the change log has attachments
     *
     * @var array<mixed>
     */
    public readonly array $ChangeLogAttachments;

    /**
     * Verbose results only, when the release notes have attachments
     *
     * @var array<mixed>
     */
    public readonly array $ReleaseNotesAttachments;

    /** The sum of change_log_attach_count and release_notes_attach_count */
    public readonly ?int $attach_count;

    /** Registered visitors only */
    public readonly ?bool $is_reacted_to;

    /** Registered visitors only, when they have reacted */
    public readonly ?int $visitor_reaction_id;

    public readonly ?bool $can_edit;

    public readonly ?bool $can_soft_delete;

    public readonly ?bool $can_hard_delete;

    public readonly ?bool $can_react;

    /** True only if one of the visitor's licenses allows it; licenses are only consulted for verbose results */
    public readonly ?bool $can_download;

    public readonly ?int $download_id;

    public readonly ?int $product_id;

    public readonly ?int $user_id;

    /** visible, scheduled, moderated or deleted */
    public readonly ?string $download_state;

    public readonly ?string $version_string;

    public readonly ?int $release_date;

    public readonly ?string $change_log;

    public readonly ?bool $has_new_features;

    public readonly ?bool $has_changed_features;

    public readonly ?bool $has_bug_fixes;

    public readonly ?bool $is_unstable;

    public readonly ?string $release_notes;

    /** dbtech_ecommerce_autogen, dbtech_ecommerce_attachment or dbtech_ecommerce_external */
    public readonly ?string $download_type;

    public readonly ?int $download_count;

    public readonly ?int $full_download_count;

    public readonly ?int $change_log_attach_count;

    public readonly ?int $release_notes_attach_count;

    public readonly ?string $warning_message;

    public readonly ?int $reaction_score;

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
        $this->Versions = Cast::array($data['Versions'] ?? null);
        $this->ChangeLogAttachments = Cast::array($data['ChangeLogAttachments'] ?? null);
        $this->ReleaseNotesAttachments = Cast::array($data['ReleaseNotesAttachments'] ?? null);
        $this->attach_count = Cast::int($data['attach_count'] ?? null);
        $this->is_reacted_to = Cast::bool($data['is_reacted_to'] ?? null);
        $this->visitor_reaction_id = Cast::int($data['visitor_reaction_id'] ?? null);
        $this->can_edit = Cast::bool($data['can_edit'] ?? null);
        $this->can_soft_delete = Cast::bool($data['can_soft_delete'] ?? null);
        $this->can_hard_delete = Cast::bool($data['can_hard_delete'] ?? null);
        $this->can_react = Cast::bool($data['can_react'] ?? null);
        $this->can_download = Cast::bool($data['can_download'] ?? null);
        $this->download_id = Cast::int($data['download_id'] ?? null);
        $this->product_id = Cast::int($data['product_id'] ?? null);
        $this->user_id = Cast::int($data['user_id'] ?? null);
        $this->download_state = Cast::string($data['download_state'] ?? null);
        $this->version_string = Cast::string($data['version_string'] ?? null);
        $this->release_date = Cast::int($data['release_date'] ?? null);
        $this->change_log = Cast::string($data['change_log'] ?? null);
        $this->has_new_features = Cast::bool($data['has_new_features'] ?? null);
        $this->has_changed_features = Cast::bool($data['has_changed_features'] ?? null);
        $this->has_bug_fixes = Cast::bool($data['has_bug_fixes'] ?? null);
        $this->is_unstable = Cast::bool($data['is_unstable'] ?? null);
        $this->release_notes = Cast::string($data['release_notes'] ?? null);
        $this->download_type = Cast::string($data['download_type'] ?? null);
        $this->download_count = Cast::int($data['download_count'] ?? null);
        $this->full_download_count = Cast::int($data['full_download_count'] ?? null);
        $this->change_log_attach_count = Cast::int($data['change_log_attach_count'] ?? null);
        $this->release_notes_attach_count = Cast::int($data['release_notes_attach_count'] ?? null);
        $this->warning_message = Cast::string($data['warning_message'] ?? null);
        $this->reaction_score = Cast::int($data['reaction_score'] ?? null);
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
