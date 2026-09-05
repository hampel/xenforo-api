<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Authentication;

use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Psr\Http\Message\RequestInterface;

/**
 * A guest or user API key, presented in the XF-Api-Key header.
 *
 * The key itself decides which user the request acts as - a user key is bound to one user
 * at the point it was created in the admin panel, and a guest key acts as a guest. Neither
 * can act as anyone else; that is what SuperUserKey is for.
 */
class ApiKey implements Authentication
{
    public function __construct(protected readonly string $key)
    {
        if (trim($key) === '') {
            throw new InvalidArgumentException('A XenForo API key is required.');
        }
    }

    public function applyTo(RequestInterface $request): RequestInterface
    {
        return $request->withHeader('XF-Api-Key', $this->key);
    }

    public function describe(): string
    {
        return 'API key';
    }
}
