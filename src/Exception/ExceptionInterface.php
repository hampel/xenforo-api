<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Exception;

/**
 * Implemented by every exception this package throws, so a consumer can catch the lot with
 * one clause and let anything else through.
 */
interface ExceptionInterface extends \Throwable
{
}
