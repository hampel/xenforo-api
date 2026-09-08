<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The Attachment entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class Attachment implements \JsonSerializable
{
    public readonly ?string $filename;

    public readonly ?int $file_size;

    public readonly ?int $height;

    public readonly ?int $width;

    public readonly ?string $thumbnail_url;

    public readonly ?string $retina_thumbnail_url;

    public readonly ?string $direct_url;

    public readonly ?bool $is_video;

    public readonly ?bool $is_audio;

    public readonly ?int $attachment_id;

    public readonly ?string $content_type;

    public readonly ?int $content_id;

    public readonly ?int $attach_date;

    public readonly ?int $view_count;

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

        $this->filename = Cast::string($data['filename'] ?? null);
        $this->file_size = Cast::int($data['file_size'] ?? null);
        $this->height = Cast::int($data['height'] ?? null);
        $this->width = Cast::int($data['width'] ?? null);
        $this->thumbnail_url = Cast::string($data['thumbnail_url'] ?? null);
        $this->retina_thumbnail_url = Cast::string($data['retina_thumbnail_url'] ?? null);
        $this->direct_url = Cast::string($data['direct_url'] ?? null);
        $this->is_video = Cast::bool($data['is_video'] ?? null);
        $this->is_audio = Cast::bool($data['is_audio'] ?? null);
        $this->attachment_id = Cast::int($data['attachment_id'] ?? null);
        $this->content_type = Cast::string($data['content_type'] ?? null);
        $this->content_id = Cast::int($data['content_id'] ?? null);
        $this->attach_date = Cast::int($data['attach_date'] ?? null);
        $this->view_count = Cast::int($data['view_count'] ?? null);
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
