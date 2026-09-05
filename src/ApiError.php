<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api;

/**
 * One entry from the API's `errors` array.
 *
 * XenForo renders every failure the same way, whatever the status code
 * (\XF\Api\Mvc\Renderer\Api::renderErrors()):
 *
 *     {"errors": [{"code": "...", "message": "...", "params": {...}}]}
 *
 * The code is worth more than the status. XenForo answers 400 for most things a caller got
 * wrong - a missing required input, an invalid page, a validation failure on the entity -
 * so branching on the status tells you very little, while `code` distinguishes
 * `invalid_page` from `api_key_not_found` from a data-writer error exactly.
 *
 * Codes come from phrase names with an `api_error.` prefix stripped, so they are stable
 * identifiers rather than translated text. `unknown_api_error` is the fallback XenForo
 * uses when the error was not phrased at all.
 */
final class ApiError
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function __construct(
        public readonly string $code,
        public readonly string $message,
        public readonly array $params = [],
    ) {
    }

    /**
     * @param  array<mixed>  $error
     */
    public static function fromArray(array $error): self
    {
        $code = isset($error['code']) && is_scalar($error['code'])
            ? (string) $error['code']
            : 'unknown_api_error';

        $message = isset($error['message']) && is_scalar($error['message'])
            ? (string) $error['message']
            : '';

        $params = isset($error['params']) && is_array($error['params']) ? $error['params'] : [];

        /** @var array<string, mixed> $params */
        return new self($code, $message, $params);
    }

    /**
     * Every error in a decoded response body, in order.
     *
     * @param  array<mixed>|null  $decoded
     * @return list<self>
     */
    public static function listFromResponse(?array $decoded): array
    {
        if (!isset($decoded['errors']) || !is_array($decoded['errors'])) {
            return [];
        }

        $errors = [];

        foreach ($decoded['errors'] as $error) {
            if (is_array($error)) {
                $errors[] = self::fromArray($error);
            }
        }

        return $errors;
    }

    public function __toString(): string
    {
        return $this->message === ''
            ? $this->code
            : sprintf('%s (%s)', $this->message, $this->code);
    }
}
