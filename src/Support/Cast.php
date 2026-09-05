<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Support;

/**
 * Reading a value out of an API result without trusting it.
 *
 * Every one of these returns null rather than throwing when the value is absent or the
 * wrong shape, and that is deliberate. A XenForo API result is not a fixed record: the
 * same entity comes back with different fields at different verbosities
 * (Entity::VERBOSITY_QUIET against VERBOSITY_VERBOSE), fields the acting user cannot see
 * are simply missing, and an add-on can add or remove fields on any entity by extending
 * its toApiResult(). A client that treated an absent field as an error would break on a
 * forum whose only sin was having a different add-on installed.
 */
final class Cast
{
    public static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }

    public static function int(mixed $value): ?int
    {
        // XenForo serialises from MySQL, so an integer column can arrive as "12" as
        // readily as 12 depending on the driver and the field.
        return is_int($value) || (is_string($value) && $value !== '' && ctype_digit(ltrim($value, '-')))
            ? (int) $value
            : (is_float($value) ? (int) $value : null);
    }

    public static function float(mixed $value): ?float
    {
        return is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))
            ? (float) $value
            : null;
    }

    public static function bool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return match ($value) {
            1, '1' => true,
            0, '0' => false,
            default => null,
        };
    }

    /**
     * @return array<mixed>
     */
    public static function array(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * A unix timestamp as a DateTimeImmutable, in UTC.
     *
     * XenForo stores and returns every date as a unix timestamp, so this is the only
     * conversion needed - there is no format to negotiate and no timezone in the value.
     * The generated entities keep the raw integer; this is for a caller that wants a date.
     */
    public static function timestamp(mixed $value): ?\DateTimeImmutable
    {
        $timestamp = self::int($value);

        if ($timestamp === null || $timestamp <= 0) {
            return null;
        }

        return (new \DateTimeImmutable('@' . $timestamp))->setTimezone(new \DateTimeZone('UTC'));
    }
}
