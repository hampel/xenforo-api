<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Exception;

/**
 * Thrown before anything is sent: a malformed base URI, a missing credential, a payload
 * that will not encode. Nothing has reached the forum.
 */
final class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}
