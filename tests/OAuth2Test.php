<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Authentication\Guest;
use Hampel\XenForo\Api\Exception\NotFoundException;

final class OAuth2Test extends TestCase
{
    /**
     * The whole point of the flow: no credential of the client's own, because a process
     * exchanging a code has not got one yet. XenForo's OAuth2Controller declares
     * allowUnauthenticatedRequest(), so the request goes out bare.
     */
    public function test_the_exchange_needs_no_credential_of_its_own(): void
    {
        $this->client->pushJson(200, [
            'access_token' => 'access-1',
            'refresh_token' => 'refresh-1',
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'scope' => 'user:read thread:read',
            'issue_date' => 1_756_000_000,
        ]);

        $xf = $this->xenforo(new Guest());

        $token = $xf->oauth2()->exchangeCode('client-1', 'the-code', 'https://app.example.com/cb', 'secret-1');

        $request = $this->client->lastRequest();

        $this->assertFalse($request->hasHeader('XF-Api-Key'));
        $this->assertFalse($request->hasHeader('Authorization'));
        $this->assertSame('https://forum.example.com/api/oauth2/token', (string) $request->getUri());

        $this->assertSame([
            'grant_type' => 'authorization_code',
            'client_id' => 'client-1',
            'client_secret' => 'secret-1',
            'code' => 'the-code',
            'redirect_uri' => 'https://app.example.com/cb',
        ], $this->sentBody());

        $this->assertSame('access-1', $token->accessToken);
        $this->assertSame('refresh-1', $token->refreshToken);
        $this->assertSame(['user:read', 'thread:read'], $token->scopes());
        $this->assertTrue($token->hasScope('thread:read'));
        $this->assertFalse($token->hasScope('thread:write'));
    }

    /**
     * A public client - a mobile or single-page app, which cannot keep a secret - proves
     * itself with PKCE instead, and sends no client_secret at all rather than an empty one.
     */
    public function test_a_public_client_sends_a_verifier_and_no_secret(): void
    {
        $this->client->pushJson(200, ['access_token' => 'access-1', 'token_type' => 'bearer']);

        $this->xenforo(new Guest())->oauth2()->exchangeCode(
            'client-1',
            'the-code',
            'https://app.example.com/cb',
            codeVerifier: 'the-verifier'
        );

        $body = $this->sentBody();

        $this->assertSame('the-verifier', $body['code_verifier']);
        $this->assertArrayNotHasKey('client_secret', $body);
    }

    /**
     * Expiry is derived from the forum's own issue_date rather than from the local clock,
     * so a process whose time is out does not silently shorten or extend the token.
     */
    public function test_expiry_is_measured_from_the_forums_clock(): void
    {
        $this->client->pushJson(200, [
            'access_token' => 'access-1',
            'expires_in' => 3600,
            'issue_date' => 1_756_000_000,
        ]);

        $token = $this->xenforo()->oauth2()->refresh('client-1', 'refresh-1', 'secret-1');

        $this->assertSame(1_756_003_600, $token->expiresAt());
    }

    public function test_a_token_without_an_expiry_says_null_rather_than_guessing(): void
    {
        $this->client->pushJson(200, ['access_token' => 'access-1']);

        $this->assertNull($this->xenforo()->oauth2()->refresh('client-1', 'refresh-1')->expiresAt());
    }

    /**
     * A refresh replaces BOTH tokens - XenForo issues a new pair and revokes the old access
     * token - so the refresh token that comes back is not the one that went in. A caller
     * storing only the access token works once and then fails with invalid_grant.
     */
    public function test_a_refresh_returns_a_new_refresh_token_as_well(): void
    {
        $this->client->pushJson(200, [
            'access_token' => 'access-2',
            'refresh_token' => 'refresh-2',
        ]);

        $token = $this->xenforo()->oauth2()->refresh('client-1', 'refresh-1', 'secret-1');

        $this->assertSame(['grant_type' => 'refresh_token', 'client_id' => 'client-1', 'client_secret' => 'secret-1', 'refresh_token' => 'refresh-1'], $this->sentBody());
        $this->assertNotSame('refresh-1', $token->refreshToken);
        $this->assertSame('refresh-2', $token->refreshToken);
    }

    /**
     * The end of the flow, and the reason withCredential() exists: the token belongs to a
     * user, and the client that fetched it was authenticated as nobody.
     */
    public function test_the_token_becomes_the_credential_for_a_new_client(): void
    {
        $this->client->pushJson(200, ['access_token' => 'access-1', 'token_type' => 'bearer']);

        $xf = $this->xenforo(new Guest());
        $token = $xf->oauth2()->refresh('client-1', 'refresh-1');

        $authenticated = $xf->withCredential($token->credential());

        $this->assertNotSame($xf, $authenticated);
        $this->assertSame('OAuth2 bearer token', $authenticated->authentication()->describe());

        $this->client->pushJson(200, ['me' => ['user_id' => 3]]);

        $authenticated->me()->get();

        $this->assertSame('Bearer access-1', $this->client->lastRequest()->getHeaderLine('Authorization'));
    }

    /**
     * RFC 7662: an expired, revoked, unknown or foreign token is an ordinary 200 saying
     * `active` false, not an error - so nothing raises and the answer is on the result.
     */
    public function test_a_dead_token_introspects_as_inactive_rather_than_raising(): void
    {
        $this->client->pushJson(200, ['active' => false]);

        $result = $this->xenforo()->oauth2()->introspect('client-1', 'secret-1', 'stale-token');

        $this->assertFalse($result->active);
        $this->assertNull($result->username);
        $this->assertSame([], $result->scopes());
    }

    public function test_a_live_token_introspects_with_the_rfc_names_spelled_out(): void
    {
        $this->client->pushJson(200, [
            'active' => true,
            'scope' => 'user:read user:write',
            'client_id' => 'client-1',
            'username' => 'sim@example.com',
            'token_type' => 'bearer',
            'exp' => 1_756_003_600,
            'iat' => 1_756_000_000,
            'sub' => '3',
            'iss' => 'https://forum.example.com',
        ]);

        $result = $this->xenforo()->oauth2()->introspect('client-1', 'secret-1', 'live-token', 'access_token');

        $this->assertTrue($result->active);
        $this->assertSame(1_756_003_600, $result->expiresAt);
        $this->assertSame(1_756_000_000, $result->issuedAt);
        $this->assertSame(3, $result->userId, 'sub is a string in the response and a user id in fact.');
        $this->assertSame('https://forum.example.com', $result->issuer);
        $this->assertTrue($result->hasScope('user:write'));
        $this->assertFalse($result->isRefreshToken());
        $this->assertSame('https://forum.example.com/api/oauth2/introspect', $this->sentUri());
    }

    public function test_a_refresh_token_introspects_as_one(): void
    {
        $this->client->pushJson(200, ['active' => true, 'token_type' => 'refresh_token']);

        $this->assertTrue(
            $this->xenforo()->oauth2()->introspect('client-1', 'secret-1', 'r', 'refresh_token')->isRefreshToken()
        );
    }

    /**
     * Revocation answers success whether or not the token existed, so that it cannot be
     * used to test whether one is real. True here means "it is gone", not "it was there".
     */
    public function test_revoking_a_token_that_never_existed_still_succeeds(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->assertTrue($this->xenforo()->oauth2()->revoke('client-1', 'secret-1', 'never-issued'));

        $this->assertSame('https://forum.example.com/api/oauth2/revoke', $this->sentUri());
        $this->assertArrayNotHasKey('token_type_hint', $this->sentBody());
    }

    public function test_a_refresh_token_is_revoked_by_hint(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->xenforo()->oauth2()->revoke('client-1', 'secret-1', 'refresh-1', 'refresh_token');

        $this->assertSame('refresh_token', $this->sentBody()['token_type_hint']);
    }

    /**
     * The deprecated GET differs from introspection in both directions: it raises for a
     * token it does not know, and its expires_in is the REMAINING lifetime rather than the
     * whole one. Its arguments also go in the query string, secret included.
     */
    public function test_the_deprecated_lookup_puts_its_secret_in_the_url_and_raises_on_a_miss(): void
    {
        $this->client->pushJson(200, [
            'user_id' => 3,
            'scope' => ['user:read' => true],
            'expires_in' => 1200,
            'issue_date' => 1_756_000_000,
        ]);

        $info = $this->xenforo()->oauth2()->tokenInfo('client-1', 'secret-1', 'live-token');

        $this->assertSame(3, $info['user_id']);
        $this->assertSame(1200, $info['expires_in']);
        $this->assertSame(
            'https://forum.example.com/api/oauth2/token?client_id=client-1&client_secret=secret-1&token=live-token',
            $this->sentUri()
        );

        $this->client->pushError(404, [['code' => 'not_found']]);

        $this->expectException(NotFoundException::class);

        $this->xenforo()->oauth2()->tokenInfo('client-1', 'secret-1', 'unknown');
    }
}
