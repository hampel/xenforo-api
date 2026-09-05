<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Every endpoint this package calls exists in XenForo's own OpenAPI specification.
 *
 * A drift guard rather than a unit test, and the one test here that a client for a
 * hand-written API could not write. The resource classes are hand-written, so their paths
 * are typed by a person: `threads/{id}/mark-read` against `threads/{id}/markread` is a
 * 404 at runtime and identical to the eye. Every stubbed test in this suite passes either
 * way, because the stub answers whatever it is asked.
 *
 * The paths are read out of the source rather than by calling anything, so this runs with
 * no network and no forum.
 *
 * What it deliberately does NOT do is require the reverse - that this package covers every
 * endpoint in the spec. Coverage is a scope decision; a typo is a defect.
 */
final class SpecConformanceTest extends BaseTestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function endpoints(): iterable
    {
        foreach (self::extractCalls() as [$file, $method, $path]) {
            yield sprintf('%s %s (%s)', $method, $path, $file) => [$method, $path, $file];
        }
    }

    #[DataProvider('endpoints')]
    public function test_the_endpoint_exists_in_the_specification(string $method, string $path, string $file): void
    {
        $paths = self::spec()['paths'];

        $this->assertArrayHasKey($path, $paths, sprintf(
            '%s calls %s, which is not a path in the XenForo API specification.',
            $file,
            $path
        ));

        $this->assertArrayHasKey(strtolower($method), $paths[$path], sprintf(
            '%s calls %s %s, but the specification only allows %s on that path.',
            $file,
            $method,
            $path,
            strtoupper(implode(', ', array_keys(array_filter(
                $paths[$path],
                static fn (mixed $operation): bool => is_array($operation)
            ))))
        ));
    }

    /**
     * Guards the extractor itself. If a refactor renamed the helpers and this stopped
     * finding calls, every test above would pass by finding nothing to check - which is the
     * failure mode a data-provider test has and a plain one does not.
     */
    public function test_it_found_the_calls(): void
    {
        $this->assertGreaterThan(40, count(self::extractCalls()));
    }

    /**
     * @return array<mixed>
     */
    private static function spec(): array
    {
        static $spec = null;

        if ($spec === null) {
            $spec = json_decode(
                (string) file_get_contents(dirname(__DIR__) . '/resources/openapi.json'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        /** @var array<mixed> $spec */
        return $spec;
    }

    /**
     * Every API call in src/Resource, as [file, method, path].
     *
     * Read statically out of the source: a path built as `'users/' . $userId . '/'` becomes
     * `/users/{id}/`, which is the form the specification uses.
     *
     * @return list<array{string, string, string}>
     */
    private static function extractCalls(): array
    {
        $methods = [
            'apiGet' => 'GET',
            'apiPaginate' => 'GET',
            'apiEach' => 'GET',
            'apiFind' => 'GET',
            'apiPost' => 'POST',
            'apiPut' => 'PUT',
            'apiDelete' => 'DELETE',
            'get' => 'GET',
            'post' => 'POST',
            'put' => 'PUT',
            'delete' => 'DELETE',
        ];

        $pattern = '/(?:\$this->(api(?:Get|Post|Put|Delete|Paginate|Each|Find))'
            . '|\$this->connection->(get|post|put|delete))'
            . '\(\s*((?:\'(?:[^\'\\\\]|\\\\.)*\'|\s*\.\s*|\$[A-Za-z_]\w*)+)/';

        $calls = [];

        foreach (glob(dirname(__DIR__) . '/src/Resource/*.php') ?: [] as $file) {
            $name = basename($file);

            if ($name === 'Resource.php') {
                continue; // the base class, whose paths are its callers'
            }

            preg_match_all($pattern, (string) file_get_contents($file), $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $helper = $match[1] !== '' ? $match[1] : $match[2];
                $call = [$name, $methods[$helper], self::normalisePath($match[3])];

                // list() and each() reach the same endpoint, deliberately. One check is
                // enough, and a data provider will not take a duplicate key.
                $calls[implode(' ', $call)] = $call;
            }
        }

        return array_values($calls);
    }

    /**
     * `'users/' . $userId . '/profile-posts'` becomes `/users/{id}/profile-posts`.
     */
    private static function normalisePath(string $expression): string
    {
        preg_match_all('/\'((?:[^\'\\\\]|\\\\.)*)\'|(\$[A-Za-z_]\w*)/', $expression, $parts, PREG_SET_ORDER);

        $path = '';

        foreach ($parts as $part) {
            $path .= ($part[2] ?? '') !== '' ? '{id}' : stripslashes($part[1] ?? '');
        }

        return '/' . ltrim($path, '/');
    }
}
