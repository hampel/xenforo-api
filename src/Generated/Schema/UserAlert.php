<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The UserAlert entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class UserAlert
{
    public readonly ?int $alert_id;

    public readonly ?int $alerted_user_id;

    public readonly ?int $user_id;

    public readonly ?string $username;

    public readonly ?string $content_type;

    public readonly ?int $content_id;

    public readonly ?string $action;

    public readonly ?int $event_date;

    public readonly ?int $view_date;

    public readonly ?int $read_date;

    public readonly ?bool $auto_read;

    public readonly ?string $alert_text;

    public readonly ?string $alert_url;

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

        $this->alert_id = Cast::int($data['alert_id'] ?? null);
        $this->alerted_user_id = Cast::int($data['alerted_user_id'] ?? null);
        $this->user_id = Cast::int($data['user_id'] ?? null);
        $this->username = Cast::string($data['username'] ?? null);
        $this->content_type = Cast::string($data['content_type'] ?? null);
        $this->content_id = Cast::int($data['content_id'] ?? null);
        $this->action = Cast::string($data['action'] ?? null);
        $this->event_date = Cast::int($data['event_date'] ?? null);
        $this->view_date = Cast::int($data['view_date'] ?? null);
        $this->read_date = Cast::int($data['read_date'] ?? null);
        $this->auto_read = Cast::bool($data['auto_read'] ?? null);
        $this->alert_text = Cast::string($data['alert_text'] ?? null);
        $this->alert_url = Cast::string($data['alert_url'] ?? null);
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
