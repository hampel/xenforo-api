<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Result;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The response from `GET /stats/`.
 *
 * Hand-written rather than generated, for the same reason as SiteInfo: the specification
 * describes this response with property names like `totals[threads]` and `online[total]`,
 * which is the shape of the @api-out annotation the docs were compiled from rather than the
 * shape of the JSON. The JSON has three nested objects. Generating from it literally would
 * produce a class whose properties could never be filled.
 */
final class SiteStats implements \JsonSerializable
{
    /**
     * @param  array<mixed>  $raw
     */
    public function __construct(
        public readonly int $threads,
        public readonly int $messages,
        public readonly int $users,
        public readonly int $latestUserId,
        public readonly string $latestUsername,
        public readonly int $latestUserRegisterDate,
        public readonly int $onlineTotal,
        public readonly int $onlineMembers,
        public readonly int $onlineGuests,
        public readonly array $raw = [],
    ) {
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $totals = Cast::array($data['totals'] ?? null);
        $latest = Cast::array($data['latest_user'] ?? null);
        $online = Cast::array($data['online'] ?? null);

        return new self(
            Cast::int($totals['threads'] ?? null) ?? 0,
            Cast::int($totals['messages'] ?? null) ?? 0,
            Cast::int($totals['users'] ?? null) ?? 0,
            Cast::int($latest['user_id'] ?? null) ?? 0,
            Cast::string($latest['username'] ?? null) ?? '',
            Cast::int($latest['register_date'] ?? null) ?? 0,
            Cast::int($online['total'] ?? null) ?? 0,
            Cast::int($online['members'] ?? null) ?? 0,
            Cast::int($online['guests'] ?? null) ?? 0,
            $data,
        );
    }

    /**
     * Whether there is a latest user to speak of.
     *
     * A forum with no registered users still sends the block, filled with 0 and an empty
     * string rather than left out - so `latestUserId` of 0 is the case to test for, and
     * this is the readable way to do it.
     */
    public function hasLatestUser(): bool
    {
        return $this->latestUserId > 0;
    }

    /**
     * Guests are counted from session activity, so this is "seen in the last few minutes"
     * rather than a live connection count, and the online-window option decides how many
     * minutes that is.
     */
    public function onlineGuestsShare(): float
    {
        return $this->onlineTotal > 0 ? $this->onlineGuests / $this->onlineTotal : 0.0;
    }

    /**
     * What json_encode() emits: the payload as it arrived. See the generated entities for
     * why it is the raw payload and not the typed fields.
     *
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->raw;
    }
}
