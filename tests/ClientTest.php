<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Authentication\ApiKey;
use Hampel\XenForo\Api\Authentication\SuperUserKey;
use Hampel\XenForo\Api\Exception\InvalidArgumentException;

final class ClientTest extends TestCase
{
    public function test_resources_are_memoised(): void
    {
        $xf = $this->xenforo();

        $this->assertSame($xf->users(), $xf->users());
        $this->assertSame($xf->threads(), $xf->threads());
        $this->assertNotSame($xf->users(), $this->xenforo()->users());
    }

    public function test_acting_as_returns_a_new_client_leaving_the_original_alone(): void
    {
        $xf = $this->xenforo(new SuperUserKey('super', actingAs: 1));
        $other = $xf->actingAs(2);

        $this->assertNotSame($xf, $other);

        $this->client->pushJson(200, ['me' => ['user_id' => 2]])->pushJson(200, ['me' => ['user_id' => 1]]);

        $other->me()->get();
        $this->assertSame('2', $this->client->lastRequest()->getHeaderLine('XF-Api-User'));

        $xf->me()->get();
        $this->assertSame('1', $this->client->lastRequest()->getHeaderLine('XF-Api-User'));
    }

    /**
     * Only a super-user key can act as somebody else. Failing here rather than at the forum
     * turns a 403 in production into an error at the point the mistake was made.
     */
    public function test_only_a_super_user_key_can_act_as_another_user(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only a super-user key can act as another user');

        $this->xenforo(new ApiKey('plain'))->actingAs(2);
    }

    public function test_it_exposes_what_it_was_configured_with(): void
    {
        $xf = $this->xenforo();

        $this->assertSame('forum.example.com', $xf->config()->host());
        $this->assertSame('API key', $xf->authentication()->describe());
    }
}
