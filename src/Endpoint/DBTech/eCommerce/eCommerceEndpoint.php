<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint\DBTech\eCommerce;

use Hampel\XenForo\Api\Endpoint\Endpoint;
use Hampel\XenForo\Api\Result\ApiResponse;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The base of every DragonByte eCommerce endpoint group.
 *
 * DRAGONBYTE ECOMMERCE IS A THIRD-PARTY ADD-ON. Unlike XFMG and XFRM it is not bundled with
 * XenForo, and it is wrapped here as the worked example of what the Endpoint extension
 * mechanism is for. A forum without it answers `endpoint_not_found` for every path, and so
 * does a forum that has it with its API switched off (from 5.4.2; earlier releases answered a
 * plain 404 there, the same as a missing record) - which surfaces as
 * EndpointNotFoundException, never as a null from find().
 *
 * IDENTIFY THE REQUEST. eCommerce refuses every request that does not say which board and
 * platform it is on behalf of - see Identity - so in practice every call here goes through
 * identifiedAs(). An unidentified request is still sent as it is, rather than refused on
 * this side, and 5.4.x answers it with a 400 naming the four missing fields.
 */
abstract class eCommerceEndpoint extends Endpoint
{
    private ?Identity $identity = null;

    /**
     * The same endpoints, sending the identity's headers with every request.
     *
     * A copy rather than a mutation, because Client::endpoint() hands every caller the
     * same instance and one caller's board must not become everybody's.
     */
    public function identifiedAs(Identity $identity): static
    {
        $endpoint = clone $this;
        $endpoint->identity = $identity;

        return $endpoint;
    }

    public function identity(): ?Identity
    {
        return $this->identity;
    }

    /**
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    protected function apiGet(string $path, array $query = []): ApiResponse
    {
        if ($this->identity === null) {
            return parent::apiGet($path, $query);
        }

        return $this->connection->send($this->request('GET', $path, $query));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    protected function apiPost(string $path, array $payload = [], array $query = []): ApiResponse
    {
        if ($this->identity === null) {
            return parent::apiPost($path, $payload, $query);
        }

        return $this->connection->send($this->connection->withForm($this->request('POST', $path, $query), $payload));
    }

    /**
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    protected function apiGetRaw(string $path, array $query = []): ResponseInterface
    {
        if ($this->identity === null) {
            return parent::apiGetRaw($path, $query);
        }

        return $this->connection->sendRaw($this->request('GET', $path, $query));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, \Hampel\XenForo\Api\Upload>  $files
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    protected function apiUpload(string $path, array $payload, array $files, array $query = []): ApiResponse
    {
        if ($this->identity === null) {
            return parent::apiUpload($path, $payload, $files, $query);
        }

        return $this->connection->send(
            $this->connection->withMultipart($this->request('POST', $path, $query), $payload, $files)
        );
    }

    /**
     * @param  array<string, scalar|array<mixed>|null>  $query
     * @param  array<string, mixed>  $payload
     */
    protected function apiDelete(string $path, array $query = [], array $payload = []): ApiResponse
    {
        if ($this->identity === null) {
            return parent::apiDelete($path, $query, $payload);
        }

        $request = $this->request('DELETE', $path, $query);

        return $this->connection->send($payload === [] ? $request : $this->connection->withForm($request, $payload));
    }

    /**
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    private function request(string $method, string $path, array $query): RequestInterface
    {
        $request = $this->connection->request($method, $path, $query);

        foreach ($this->identity?->headers() ?? [] as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }

    /**
     * Every eCommerce delete takes the same optional query: hard_delete, reason, and the
     * author alert.
     *
     * @param  array<string, scalar|null>  $options  author_alert, author_alert_reason
     * @return array<string, scalar>
     */
    protected static function deleteQuery(bool $hard, ?string $reason, array $options = []): array
    {
        return array_filter([
            'hard_delete' => $hard ? '1' : null,
            'reason' => $reason,
        ] + $options, static fn ($value): bool => $value !== null);
    }
}
