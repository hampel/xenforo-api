<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Psr\Http\Client\ClientExceptionInterface;

/**
 * What a PSR-18 client throws when the request never got there.
 */
final class TransportFailure extends \RuntimeException implements ClientExceptionInterface
{
}
