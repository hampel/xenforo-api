<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Result;

/**
 * A successful API response: the decoded body, and the metadata that came with it.
 *
 * Returned rather than a bare array so the version headers are reachable without the
 * client having to remember the last response in a mutable field. Endpoint classes read
 * `->data` and mostly ignore the rest; an integration that cares whether the forum has
 * outgrown it reads `->meta`.
 */
final class ApiResponse implements \JsonSerializable
{
    /**
     * @param  array<mixed>  $data  the decoded JSON body, or [] for a 204
     */
    public function __construct(
        public readonly array $data,
        public readonly int $status,
        public readonly ResponseMeta $meta,
    ) {
    }

    /**
     * Whether the body carried this key at all - which for this API is a different question
     * from whether it carried a value, and the one that matters more.
     *
     * XenForo builds a result with includeColumn(): a field the credential may not see is
     * OMITTED, not sent as null. `email`, `user_state`, `user_group_id` and `is_banned` on a
     * user all behave this way. So an absent key means "you were not allowed to know", which
     * is usually a configuration error in the caller's credential, and a present null means
     * "there is nothing" - and value() with a default cannot tell them apart. This can.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * One top-level key of the body, or the default when it is absent OR null. Where the
     * difference matters - and see has() for why here it often does - ask has() first.
     */
    public function value(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * One top-level key that is expected to be an object or list.
     *
     * @return array<mixed>
     */
    public function array(string $key): array
    {
        $value = $this->data[$key] ?? null;

        return is_array($value) ? $value : [];
    }

    /**
     * XenForo answers a successful write with {"success": true} where there is nothing
     * else to say.
     */
    public function isSuccess(): bool
    {
        return ($this->data['success'] ?? true) !== false;
    }

    /**
     * What json_encode() emits: the decoded body, exactly as the forum sent it. The status
     * and the version headers are metadata about the exchange, not part of the answer.
     *
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
