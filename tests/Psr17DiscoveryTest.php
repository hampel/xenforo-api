<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use Hampel\XenForo\Api\Authentication\ApiKey;
use Hampel\XenForo\Api\Client;
use Hampel\XenForo\Api\Config;
use Hampel\XenForo\Api\Exception\RuntimeException;
use Hampel\XenForo\Api\Support\Psr17Discovery;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Psr17DiscoveryTest extends TestCase
{
    public function test_a_client_can_be_built_without_naming_a_factory(): void
    {
        $xf = new Client(new Config('https://forum.example.com'), new ApiKey('k'), $this->client);

        $this->assertInstanceOf(RequestFactoryInterface::class, $xf->connection()->requestFactory());
        $this->assertInstanceOf(StreamFactoryInterface::class, $xf->connection()->streamFactory());

        // and the found factory actually works end to end
        $this->client->pushJson(200, ['user' => ['user_id' => 1]]);
        $this->assertSame(1, $xf->users()->get(1)->user_id);
    }

    public function test_on_this_machine_it_finds_guzzle(): void
    {
        [$request, $stream] = Psr17Discovery::find();

        $this->assertInstanceOf(HttpFactory::class, $request);
        $this->assertSame($request, $stream, 'Guzzle ships one class for both roles; it should be built once.');
    }

    public function test_a_factory_that_is_given_is_used_rather_than_discovered(): void
    {
        $given = new HttpFactory();

        $xf = new Client(new Config('https://forum.example.com'), new ApiKey('k'), $this->client, $given, $given);

        $this->assertSame($given, $xf->connection()->requestFactory());
    }

    /**
     * The not-found path cannot be reached with Guzzle installed, so it is reached through
     * the list instead.
     */
    public function test_nothing_found_says_what_to_pass_and_what_to_install(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(RequestFactoryInterface::class);
        $this->expectExceptionMessage('guzzlehttp/psr7');

        Psr17Discovery::from([['No\Such\Factory', 'No\Such\Factory']]);
    }

    /**
     * A class that exists but is not a factory is skipped, not returned - the instanceof is
     * what makes discovery by name safe.
     */
    public function test_a_class_that_exists_but_is_not_a_factory_is_skipped(): void
    {
        $this->expectException(RuntimeException::class);

        Psr17Discovery::from([[\stdClass::class, \stdClass::class]]);
    }

    public function test_later_candidates_are_tried_when_earlier_ones_are_absent(): void
    {
        [$request] = Psr17Discovery::from([
            ['No\Such\Factory', 'No\Such\Factory'],
            [\stdClass::class, \stdClass::class],
            [HttpFactory::class, HttpFactory::class],
        ]);

        $this->assertInstanceOf(HttpFactory::class, $request);
    }
}
