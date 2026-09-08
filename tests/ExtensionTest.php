<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Endpoint\Endpoint;
use Hampel\XenForo\Api\Endpoint\Users;
use Hampel\XenForo\Api\Exception\EndpointNotFoundException;
use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Hampel\XenForo\Api\Exception\NotFoundException;
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
     * XenForo tells a missing record from a missing route, by code: `requested_page_not_found`
     * against `endpoint_not_found`. The first is "no such user" and reads as null; the
     * second is "this forum does not have the add-on", which is a configuration problem
     * and must not wear the costume of an ordinary miss. Measured on 2.3.12.
     */
    public function test_no_such_user_reads_as_null(): void
    {
        $this->client->pushError(404, [['code' => 'requested_page_not_found']]);

        $this->assertNull($this->xenforo()->endpoint(UserFindCriteria::class)->byUsername('nobody'));
    }

    public function test_a_forum_without_the_add_on_raises_rather_than_reading_as_no_result(): void
    {
        $this->client->pushError(404, [['code' => 'endpoint_not_found']]);

        try {
            $this->xenforo()->endpoint(UserFindCriteria::class)->byUsername('anybody');

            $this->fail('A missing route should have raised.');
        } catch (EndpointNotFoundException $e) {
            $this->assertInstanceOf(NotFoundException::class, $e, 'Catching NotFoundException must still see it.');
            $this->assertTrue($e->hasCode('endpoint_not_found'));
        }
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
