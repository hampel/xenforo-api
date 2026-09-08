<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests\Fixture;

use Hampel\XenForo\Api\Endpoint\Endpoint;
use Hampel\XenForo\Api\Generated\Schema\User;

/**
 * An endpoint class for a route this package knows nothing about.
 *
 * Modelled on the real Hampel/UserFindCriteria add-on, which extends
 * \XF\Api\Controller\UsersController with an actionGetFindCriteria() and so adds
 * `GET users/find-criteria` to any forum it is installed on. That endpoint is in no
 * specification, and a client that could only reach it by being rewritten would be the
 * wrong shape for XenForo - which is the point this fixture exists to prove.
 *
 * Note what it gets for free: apiFindResponse()'s 404 handling, and - had the endpoint been
 * paginated - apiPaginate() and apiEach(), because every list endpoint in XenForo returns
 * the same pagination block whoever wrote it.
 */
final class UserFindCriteria extends Endpoint
{
    public function byEmail(string $email): ?User
    {
        return $this->find(['email' => $email])['user'] ?? null;
    }

    public function byUsername(string $username): ?User
    {
        return $this->find(['username' => $username])['user'] ?? null;
    }

    /**
     * The whole answer, in one request.
     *
     * The add-on returns a `urls` block beside the user - api, public and admin links to
     * the profile - which nothing in the core API does. apiFind() would keep the user and
     * drop the URLs, so this uses apiFindResponse() and maps the envelope itself.
     *
     * @param  array<string, scalar|array<mixed>|null>  $criteria
     * @return array{user: User, urls: array<string, string>}|null
     */
    public function find(array $criteria): ?array
    {
        $response = $this->apiFindResponse('users/find-criteria', $criteria);

        if ($response === null || !$response->has('user')) {
            return null;
        }

        $urls = [];

        foreach ($response->array('urls') as $type => $url) {
            if (is_string($type) && is_string($url)) {
                $urls[$type] = $url;
            }
        }

        return ['user' => User::fromArray($response->array('user')), 'urls' => $urls];
    }
}
