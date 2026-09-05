<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api;

use Hampel\XenForo\Api\Authentication\Authentication;
use Hampel\XenForo\Api\Exception\ApiException;
use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Hampel\XenForo\Api\Exception\RequestException;
use Hampel\XenForo\Api\Result\ApiResponse;
use Hampel\XenForo\Api\Result\ResponseMeta;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Everything that touches HTTP, in one place.
 *
 * The client is injected as a PSR-18 ClientInterface rather than a concrete one, which is
 * the whole point of the package: a host application with its own HTTP stack - a
 * proxy-aware, SSRF-guarded client that all outbound requests are required to go through -
 * implements sendRequest() over it and shares this code, instead of writing a second API
 * client because ours hardcoded the wrong library.
 *
 * A PSR-18 client does not throw on an HTTP status, only on a transport failure, so the
 * two failure modes stay cleanly separated here.
 *
 * This class is also the extension point of last resort. Any endpoint an add-on has added
 * to a forum can be called through it directly, without waiting for a wrapper:
 *
 *     $xf->connection()->get('users/find-criteria', ['email' => $email])->data;
 */
final class Connection
{
    /**
     * Written here exactly, with no parameters, and that is not fussiness.
     *
     * On a POST it would not matter: PHP populates $_POST itself, and its own content-type
     * lookup ignores parameters, so `; charset=utf-8` is harmless there. On a PUT, PATCH or
     * DELETE it matters entirely. PHP does not parse those bodies at all, so XenForo does it
     * by hand in \XF\Http\Request::convertCustomMethodPhpInput() - and that method compares
     * the header with `===` against this exact string. A parameter on it makes the
     * comparison fail, the body is discarded, and the endpoint sees no input whatsoever.
     * There is no error: the request returns 200 having done nothing.
     *
     * The core API never meets that, because it has no PUT or PATCH endpoints and its
     * DELETEs take query parameters - every write in XenForo's own API is a POST. It is
     * add-on endpoints that meet it, which is to say precisely the endpoints this package
     * exists to be able to reach. An add-on is free to define an actionPut... or to read
     * input from a DELETE body, and a client that let its HTTP library decorate this header
     * would fail against it silently.
     *
     * The same `===` comparison governs ::getPhpInputJson(), which also accepts only POST -
     * so a JSON-bodied client would work for creates and quietly do nothing for updates.
     * This package sends form-encoded bodies throughout and never meets that either.
     */
    public const FORM_CONTENT_TYPE = 'application/x-www-form-urlencoded';

    public function __construct(
        private readonly Config $config,
        private readonly Authentication $authentication,
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function authentication(): Authentication
    {
        return $this->authentication;
    }

    /**
     * The injected transport, exposed rather than hidden because a caller assembling
     * something this class does not cover needs the same factories to do it - a multipart
     * upload, most of all, which has to build its own body but should still go out through
     * the application's own HTTP client.
     */
    public function client(): ClientInterface
    {
        return $this->client;
    }

    public function requestFactory(): RequestFactoryInterface
    {
        return $this->requestFactory;
    }

    public function streamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    /**
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    public function get(string $path, array $query = []): ApiResponse
    {
        return $this->send($this->request('GET', $path, $query));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    public function post(string $path, array $payload = [], array $query = []): ApiResponse
    {
        return $this->send($this->withForm($this->request('POST', $path, $query), $payload));
    }

    /**
     * Core XenForo has no PUT endpoints - every write in its API is a POST - so this exists
     * for an add-on that defines one. See FORM_CONTENT_TYPE for why the body encoding
     * matters more here than it does on a POST.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    public function put(string $path, array $payload = [], array $query = []): ApiResponse
    {
        return $this->send($this->withForm($this->request('PUT', $path, $query), $payload));
    }

    /**
     * As put(): nothing in the core API answers PATCH, and an add-on may.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    public function patch(string $path, array $payload = [], array $query = []): ApiResponse
    {
        return $this->send($this->withForm($this->request('PATCH', $path, $query), $payload));
    }

    /**
     * XenForo's own DELETE endpoints take their arguments as QUERY parameters - hard_delete,
     * reason, rename_to - not as a body, which is why $query is the second argument on the
     * resource-level helper. A body is accepted here for an add-on endpoint that reads one,
     * and that is the case FORM_CONTENT_TYPE is about.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    public function delete(string $path, array $payload = [], array $query = []): ApiResponse
    {
        $request = $this->request('DELETE', $path, $query);

        return $this->send($payload === [] ? $request : $this->withForm($request, $payload));
    }

    /**
     * Build a request without sending it - for a caller assembling something this class
     * does not cover, a multipart upload most of all. The credential and the Accept header
     * are already applied.
     *
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    public function request(string $method, string $path, array $query = []): RequestInterface
    {
        $request = $this->requestFactory
            ->createRequest($method, $this->config->resolve($path, $query))
            ->withHeader('Accept', 'application/json');

        return $this->authentication->applyTo($request);
    }

    /**
     * Attach a form-encoded body. Public because a caller assembling its own request
     * through request() should not have to reimplement the Content-Type rule above.
     *
     * @param  array<string, mixed>  $payload
     */
    public function withForm(RequestInterface $request, array $payload): RequestInterface
    {
        return $request
            ->withHeader('Content-Type', self::FORM_CONTENT_TYPE)
            ->withBody($this->streamFactory->createStream(self::encode($payload)));
    }

    /**
     * Send a request that was built elsewhere, with this connection's error handling.
     */
    public function send(RequestInterface $request): ApiResponse
    {
        $method = $request->getMethod();
        $uri = (string) $request->getUri();

        $this->logger->debug('XenForo API request', ['method' => $method, 'uri' => $uri]);

        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('XenForo API request failed', [
                'method' => $method,
                'uri' => $uri,
                'error' => $e->getMessage(),
            ]);

            throw RequestException::for($method, $uri, $e);
        }

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $decoded = self::decode($body);
        $meta = ResponseMeta::fromResponse($response);

        if ($status >= 200 && $status < 300) {
            if ($meta->isOutdated()) {
                // Not a warning about anything broken - nothing here has failed - but the
                // only notice a client ever gets that the forum has a newer API.
                $this->logger->info('XenForo API version is behind the forum', $meta->toArray());
            }

            return new ApiResponse($decoded ?? [], $status, $meta);
        }

        $this->logger->error('XenForo API error response', [
            'method' => $method,
            'uri' => $uri,
            'status' => $status,
            'body' => $decoded ?? $body,
        ]);

        throw ApiException::fromResponse($method, $uri, $response, $decoded, $body);
    }

    /**
     * XenForo reads input with parse_str(), so a nested payload is PHP's bracket notation
     * and booleans have to be the 1/0 that its `bool` filter understands - http_build_query
     * would otherwise drop `false` to an empty string, which XenForo reads as false too,
     * but only by accident.
     *
     * @param  array<string, mixed>  $payload
     */
    private static function encode(array $payload): string
    {
        return Config::buildQuery(self::normalise($payload));
    }

    /**
     * @param  array<mixed>  $payload
     * @return array<mixed>
     */
    private static function normalise(array $payload): array
    {
        $normalised = [];

        foreach ($payload as $key => $value) {
            if ($value === null) {
                // A key with no value is not the same as an absent key: XenForo's filters
                // coerce an empty string to 0/''/false, which for a nullable field means
                // "set it to nothing" rather than "leave it alone". Dropping nulls makes
                // an unset optional argument mean what a caller expects.
                continue;
            }

            if (is_bool($value)) {
                $normalised[$key] = $value ? '1' : '0';
            } elseif (is_array($value)) {
                /** @var array<mixed> $value */
                $normalised[$key] = self::normalise($value);
            } elseif (is_scalar($value)) {
                $normalised[$key] = $value;
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Cannot send %s as the value of "%s": the XenForo API takes form-encoded scalars and arrays of them.',
                    get_debug_type($value),
                    (string) $key
                ));
            }
        }

        return $normalised;
    }

    /**
     * @return array<mixed>|null  null when the body was not JSON, or was JSON but not an
     *                            object - which is what a maintenance page or a WAF block
     *                            looks like from here
     */
    private static function decode(string $body): ?array
    {
        if (trim($body) === '') {
            return null;
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }
}
