<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Resource;

use Hampel\XenForo\Api\Result\AccessToken;
use Hampel\XenForo\Api\Result\TokenIntrospection;
use Hampel\XenForo\Api\Support\Cast;

/**
 * OAuth2 - the `OAuth2` tag in the XenForo API documentation.
 *
 * THESE ENDPOINTS TAKE NO API KEY. \XF\Api\Controller\OAuth2Controller declares
 * allowUnauthenticatedRequest(), and the client identifies itself with `client_id` and
 * `client_secret` in the request itself rather than with a header - so a client built with
 * a Guest credential can run the whole flow, which is just as well, because a process
 * exchanging a code usually has no key of its own.
 *
 * WHERE THE CODE COMES FROM. Not from here. The user is sent to the forum's own
 * `<board url>/oauth2/authorize?...` page in a browser, approves the request there, and is
 * redirected back to the client with a `code` parameter. This class is only the second half
 * - trading that code for a token - and there is nothing in the API that can replace the
 * first half, because it is the user's consent.
 *
 * CONFIDENTIAL AND PUBLIC CLIENTS differ in what they must send. A confidential client
 * holds a `client_secret` and must present it. A public one - a mobile or single-page app,
 * which cannot keep a secret - has none, and proves itself with PKCE instead: the
 * `code_verifier` whose SHA-256 it sent as the `code_challenge` when the flow began. Send
 * the wrong one for your client type and XenForo answers `invalid_client` or a
 * `required_input_missing` naming the field.
 */
final class OAuth2 extends Resource
{
    /**
     * Trade an authorization code for an access token.
     *
     * `$redirectUri` must be the one the flow began with. XenForo checks it against the
     * client's registered URIs and against the request, and answers `invalid_grant` rather
     * than anything more specific when it does not match.
     *
     * @param  string|null  $clientSecret  required for a confidential client
     * @param  string|null  $codeVerifier  required for a public client using PKCE
     */
    public function exchangeCode(
        string $clientId,
        string $code,
        string $redirectUri,
        ?string $clientSecret = null,
        ?string $codeVerifier = null,
    ): AccessToken {
        return AccessToken::fromArray($this->apiPost('oauth2/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'code_verifier' => $codeVerifier,
        ])->data);
    }

    /**
     * Trade a refresh token for a new access token.
     *
     * BOTH TOKENS ARE REPLACED. XenForo issues a new access token AND a new refresh token,
     * and revokes the old access token as it goes - so store both halves of what comes
     * back. Reusing the refresh token you passed in fails with `invalid_grant`, which reads
     * like an expiry and is not one.
     *
     * @param  string|null  $clientSecret  required for a confidential client
     */
    public function refresh(string $clientId, string $refreshToken, ?string $clientSecret = null): AccessToken
    {
        return AccessToken::fromArray($this->apiPost('oauth2/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
        ])->data);
    }

    /**
     * Ask the forum whether a token is live, and what it carries - RFC 7662.
     *
     * An access token or a refresh token; `$tokenTypeHint` only says which to look for
     * first, and XenForo tries the other kind before giving up.
     *
     * A token that is expired, revoked, unknown or issued to a different client comes back
     * as an ordinary 200 with `active` false. That is the RFC's design rather than an
     * oversight, so this raises nothing and the answer is on the result.
     *
     * @param  string|null  $tokenTypeHint  'access_token' or 'refresh_token'
     */
    public function introspect(
        string $clientId,
        string $clientSecret,
        string $token,
        ?string $tokenTypeHint = null,
    ): TokenIntrospection {
        return TokenIntrospection::fromArray($this->apiPost('oauth2/introspect', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'token' => $token,
            'token_type_hint' => $tokenTypeHint,
        ])->data);
    }

    /**
     * Revoke an access token, or a refresh token with the hint.
     *
     * TRUE DOES NOT MEAN SOMETHING WAS REVOKED. XenForo answers success whether or not the
     * token existed - deliberately, so that revocation cannot be used to test whether a
     * token is real - so this is idempotent and tells you nothing about what was there
     * before. Introspect first if you need to know.
     *
     * Revoking a refresh token does not revoke the access token it belongs to, or the other
     * way round: pass each one you want gone.
     *
     * @param  string|null  $tokenTypeHint  'refresh_token' to revoke one; anything else,
     *                                      including null, revokes an access token
     */
    public function revoke(
        string $clientId,
        string $clientSecret,
        string $token,
        ?string $tokenTypeHint = null,
    ): bool {
        return $this->apiPost('oauth2/revoke', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'token' => $token,
            'token_type_hint' => $tokenTypeHint,
        ])->isSuccess();
    }

    /**
     * The older, narrower way of asking about an access token.
     *
     * DEPRECATED BY XENFORO ITSELF in favour of introspect(), and it differs from it in two
     * ways worth knowing before choosing between them. It answers 404 for a token it does
     * not know, where introspection answers `active` false. And its `expires_in` is the
     * REMAINING lifetime, where the same field name on a freshly issued token is the whole
     * lifetime - the same word for two different quantities.
     *
     * It also reads its arguments from the query string rather than a body, being a GET, so
     * the client secret ends up in the URL and in whatever logs that URL.
     *
     * @return array{user_id: int|null, scope: array<mixed>, expires_in: int|null, issue_date: int|null}
     */
    public function tokenInfo(string $clientId, string $clientSecret, string $token): array
    {
        $response = $this->apiGet('oauth2/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'token' => $token,
        ]);

        return [
            'user_id' => Cast::int($response->value('user_id')),
            'scope' => $response->array('scope'),
            'expires_in' => Cast::int($response->value('expires_in')),
            'issue_date' => Cast::int($response->value('issue_date')),
        ];
    }
}
