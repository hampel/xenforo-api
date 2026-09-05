<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Exception;

/**
 * Something failed on this side that is neither the caller's argument nor the forum's
 * answer - a temporary stream that could not be opened while assembling an upload body,
 * say. Environmental rather than programmatic: retrying the same call may well work, and
 * changing the arguments will not.
 */
final class RuntimeException extends XenForoException
{
}
