<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Endpoint\Endpoint;
use Hampel\XenForo\Api\Endpoint\Users;
use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Hampel\XenForo\Api\Tests\Fixture\UserFindCriteria;

/**
 * The extension point, exercised the way a consumer would use it.
 */
final class ExtensionTest extends TestCase
{
    public function test_a_third_party_resource_is_constructed_by_naming_its_class(): void
    {
        $this->client->pushJson(200, ['user' => ['user_id' => 4264, 'username' => 'sim']]);

        $user = $this->xenforo()->endpoint(UserFindCriteria::class)->byEmail('sim@example.com');

        $this->assertNotNull($user);
        $this->assertSame(4264, $user->user_id);
        $this->assertSame(
            'https://forum.example.com/api/users/find-criteria?email=sim%40example.com',
            $this->sentUri()
        );
    }

    public function test_an_extension_resource_is_memoised_like_a_built_in_one(): void
    {
        $xf = $this->xenforo();

        $this->assertSame($xf->endpoint(UserFindCriteria::class), $xf->endpoint(UserFindCriteria::class));
        $this->assertSame($xf->users(), $xf->endpoint(Users::class));
    }

    /**
     * A forum without the add-on installed has no such route, and XenForo answers 404 -
     * the same 404 as "no such user". apiFind() turns both into null, which is the right
     * answer for this endpoint and worth knowing is not distinguishable.
     */
    public function test_a_missing_endpoint_reads_as_no_result(): void
    {
        $this->client->pushError(404, [['code' => 'requested_page_not_found']]);

        $this->assertNull($this->xenforo()->endpoint(UserFindCriteria::class)->byUsername('nobody'));
    }

    public function test_an_extension_can_return_data_no_core_endpoint_produces(): void
    {
        $this->client->pushJson(200, [
            'user' => ['user_id' => 1],
            'urls' => [
                'api' => 'https://forum.example.com/api/users/1/',
                'public' => 'https://forum.example.com/members/sim.1/',
                'admin' => 'https://forum.example.com/admin.php?users/sim.1/edit',
            ],
        ]);

        $found = $this->xenforo()->endpoint(UserFindCriteria::class)->find(['email' => 'sim@example.com']);

        $this->assertNotNull($found);
        $this->assertSame(1, $found['user']->user_id);
        $this->assertSame('https://forum.example.com/members/sim.1/', $found['urls']['public']);
        $this->assertCount(1, $this->client->requests, 'The user and the URLs arrive together; a second request would be a design smell.');
    }

    /**
     * apiFindResponse() keeps apiFind()'s 404 rule - a forum without the add-on, or no
     * such user, both read as null.
     */
    public function test_the_whole_envelope_still_reads_a_404_as_nothing(): void
    {
        $this->client->pushError(404, [['code' => 'requested_page_not_found']]);

        $this->assertNull($this->xenforo()->endpoint(UserFindCriteria::class)->find(['email' => 'nobody@example.com']));
    }

    /**
     * The escape hatch, for an endpoint not worth a class.
     */
    public function test_the_connection_will_call_anything(): void
    {
        $this->client->pushJson(200, ['user' => ['user_id' => 9]]);

        $data = $this->xenforo()->connection()->get('users/find-criteria', ['user_id' => 9])->data;

        $this->assertSame(['user' => ['user_id' => 9]], $data);
    }

    /**
     * The base class satisfies class-string<Endpoint> and is abstract, so this is the one
     * way to reach the guard that static analysis does not already prevent - and it is a
     * mistake somebody will actually make, unlike passing an unrelated class.
     */
    public function test_the_abstract_base_class_cannot_be_constructed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a concrete subclass');

        $this->xenforo()->endpoint(Endpoint::class);
    }
}
