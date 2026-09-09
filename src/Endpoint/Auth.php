<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Exception\ClientException;
use Hampel\XenForo\Api\Generated\Schema\User;
use Hampel\XenForo\Api\Support\Cast;

/**
 * Auth - validating a member's credentials, and handing them a session.
 *
 * Every endpoint here is super-user only, and for good reason: this is the API that turns a
 * username and password into a user record. It exists so an external application can sign
 * somebody in against the forum's user database, not so a client can log itself in - the
 * client's own credential is the API key or OAuth token it was constructed with.
 *
 * SECURITY NOTE. authenticate() sends a plaintext password in a form-encoded body. That is
 * the only way this endpoint works, and it is exactly as safe as the transport underneath
 * it: over HTTPS to a forum you control, fine; over plain HTTP, a password on the wire.
 */
final class Auth extends Endpoint
{
    /**
     * Validate a login and password. Returns null when they do not match, which is the
     * ordinary answer rather than an exception.
     *
     * @param  bool  $limitIp  count this attempt against the login rate limit, as the
     *                         forum's own login form does. Worth leaving on: without it an
     *                         integration becomes an unthrottled way to guess passwords.
     */
    public function authenticate(string $login, string $password, bool $limitIp = true): ?User
    {
        try {
            $response = $this->apiPost('auth/', [
                'login' => $login,
                'password' => $password,
                'limit_ip' => $limitIp,
            ]);
        } catch (ClientException $e) {
            // XenForo answers a rejected login with a 400, not a 401 - the credential that
            // failed is the member's, not the client's.
            //
            // The status test is the whole point of this clause. A 403 here means the key
            // is not allowed to use this endpoint at all, and swallowing that as "wrong
            // password" would make a misconfigured integration indistinguishable from every
            // user mistyping their password. Since this method always sends both fields, a
            // 400 can only be the login itself.
            if ($e->statusCode !== 400) {
                throw $e;
            }

            return null;
        }

        $user = $response->array('user');

        return $user === [] ? null : User::fromArray($user);
    }

    /**
     * Identify the user behind an existing forum session cookie.
     */
    public function fromSession(?string $sessionId = null, ?string $rememberCookie = null): ?User
    {
        $response = $this->apiPost('auth/from-session', [
            'session_id' => $sessionId,
            'remember_cookie' => $rememberCookie,
        ]);

        $user = $response->array('user');

        return $user === [] ? null : User::fromArray($user);
    }

    /**
     * A one-shot URL that signs a user in when they follow it.
     *
     * The way to hand somebody a logged-in forum session from an external application
     * without ever holding their password. Short-lived - the response carries its own
     * expiry_date - so it is generated at the moment of the redirect, not stored.
     *
     * @param  array<string, mixed>  $options  limit_ip, return_url, force, remember
     * @return array{login_token: string, login_url: string, expiry_date: int}
     */
    public function loginToken(int $userId, array $options = []): array
    {
        $response = $this->apiPost('auth/login-token', ['user_id' => $userId] + $options);

        return [
            'login_token' => Cast::string($response->value('login_token')) ?? '',
            'login_url' => Cast::string($response->value('login_url')) ?? '',
            'expiry_date' => Cast::int($response->value('expiry_date')) ?? 0,
        ];
    }
}
