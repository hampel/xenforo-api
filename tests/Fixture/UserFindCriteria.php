<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests\Fixture;

use Hampel\XenForo\Api\Generated\Schema\User;
use Hampel\XenForo\Api\Resource\Resource;

/**
 * A resource for an endpoint this package knows nothing about.
 *
 * Modelled on the real Hampel/UserFindCriteria add-on, which extends
 * \XF\Api\Controller\UsersController with an actionGetFindCriteria() and so adds
 * `GET users/find-criteria` to any forum it is installed on. That endpoint is in no
 * specification, and a client that could only reach it by being rewritten would be the
 * wrong shape for XenForo - which is the point this fixture exists to prove.
 *
 * Note what it gets for free: apiFind()'s 404 handling, and - had the endpoint been
 * paginated - apiPaginate() and apiEach(), because every list endpoint in XenForo returns
 * the same pagination block whoever wrote it.
 */
final class UserFindCriteria extends Resource
{
    public function byEmail(string $email): ?User
    {
        return $this->find(['email' => $email]);
    }

    public function byUsername(string $username): ?User
    {
        return $this->find(['username' => $username]);
    }

    /**
     * @param  array<string, scalar|array<mixed>|null>  $criteria
     */
    public function find(array $criteria): ?User
    {
        return $this->apiFind('users/find-criteria', $criteria, 'user', User::fromArray(...));
    }

    /**
     * The add-on returns a `urls` block alongside the user - api, public and admin links to
     * the profile - which nothing in the core API does.
     *
     * @return array<string, string>
     */
    public function urlsFor(string $email): array
    {
        $urls = [];

        foreach ($this->apiGet('users/find-criteria', ['email' => $email])->array('urls') as $type => $url) {
            if (is_string($type) && is_string($url)) {
                $urls[$type] = $url;
            }
        }

        return $urls;
    }
}
