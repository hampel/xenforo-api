<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use GuzzleHttp\Psr7\Request;
use Hampel\XenForo\Api\Authentication\ApiKey;
use Hampel\XenForo\Api\Authentication\BearerToken;
use Hampel\XenForo\Api\Authentication\ClientCredentials;
use Hampel\XenForo\Api\Authentication\Guest;
use Hampel\XenForo\Api\Authentication\SuperUserKey;
use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase as BaseTestCase;

final class AuthenticationTest extends BaseTestCase
{
    private function request(): Request
    {
        return new Request('GET', 'https://forum.example.com/api/me/');
    }

    public function test_an_api_key_goes_in_its_own_header(): void
    {
        $applied = (new ApiKey('abc123'))->applyTo($this->request());

        $this->assertSame('abc123', $applied->getHeaderLine('XF-Api-Key'));
    }

    public function test_a_bearer_token_goes_in_the_authorization_header(): void
    {
        $applied = (new BearerToken('tok'))->applyTo($this->request());

        $this->assertSame('Bearer tok', $applied->getHeaderLine('Authorization'));
    }

    public function test_client_credentials_are_basic(): void
    {
        $applied = (new ClientCredentials('id', 'secret'))->applyTo($this->request());

        $this->assertSame('Basic ' . base64_encode('id:secret'), $applied->getHeaderLine('Authorization'));
    }

    public function test_a_guest_sends_nothing(): void
    {
        $applied = (new Guest())->applyTo($this->request());

        $this->assertFalse($applied->hasHeader('XF-Api-Key'));
        $this->assertFalse($applied->hasHeader('Authorization'));
    }

    public function test_acting_as_returns_a_new_credential(): void
    {
        $key = new SuperUserKey('super', actingAs: 1);
        $other = $key->actingAs(2);

        $this->assertSame(1, $key->actingAs);
        $this->assertSame(2, $other->actingAs);
        $this->assertNotSame($key, $other);
    }

    public function test_acting_as_null_means_guest(): void
    {
        $applied = (new SuperUserKey('super'))->applyTo($this->request());

        $this->assertFalse($applied->hasHeader('XF-Api-User'));
    }

    public function test_a_user_to_act_as_must_be_a_real_user_id(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SuperUserKey('super', actingAs: 0);
    }

    public function test_an_empty_credential_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ApiKey('   ');
    }

    /**
     * describe() reaches log lines and exception messages, so it must never carry the
     * credential itself.
     */
    public function test_descriptions_do_not_leak_the_credential(): void
    {
        $descriptions = [
            (new ApiKey('secret-key'))->describe(),
            (new SuperUserKey('secret-key', actingAs: 3))->describe(),
            (new BearerToken('secret-token'))->describe(),
            (new ClientCredentials('public-id', 'secret-value'))->describe(),
            (new Guest())->describe(),
        ];

        foreach ($descriptions as $description) {
            $this->assertStringNotContainsString('secret-key', $description);
            $this->assertStringNotContainsString('secret-token', $description);
            $this->assertStringNotContainsString('secret-value', $description);
        }

        $this->assertStringContainsString('acting as user 3', $descriptions[1]);
    }
}
