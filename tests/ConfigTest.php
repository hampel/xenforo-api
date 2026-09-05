<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Config;
use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as BaseTestCase;

final class ConfigTest extends BaseTestCase
{
    #[DataProvider('baseUris')]
    public function test_it_accepts_a_board_url_or_an_api_url(string $given, string $expected): void
    {
        $this->assertSame($expected, (new Config($given))->baseUri);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function baseUris(): iterable
    {
        yield 'board url' => ['https://forum.example.com', 'https://forum.example.com/api'];
        yield 'board url with slash' => ['https://forum.example.com/', 'https://forum.example.com/api'];
        yield 'api url' => ['https://forum.example.com/api', 'https://forum.example.com/api'];
        yield 'api url with slash' => ['https://forum.example.com/api/', 'https://forum.example.com/api'];
        yield 'in a subdirectory' => ['https://example.com/community', 'https://example.com/community/api'];
        yield 'plain http' => ['http://forum.local', 'http://forum.local/api'];
    }

    public function test_it_requires_a_base_uri(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Config('  ');
    }

    /**
     * A hostname on its own would resolve to a relative path and produce requests that go
     * nowhere useful, with no obvious sign of why.
     */
    public function test_it_requires_an_absolute_base_uri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be absolute');

        new Config('forum.example.com');
    }

    public function test_it_rejects_a_version_below_one(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Config('https://forum.example.com', 0);
    }

    public function test_it_resolves_a_relative_path(): void
    {
        $config = new Config('https://forum.example.com');

        $this->assertSame('https://forum.example.com/api/threads/1/', $config->resolve('threads/1/'));
        $this->assertSame('https://forum.example.com/api/threads/1/', $config->resolve('/threads/1/'));
    }

    /**
     * A URL the API itself produced can be fed straight back in.
     */
    public function test_an_absolute_uri_passes_through(): void
    {
        $config = new Config('https://forum.example.com');

        $this->assertSame(
            'https://forum.example.com/api/users/3/',
            $config->resolve('https://forum.example.com/api/users/3/')
        );
    }

    public function test_a_path_that_already_carries_the_api_prefix_is_not_doubled(): void
    {
        $config = new Config('https://forum.example.com');

        $this->assertSame('https://forum.example.com/api/users/', $config->resolve('api/users/'));
    }

    public function test_a_pinned_version_is_added_once(): void
    {
        $config = new Config('https://forum.example.com', 2);

        $this->assertSame('https://forum.example.com/api/v2/users/', $config->resolve('users/'));
        $this->assertSame('https://forum.example.com/api/v2/users/', $config->resolve('v2/users/'));
        $this->assertSame('https://forum.example.com/api/v1/users/', $config->resolve('v1/users/'));
    }

    public function test_query_parameters_use_php_bracket_notation(): void
    {
        $config = new Config('https://forum.example.com');

        $this->assertSame(
            'https://forum.example.com/api/search/?keywords=hello&c%5Bnodes%5D%5B0%5D=2&c%5Bnodes%5D%5B1%5D=3',
            $config->resolve('search/', ['keywords' => 'hello', 'c' => ['nodes' => [2, 3]]])
        );
    }

    public function test_null_query_parameters_are_dropped(): void
    {
        $config = new Config('https://forum.example.com');

        $this->assertSame(
            'https://forum.example.com/api/users/?page=2',
            $config->resolve('users/', ['page' => 2, 'order' => null])
        );
    }

    public function test_it_names_the_host(): void
    {
        $this->assertSame('forum.example.com', (new Config('https://forum.example.com'))->host());
    }
}
