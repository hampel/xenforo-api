<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Exception;

/**
 * The request never reached the forum - DNS, TLS, a connect timeout, a proxy refusing it.
 *
 * Distinct from ApiException on purpose: the forum has not seen this request, so retrying
 * it cannot duplicate a post or a user.
 */
final class RequestException extends XenForoException
{
    public static function for(string $method, string $uri, \Throwable $previous): self
    {
        return new self(
            sprintf('Could not reach the XenForo API (%s %s): %s', $method, $uri, $previous->getMessage()),
            0,
            $previous
        );
    }
}
