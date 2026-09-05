<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Result;

/**
 * A successful API response: the decoded body, and the metadata that came with it.
 *
 * Returned rather than a bare array so the version headers are reachable without the
 * client having to remember the last response in a mutable field. Resource classes read
 * `->data` and mostly ignore the rest; an integration that cares whether the forum has
 * outgrown it reads `->meta`.
 */
final class ApiResponse
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
     * One top-level key of the body.
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
}
