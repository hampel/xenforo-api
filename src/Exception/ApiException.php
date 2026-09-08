<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Exception;

use Hampel\XenForo\Api\ApiError;
use Psr\Http\Message\ResponseInterface;

/**
 * The forum answered, and the answer was not a success.
 *
 * Subclassed by status because that is what a caller can branch on without knowing
 * XenForo's error codes - but the codes are the precise signal, and they are on every
 * instance via errors() and hasCode(). A 400 covers everything from a missing required
 * input to a page past the end of a result set, so `$e->hasCode('invalid_page')` is
 * usually the test you actually want.
 */
abstract class ApiException extends XenForoException
{
    /**
     * @param  list<ApiError>  $errors  the API's own errors[], decoded
     * @param  string  $body  the raw response body, for when it was not JSON at all
     */
    final public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly array $errors = [],
        public readonly string $body = '',
    ) {
        parent::__construct($message, $statusCode);
    }

    /**
     * @param  array<mixed>|null  $decoded  the decoded body, or null if it was not JSON
     */
    public static function fromResponse(
        string $method,
        string $uri,
        ResponseInterface $response,
        ?array $decoded,
        string $body,
    ): self {
        $status = $response->getStatusCode();
        $errors = ApiError::listFromResponse($decoded);

        // Be defensive about the body. The forum is not the only thing that can answer on
        // this URL: a maintenance page, a WAF or a reverse proxy in front of it returns
        // HTML, and so does XenForo itself if the base URI is missing its /api suffix.
        $detail = $errors !== []
            ? implode('; ', array_map(strval(...), $errors))
            : self::summarise($body);

        $message = sprintf(
            'The XenForo API rejected %s %s (HTTP %d)%s',
            $method,
            $uri,
            $status,
            $detail === '' ? '' : ': ' . $detail
        );

        return match (true) {
            $status === 401 => new NotAuthenticatedException($message, $status, $errors, $body),
            $status === 403 => new NotPermittedException($message, $status, $errors, $body),
            $status === 404 => new NotFoundException($message, $status, $errors, $body),
            $status >= 500 => new ServerException($message, $status, $errors, $body),
            default => new ClientException($message, $status, $errors, $body),
        };
    }

    /**
     * @return list<ApiError>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function hasCode(string $code): bool
    {
        foreach ($this->errors as $error) {
            if ($error->code === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return array_map(static fn (ApiError $error): string => $error->code, $this->errors);
    }

    /**
     * A non-JSON body, cut down to something that belongs in an exception message. An HTML
     * error page is frequently kilobytes long and none of it helps.
     */
    protected static function summarise(string $body): string
    {
        $body = trim(preg_replace('/\s+/', ' ', $body) ?? '');

        return strlen($body) > 200 ? substr($body, 0, 197) . '...' : $body;
    }
}
