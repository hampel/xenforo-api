<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Authentication\ApiKey;
use Hampel\XenForo\Api\Authentication\SuperUserKey;
use Hampel\XenForo\Api\Config;
use Hampel\XenForo\Api\Connection;
use Hampel\XenForo\Api\Exception\ApiException;
use Hampel\XenForo\Api\Exception\ClientException;
use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Hampel\XenForo\Api\Exception\MalformedResponseException;
use Hampel\XenForo\Api\Exception\NotAuthenticatedException;
use Hampel\XenForo\Api\Exception\NotFoundException;
use Hampel\XenForo\Api\Exception\NotPermittedException;
use Hampel\XenForo\Api\Exception\RequestException;
use Hampel\XenForo\Api\Exception\ServerException;
use Hampel\XenForo\Api\Exception\TooManyRequestsException;
use Hampel\XenForo\Api\Upload;
use PHPUnit\Framework\Attributes\DataProvider;

final class ConnectionTest extends TestCase
{
    public function test_it_sends_the_credential_and_accepts_json(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->connection()->get('index/');

        $request = $this->client->lastRequest();

        $this->assertSame('test-api-key', $request->getHeaderLine('XF-Api-Key'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame('https://forum.example.com/api/index/', (string) $request->getUri());
    }

    /**
     * XenForo parses a PUT, PATCH or DELETE body by hand in
     * \XF\Http\Request::convertCustomMethodPhpInput(), comparing the Content-Type with
     * `===` against this exact string. A `; charset=utf-8` parameter - which several HTTP
     * clients add by default - makes that comparison fail, the body is discarded, and the
     * endpoint answers 200 having seen no input at all.
     *
     * Core XenForo never meets it: it has no PUT or PATCH endpoints and its DELETEs take
     * query parameters. Add-on endpoints can, which is the case this package is for.
     */
    #[DataProvider('writeMethods')]
    public function test_write_bodies_carry_the_exact_content_type_xenforo_compares_against(string $method): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->connection()->{$method}('threads/1/', ['title' => 'Hello']);

        $this->assertSame(
            'application/x-www-form-urlencoded',
            $this->client->lastRequest()->getHeaderLine('Content-Type'),
            'A Content-Type with any parameter on it is silently ignored by XenForo.'
        );
    }

    /**
     * The mirror image of the Content-Type rule above, reached by a different road.
     *
     * PHP populates $_FILES for a POST and for nothing else, and XenForo's own fallback for
     * the other methods - convertCustomMethodPhpInput() - parses one encoding and has no
     * concept of a file. A multipart PUT therefore arrives carrying neither its files nor
     * its fields, and answers 200 having done nothing. Refused here rather than sent,
     * because there is nothing about the response that would tell a caller.
     */
    #[DataProvider('nonPostMethods')]
    public function test_a_multipart_body_is_refused_on_anything_but_a_post(string $method): void
    {
        $connection = $this->connection();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A multipart body can only be sent on a POST');

        $connection->withMultipart(
            $connection->request($method, 'threads/1/'),
            [],
            ['image' => Upload::fromString('x', 'a.png')]
        );
    }

    /**
     * The likeliest way to reach for a file and miss: putting it in the payload, where a
     * form-encoded body cannot carry it. The message says where it does belong.
     */
    public function test_a_file_in_a_form_payload_says_where_it_belongs(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$files argument of Connection::postMultipart()');

        $this->connection()->post('me/avatar', ['avatar' => Upload::fromString('x', 'a.png')]);
    }

    public function test_a_multipart_post_still_carries_the_credential(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->connection()->postMultipart('me/avatar', [], ['avatar' => Upload::fromString('x', 'a.png')]);

        $request = $this->client->lastRequest();

        $this->assertSame('test-api-key', $request->getHeaderLine('XF-Api-Key'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertStringStartsWith('multipart/form-data; boundary="', $request->getHeaderLine('Content-Type'));
    }

    public function test_a_multipart_post_takes_query_parameters_too(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->connection()->postMultipart(
            'me/avatar',
            [],
            ['avatar' => Upload::fromString('x', 'a.png')],
            ['api_bypass_permissions' => 1]
        );

        $this->assertSame(
            'https://forum.example.com/api/me/avatar?api_bypass_permissions=1',
            $this->sentUri()
        );
    }

    /**
     * send() reads the whole body to decode it, which is the wrong thing to do to a 40MB
     * attachment. getRaw() hands the response over with its stream untouched.
     */
    public function test_a_raw_get_does_not_read_or_decode_the_body(): void
    {
        $this->client->pushRaw(200, 'PNGDATA', ['Content-Type' => 'image/png']);

        $response = $this->connection()->getRaw('attachments/7/data');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, $response->getBody()->tell(), 'Nothing should have read the body yet.');
        $this->assertSame('PNGDATA', (string) $response->getBody());
    }

    /**
     * A 301 IS the output of the thumbnail endpoints - the URL is in the Location header -
     * so sendRaw() has to hand a redirect back where send() throws on one.
     */
    public function test_a_redirect_is_an_answer_to_a_raw_get_and_an_error_to_a_decoded_one(): void
    {
        $this->client->pushRaw(301, '', ['Location' => 'https://forum.example.com/data/thumb/7.jpg']);

        $this->assertSame(301, $this->connection()->getRaw('attachments/7/thumbnail')->getStatusCode());

        $this->client->pushRaw(301, '', ['Location' => 'https://forum.example.com/data/thumb/7.jpg']);

        $this->expectException(ApiException::class);

        $this->connection()->get('attachments/7/thumbnail');
    }

    /**
     * The error body on these endpoints is still JSON - it is rendered by the API renderer
     * rather than by the attachment view - so the codes survive the raw path.
     */
    public function test_a_raw_get_still_raises_the_usual_exception_with_its_codes(): void
    {
        $this->client->pushError(403, [['code' => 'do_not_have_permission']]);

        try {
            $this->connection()->getRaw('attachments/7/data');

            $this->fail('A 403 should have raised.');
        } catch (NotPermittedException $e) {
            $this->assertTrue($e->hasCode('do_not_have_permission'));
        }
    }

    public function test_a_transport_failure_on_a_raw_get_is_still_a_transport_failure(): void
    {
        $this->client->push(new TransportFailure('the forum is not answering'));

        $this->expectException(RequestException::class);

        $this->connection()->getRaw('attachments/7/data');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function writeMethods(): iterable
    {
        yield 'post' => ['post'];
        yield 'put' => ['put'];
        yield 'patch' => ['patch'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonPostMethods(): iterable
    {
        yield 'put' => ['PUT'];
        yield 'patch' => ['PATCH'];
        yield 'delete' => ['DELETE'];
        yield 'get' => ['GET'];
    }

    public function test_bodies_are_form_encoded_not_json(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->connection()->post('threads/', ['node_id' => 2, 'title' => 'A & B']);

        $this->assertSame('node_id=2&title=A%20%26%20B', $this->sentBodyRaw());
    }

    /**
     * XenForo reads input with parse_str(), so nested payloads are PHP bracket notation.
     */
    public function test_nested_payloads_use_php_bracket_notation(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->connection()->post('users/', [
            'username' => 'Example',
            'custom_fields' => ['location' => 'Sydney'],
            'secondary_group_ids' => [3, 4],
        ]);

        $this->assertSame([
            'username' => 'Example',
            'custom_fields' => ['location' => 'Sydney'],
            'secondary_group_ids' => ['3', '4'],
        ], $this->sentBody());
    }

    /**
     * http_build_query() drops a false to an empty string, which XenForo's bool filter also
     * reads as false - but only by accident, and an empty string is a legitimate value for
     * a string field. Sending 1/0 makes the intent explicit either way.
     */
    public function test_booleans_are_sent_as_one_and_zero(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->connection()->post('alerts/mark-all', ['read' => true, 'viewed' => false]);

        $this->assertSame('read=1&viewed=0', $this->sentBodyRaw());
    }

    /**
     * A null means "I am not setting this", which is not the same as setting it to nothing:
     * XenForo's filters would coerce an empty string to 0/''/false and write that.
     */
    public function test_nulls_are_omitted_from_the_payload(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->connection()->post('posts/', [
            'thread_id' => 1,
            'message' => 'Hi',
            'attachment_key' => null,
        ]);

        $this->assertSame('thread_id=1&message=Hi', $this->sentBodyRaw());
    }

    public function test_it_rejects_a_payload_it_cannot_form_encode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot send stdClass as the value of "meta"');

        $this->connection()->post('threads/', ['meta' => new \stdClass()]);
    }

    public function test_a_delete_sends_its_arguments_as_query_parameters(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->connection()->delete('threads/9/', [], ['hard_delete' => '1', 'reason' => 'spam']);

        $this->assertSame(
            'https://forum.example.com/api/threads/9/?hard_delete=1&reason=spam',
            $this->sentUri()
        );
        $this->assertSame('', $this->sentBodyRaw());
    }

    public function test_it_returns_the_decoded_body(): void
    {
        $this->client->pushJson(200, ['thread' => ['thread_id' => 7, 'title' => 'Hello']]);

        $response = $this->connection()->get('threads/7/');

        $this->assertSame(200, $response->status);
        $this->assertSame(['thread_id' => 7, 'title' => 'Hello'], $response->array('thread'));
    }

    public function test_it_reads_the_version_headers(): void
    {
        $this->client->pushJson(200, [], [
            'XF-Used-Api-Version' => '1',
            'XF-Latest-Api-Version' => '2',
            'XF-Request-User' => '42',
            'XF-Request-User-Extras' => 'super_user, bypass_permissions',
        ]);

        $meta = $this->connection()->get('index/')->meta;

        $this->assertSame(1, $meta->usedVersion);
        $this->assertSame(2, $meta->latestVersion);
        $this->assertSame(42, $meta->requestUser);
        $this->assertSame(['super_user', 'bypass_permissions'], $meta->requestUserExtras);
        $this->assertTrue($meta->isOutdated());
    }

    public function test_it_notes_when_the_forum_has_a_newer_api_version(): void
    {
        $logger = new RecordingLogger();

        $this->client->pushJson(200, [], [
            'XF-Used-Api-Version' => '1',
            'XF-Latest-Api-Version' => '3',
        ]);

        $this->connection(logger: $logger)->get('index/');

        $this->assertTrue($logger->hasMessageContaining('behind the forum'));
    }

    public function test_a_transport_failure_is_not_an_api_error(): void
    {
        $this->client->push(new TransportFailure('Connection refused'));

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Could not reach the XenForo API');

        $this->connection()->get('index/');
    }

    /**
     * @param  class-string<\Throwable>  $expected
     */
    #[DataProvider('errorStatuses')]
    public function test_it_maps_a_status_to_an_exception(int $status, string $expected): void
    {
        $this->client->pushError($status, [['code' => 'some_error', 'message' => 'It went wrong']]);

        $this->expectException($expected);

        $this->connection()->get('threads/1/');
    }

    /**
     * @return iterable<string, array{int, class-string<\Throwable>}>
     */
    public static function errorStatuses(): iterable
    {
        yield '400' => [400, ClientException::class];
        yield '401' => [401, NotAuthenticatedException::class];
        yield '403' => [403, NotPermittedException::class];
        yield '404' => [404, NotFoundException::class];
        yield '500' => [500, ServerException::class];
        yield '503' => [503, ServerException::class];
    }

    public function test_an_error_carries_the_codes_xenforo_reported(): void
    {
        $this->client->pushError(400, [
            ['code' => 'invalid_page', 'message' => 'The requested page could not be found.'],
        ]);

        try {
            $this->connection()->get('users/');
            $this->fail('Expected a ClientException.');
        } catch (ClientException $e) {
            $this->assertTrue($e->hasCode('invalid_page'));
            $this->assertFalse($e->hasCode('api_key_not_found'));
            $this->assertSame(['invalid_page'], $e->codes());
            $this->assertStringContainsString('The requested page could not be found. (invalid_page)', $e->getMessage());
        }
    }

    /**
     * A maintenance page, a WAF block, or a base URI whose /api suffix reaches the forum's
     * front end instead of its API. None of those produce a XenForo error body.
     */
    public function test_a_non_json_error_body_is_summarised_not_swallowed(): void
    {
        $this->client->pushRaw(502, '<html><body>' . str_repeat('Bad gateway. ', 40) . '</body></html>');

        try {
            $this->connection()->get('index/');
            $this->fail('Expected a ServerException.');
        } catch (ServerException $e) {
            $this->assertSame([], $e->errors());
            $this->assertStringContainsString('Bad gateway.', $e->getMessage());
            $this->assertLessThan(400, strlen($e->getMessage()));
        }
    }

    /**
     * The forum is not the only thing that answers on its URL. A maintenance page, a WAF
     * challenge and a CDN interstitial are all a 200 with HTML in it, and a truncated
     * response is a 200 with nothing. Decoded as an empty body every one of them would
     * reach the caller as "no such record" - the failure shape this package is otherwise
     * built to keep visible, one layer down from where apiFind() guards it.
     *
     * Reported by the package's first consumer, against forums behind Cloudflare.
     */
    #[DataProvider('nonJsonSuccesses')]
    public function test_a_successful_status_that_is_not_json_raises_rather_than_reading_as_nothing(
        string $body,
        string $contentType,
    ): void {
        $this->client->pushRaw(200, $body, $contentType === '' ? [] : ['Content-Type' => $contentType]);

        try {
            $this->connection()->get('users/1/');

            $this->fail('A 200 that is not JSON should have raised.');
        } catch (MalformedResponseException $e) {
            $this->assertInstanceOf(ApiException::class, $e, 'Catching ApiException must be enough to see it.');
            $this->assertSame(200, $e->statusCode);
            $this->assertSame($body, $e->body);
            $this->assertSame([], $e->errors(), 'There was no JSON to carry codes in.');
            $this->assertStringContainsString('HTTP 200 but not with JSON', $e->getMessage());
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function nonJsonSuccesses(): iterable
    {
        yield 'an HTML maintenance page' => ['<html><body>Down for maintenance</body></html>', 'text/html'];
        yield 'an empty body claiming JSON' => ['', 'application/json'];
        yield 'an empty body with no type at all' => ['', ''];
        yield 'a JSON scalar, which is not an object' => ['true', 'application/json'];
        yield 'a JSON string' => ['"unavailable"', 'application/json'];
        yield 'truncated JSON' => ['{"user": {"user_id": 1, "usern', 'application/json'];
    }

    public function test_the_message_names_the_content_type_and_a_cut_of_the_body(): void
    {
        $this->client->pushRaw(200, str_repeat('<div>challenge</div>', 50), ['Content-Type' => 'text/html; charset=UTF-8']);

        try {
            $this->connection()->get('users/1/');
        } catch (MalformedResponseException $e) {
            $this->assertStringContainsString('(text/html; charset=UTF-8)', $e->getMessage());
            $this->assertStringEndsWith('...', $e->getMessage());
            $this->assertLessThan(400, strlen($e->getMessage()), 'An HTML page must not become the message.');
        }
    }

    /**
     * A rate limit is a 4xx that is nothing like the other 4xxs: the request was right and
     * will succeed later. Its own type, under ClientException so nothing catching that
     * misses it.
     */
    public function test_a_rate_limit_is_its_own_retryable_type(): void
    {
        $this->client->pushRaw(429, 'Too Many Requests', ['Content-Type' => 'text/plain', 'Retry-After' => '30']);

        try {
            $this->connection()->get('users/1/');

            $this->fail('A 429 should have raised.');
        } catch (TooManyRequestsException $e) {
            $this->assertInstanceOf(ClientException::class, $e);
            $this->assertSame(429, $e->statusCode);
        }
    }

    public function test_a_204_with_no_body_is_a_success(): void
    {
        $this->client->pushRaw(204, '');

        $response = $this->connection()->delete('alerts/1/');

        $this->assertSame([], $response->data);
        $this->assertTrue($response->isSuccess());
    }

    public function test_a_super_user_key_can_act_as_a_user_and_bypass_permissions(): void
    {
        $this->client->pushJson(200, []);

        $connection = $this->connection(new SuperUserKey('super-key', actingAs: 7, bypassPermissions: true));
        $connection->get('me/');

        $request = $this->client->lastRequest();

        $this->assertSame('super-key', $request->getHeaderLine('XF-Api-Key'));
        $this->assertSame('7', $request->getHeaderLine('XF-Api-User'));
        $this->assertStringContainsString('api_bypass_permissions=1', (string) $request->getUri());
    }

    public function test_bypass_permissions_survives_an_existing_query_string(): void
    {
        $this->client->pushJson(200, []);

        $this->connection(new SuperUserKey('super-key', bypassPermissions: true))
            ->get('users/', ['page' => 2]);

        $this->assertSame(
            'https://forum.example.com/api/users/?page=2&api_bypass_permissions=1',
            $this->sentUri()
        );
    }

    public function test_an_ordinary_key_sends_neither(): void
    {
        $this->client->pushJson(200, []);

        $this->connection(new ApiKey('plain-key'))->get('me/');

        $request = $this->client->lastRequest();

        $this->assertFalse($request->hasHeader('XF-Api-User'));
        $this->assertStringNotContainsString('api_bypass_permissions', (string) $request->getUri());
    }

    public function test_it_pins_the_api_version_when_the_config_asks_for_one(): void
    {
        $this->client->pushJson(200, []);

        $this->connection(config: new Config('https://forum.example.com', version: 1))->get('threads/5/');

        $this->assertSame('https://forum.example.com/api/v1/threads/5/', $this->sentUri());
    }

    public function test_the_form_content_type_constant_has_no_parameters(): void
    {
        // Guards the constant itself. A well-meaning "; charset=utf-8" here would be
        // invisible on every core endpoint, since those are all POSTs and PHP parses
        // those bodies itself - and would silently discard the body of every add-on
        // endpoint that answers PUT, PATCH or DELETE.
        $this->assertSame('application/x-www-form-urlencoded', Connection::FORM_CONTENT_TYPE);
    }
}
