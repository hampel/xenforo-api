<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api;

use Hampel\XenForo\Api\Authentication\Authentication;
use Hampel\XenForo\Api\Exception\ApiException;
use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Hampel\XenForo\Api\Exception\MalformedResponseException;
use Hampel\XenForo\Api\Exception\RequestException;
use Hampel\XenForo\Api\Result\ApiResponse;
use Hampel\XenForo\Api\Result\ResponseMeta;
use Hampel\XenForo\Api\Support\Payload;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
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
     * This package never sends JSON: everything is form-encoded, bar the handful of
     * endpoints that take a file and declare multipart, so it never meets that either.
     */
    public const FORM_CONTENT_TYPE = 'application/x-www-form-urlencoded';

    /**
     * The other body encoding, and the mirror image of the rule above: this one MUST carry
     * a parameter, because the boundary is the only way the far end can find where the
     * parts start. Multipart::contentType() writes the whole header.
     */
    public const MULTIPART_CONTENT_TYPE = 'multipart/form-data';

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
     * something this class does not cover - an add-on endpoint that answers in something
     * other than JSON, most likely - needs the same client and the same factories to do
     * it, rather than reaching for an HTTP library of its own.
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
     * Upload one or more files: an attachment, an avatar, a featured-content image.
     *
     * POST only, and there is deliberately no putMultipart() beside put(). PHP populates
     * $_FILES for a POST and for nothing else, and XenForo's own fallback for the other
     * methods - \XF\Http\Request::convertCustomMethodPhpInput() - parses one encoding,
     * `application/x-www-form-urlencoded`, and has no concept of a file at all. A multipart
     * PUT therefore arrives with no files AND no fields, and answers 200 having done
     * nothing, which is the same silent failure FORM_CONTENT_TYPE is about, reached by a
     * different road. An add-on cannot fix that from its controller; it is upstream of
     * anything an add-on gets to see.
     *
     * @param  array<string, mixed>  $payload  the ordinary fields, named as they would be
     *                                         in a form-encoded body
     * @param  array<string, Upload>  $files  keyed by the input name the endpoint reads
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    public function postMultipart(string $path, array $payload = [], array $files = [], array $query = []): ApiResponse
    {
        return $this->send($this->withMultipart($this->request('POST', $path, $query), $payload, $files));
    }

    /**
     * Attach a multipart body to a request built elsewhere.
     *
     * The method is checked rather than trusted, because the failure it prevents is
     * invisible: see postMultipart() above. A caller who has genuinely found an add-on
     * endpoint that reads a file from a PUT has found a XenForo bug, not a limitation here.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, Upload>  $files
     */
    public function withMultipart(RequestInterface $request, array $payload, array $files): RequestInterface
    {
        if ($request->getMethod() !== 'POST') {
            throw new InvalidArgumentException(sprintf(
                'A multipart body can only be sent on a POST, not a %s: PHP parses uploads for POST alone, so '
                    . 'the request would arrive at the forum carrying neither its files nor its fields.',
                $request->getMethod()
            ));
        }

        $multipart = new Multipart($payload, $files);

        return $request
            ->withHeader('Content-Type', $multipart->contentType())
            ->withBody($multipart->stream($this->streamFactory));
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
     * Build a request without sending it, for a caller assembling something this class does
     * not cover. The credential and the Accept header are already applied.
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
            ->withBody($this->streamFactory->createStream(Payload::encode($payload)));
    }

    /**
     * A GET whose answer is not JSON.
     *
     * Three endpoints in the API are like this - `attachments/{id}/data` returns the file
     * itself, and the two thumbnail endpoints answer with a 301 to an image - so the
     * response comes back whole rather than decoded. See sendRaw().
     *
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    public function getRaw(string $path, array $query = []): ResponseInterface
    {
        return $this->sendRaw($this->request('GET', $path, $query));
    }

    /**
     * Send a request that was built elsewhere, with this connection's error handling.
     */
    public function send(RequestInterface $request): ApiResponse
    {
        $response = $this->dispatch($request);

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $decoded = self::decode($body);
        $meta = ResponseMeta::fromResponse($response);

        if ($status >= 200 && $status < 300) {
            $this->noteVersion($meta);

            if ($decoded !== null) {
                return new ApiResponse($decoded, $status, $meta);
            }

            if ($status === 204) {
                // No Content, and nothing in XenForo's own API sends one - but it is the
                // one success status whose empty body means what it says.
                return new ApiResponse([], $status, $meta);
            }

            // A 2xx that did not decode is not an empty answer, it is somebody else's
            // answer - a maintenance page, a WAF, a CDN interstitial, a truncated body.
            // Returned as [] it would read as "no such record" everywhere downstream,
            // which is precisely the failure this package exists to keep visible.
            $this->logger->error('XenForo API answered success with a body that is not JSON', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'status' => $status,
                'content_type' => $response->getHeaderLine('Content-Type'),
            ]);

            throw MalformedResponseException::forResponse(
                $request->getMethod(),
                (string) $request->getUri(),
                $response,
                $body
            );
        }

        $this->logger->error('XenForo API error response', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'status' => $status,
            'body' => $decoded ?? $body,
        ]);

        throw ApiException::fromResponse(
            $request->getMethod(),
            (string) $request->getUri(),
            $response,
            $decoded,
            $body
        );
    }

    /**
     * The same send, without reading the body.
     *
     * send() reads the whole response into a string to decode it, which is exactly wrong
     * for a 40MB attachment - so this hands back the PSR-7 response with its body stream
     * untouched, and the caller decides whether to read it, copy it to disk or throw it
     * away.
     *
     * A REDIRECT IS A SUCCESS HERE, which is the other difference. `attachments/{id}/data`
     * answers 304 to a conditional request, and the thumbnail endpoints answer 301 with the
     * image's URL in the Location header - that redirect IS the documented output, so
     * turning it into an exception the way send() does would discard the answer. Whether it
     * ever reaches the caller depends on the injected client: a PSR-18 client is free to
     * follow redirects, and most do by default.
     *
     * A 4xx or 5xx still throws. Those bodies are JSON even on these endpoints, because the
     * error is rendered by the API renderer rather than by the attachment view.
     */
    public function sendRaw(RequestInterface $request): ResponseInterface
    {
        $response = $this->dispatch($request);

        $status = $response->getStatusCode();

        if ($status < 400) {
            $this->noteVersion(ResponseMeta::fromResponse($response));

            return $response;
        }

        $body = (string) $response->getBody();
        $decoded = self::decode($body);

        $this->logger->error('XenForo API error response', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'status' => $status,
            'body' => $decoded ?? $body,
        ]);

        throw ApiException::fromResponse(
            $request->getMethod(),
            (string) $request->getUri(),
            $response,
            $decoded,
            $body
        );
    }

    /**
     * Everything both of the above do before they differ: log it, send it, and keep a
     * transport failure distinct from an HTTP status. A PSR-18 client throws only for the
     * former, which is what makes that separation free.
     */
    private function dispatch(RequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        $uri = (string) $request->getUri();

        $this->logger->debug('XenForo API request', ['method' => $method, 'uri' => $uri]);

        try {
            return $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('XenForo API request failed', [
                'method' => $method,
                'uri' => $uri,
                'error' => $e->getMessage(),
            ]);

            throw RequestException::for($method, $uri, $e);
        }
    }

    /**
     * Not a warning about anything broken - nothing has failed - but the only notice a
     * client ever gets that the forum has a newer API.
     */
    private function noteVersion(ResponseMeta $meta): void
    {
        if ($meta->isOutdated()) {
            $this->logger->info('XenForo API version is behind the forum', $meta->toArray());
        }
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
