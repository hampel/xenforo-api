<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Authentication\SuperUserKey;
use Hampel\XenForo\Api\Exception\NotPermittedException;

final class AuthTest extends TestCase
{
    public function test_a_valid_login_returns_the_user(): void
    {
        $this->client->pushJson(200, ['user' => ['user_id' => 4264, 'username' => 'sim']]);

        $user = $this->xenforo(new SuperUserKey('super'))->auth()->authenticate('sim', 'correct horse');

        $this->assertNotNull($user);
        $this->assertSame(4264, $user->user_id);
        $this->assertSame(['login' => 'sim', 'password' => 'correct horse', 'limit_ip' => '1'], $this->sentBody());
    }

    /**
     * A wrong password is an ordinary outcome, not an exception - the caller asked a
     * question and got "no".
     */
    public function test_a_bad_login_is_null_rather_than_an_exception(): void
    {
        $this->client->pushError(400, [['code' => 'incorrect_password', 'message' => 'Incorrect password']]);

        $this->assertNull($this->xenforo(new SuperUserKey('super'))->auth()->authenticate('sim', 'wrong'));
    }

    /**
     * A key that may not use this endpoint at all is a different problem from a wrong
     * password, and must not read as one - otherwise a misconfigured integration looks
     * exactly like every user typing their password wrongly.
     */
    public function test_a_key_without_the_standing_still_raises(): void
    {
        $this->client->pushError(403, [['code' => 'api_key_not_super_user']]);

        $this->expectException(NotPermittedException::class);

        $this->xenforo()->auth()->authenticate('sim', 'correct horse');
    }

    public function test_a_login_token_comes_back_whole(): void
    {
        $this->client->pushJson(200, [
            'login_token' => 'abc',
            'login_url' => 'https://forum.example.com/login/login-token?token=abc',
            'expiry_date' => 1757030400,
        ]);

        $token = $this->xenforo(new SuperUserKey('super'))->auth()->loginToken(42, ['return_url' => '/threads/1/']);

        $this->assertSame('abc', $token['login_token']);
        $this->assertSame(1757030400, $token['expiry_date']);
        $this->assertSame(['user_id' => '42', 'return_url' => '/threads/1/'], $this->sentBody());
    }
}
