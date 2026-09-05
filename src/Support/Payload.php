<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Support;

use Hampel\XenForo\Api\Config;
use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Hampel\XenForo\Api\Upload;

/**
 * Turning a PHP array into the input XenForo will read back out.
 *
 * This lives apart from Connection because there are two body encodings and only one set
 * of naming rules. A form-encoded body and a multipart one must name their fields
 * identically - `custom_fields[location]`, `context[post_id]`, `node_ids[]` - because on
 * the far side both arrive in $_POST and are read by the same filter() call, which knows
 * nothing about how they were transmitted. Writing that twice is how the two drift.
 */
final class Payload
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function encode(array $payload): string
    {
        return Config::buildQuery(self::normalise($payload));
    }

    /**
     * The same fields a form-encoded body would carry, as name/value pairs ready to become
     * multipart parts.
     *
     * Produced by encoding and then decoding rather than by walking the array again, so
     * the bracket notation cannot disagree with encode()'s: `parse_str()` on the far end
     * has one idea of what `context[post_id]` means, and this way there is one idea of it
     * here too. rawurldecode() is the exact inverse of PHP_QUERY_RFC3986.
     *
     * @param  array<string, mixed>  $payload
     * @return list<array{string, string}>
     */
    public static function flatten(array $payload): array
    {
        $encoded = self::encode($payload);

        if ($encoded === '') {
            return [];
        }

        $pairs = [];

        foreach (explode('&', $encoded) as $pair) {
            [$name, $value] = array_pad(explode('=', $pair, 2), 2, '');

            $pairs[] = [rawurldecode($name), rawurldecode($value)];
        }

        return $pairs;
    }

    /**
     * XenForo reads input with parse_str(), so a nested payload is PHP's bracket notation
     * and booleans have to be the 1/0 that its `bool` filter understands - http_build_query
     * would otherwise drop `false` to an empty string, which XenForo reads as false too,
     * but only by accident.
     *
     * @param  array<mixed>  $payload
     * @return array<mixed>
     */
    public static function normalise(array $payload): array
    {
        $normalised = [];

        foreach ($payload as $key => $value) {
            if ($value === null) {
                // A key with no value is not the same as an absent key: XenForo's filters
                // coerce an empty string to 0/''/false, which for a nullable field means
                // "set it to nothing" rather than "leave it alone". Dropping nulls makes
                // an unset optional argument mean what a caller expects.
                continue;
            }

            if (is_bool($value)) {
                $normalised[$key] = $value ? '1' : '0';
            } elseif (is_array($value)) {
                /** @var array<mixed> $value */
                $normalised[$key] = self::normalise($value);
            } elseif (is_scalar($value)) {
                $normalised[$key] = $value;
            } elseif ($value instanceof Upload) {
                throw new InvalidArgumentException(sprintf(
                    'Cannot send a file as the value of "%s" in a form-encoded body: a file goes in the $files '
                        . 'argument of Connection::postMultipart() or Endpoint::apiUpload().',
                    (string) $key
                ));
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Cannot send %s as the value of "%s": the XenForo API takes form-encoded scalars and arrays of them.',
                    get_debug_type($value),
                    (string) $key
                ));
            }
        }

        return $normalised;
    }

    /**
     * A part name or a filename is written into a header, so it cannot contain the
     * characters that would end the header early.
     *
     * Rejected rather than stripped, because both silent alternatives are worse: escaping
     * a quote leaves it to the far end's parser to agree with ours about the escape, and
     * quietly renaming a file means an upload that succeeded under a name the caller never
     * chose. These characters are vanishingly rare in a real filename and always a mistake
     * in a field name.
     */
    public static function assertHeaderSafe(string $value, string $what): string
    {
        if ($value === '') {
            throw new InvalidArgumentException(sprintf('A multipart %s cannot be empty.', $what));
        }

        if (preg_match('/["\r\n\0]/', $value) === 1) {
            throw new InvalidArgumentException(sprintf(
                'A multipart %s cannot contain a quote, a line break or a null byte: %s',
                $what,
                var_export($value, true)
            ));
        }

        return $value;
    }
}
