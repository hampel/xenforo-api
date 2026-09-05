<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use Hampel\XenForo\Api\Authentication\ApiKey;
use Hampel\XenForo\Api\Authentication\Authentication;
use Hampel\XenForo\Api\Client;
use Hampel\XenForo\Api\Config;
use Hampel\XenForo\Api\Connection;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class TestCase extends BaseTestCase
{
    protected StubClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new StubClient();
    }

    protected function xenforo(
        ?Authentication $authentication = null,
        ?Config $config = null,
        ?LoggerInterface $logger = null,
    ): Client {
        $factory = new HttpFactory();

        return new Client(
            $config ?? new Config('https://forum.example.com'),
            $authentication ?? new ApiKey('test-api-key'),
            $this->client,
            $factory,
            $factory,
            $logger ?? new NullLogger()
        );
    }

    protected function connection(
        ?Authentication $authentication = null,
        ?Config $config = null,
        ?LoggerInterface $logger = null,
    ): Connection {
        return $this->xenforo($authentication, $config, $logger)->connection();
    }

    protected function sentUri(): string
    {
        return (string) $this->client->lastRequest()->getUri();
    }

    /**
     * The form-encoded body of the last request, decoded back into an array.
     *
     * @return array<mixed>
     */
    protected function sentBody(): array
    {
        parse_str((string) $this->client->lastRequest()->getBody(), $parsed);

        return $parsed;
    }

    /**
     * The multipart body of the last request, taken apart on the boundary its own header
     * declares.
     */
    protected function sentParts(): MultipartBody
    {
        return MultipartBody::fromRequest($this->client->lastRequest());
    }

    protected function sentBodyRaw(): string
    {
        return (string) $this->client->lastRequest()->getBody();
    }
}
