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
 * It now checks the reverse as well, which it did not always. Coverage is a scope decision
 * rather than a defect, so requiring it only made sense once it was complete - and it is:
 * every endpoint in the specification is wrapped. Keeping that true is the point. When
 * `resources/openapi.json` is next updated, whatever XenForo has added shows up here as a
 * named failure rather than as nothing at all, which is the only moment anybody would
 * otherwise have noticed.
 *
 * Each specification is held to its own classes. DragonByte eCommerce's endpoints are checked
 * against resources/dbtech-ecommerce.openapi.json, which is transcribed by hand rather than
 * published - so here the test catches a path mistyped in either place, and the two failing
 * together is the cue to go back to the add-on's source.
 */
final class SpecConformanceTest extends BaseTestCase
{
    /**
     * The endpoint classes each specification is held to: their directory, the base class
     * there that is not itself an endpoint group, and how many calls the extractor must find.
     */
    private const SETS = [
        'openapi.json' => ['src/Endpoint', 'Endpoint.php', 40],
        'dbtech-ecommerce.openapi.json' => ['src/Endpoint/DBTech/eCommerce', 'eCommerceEndpoint.php', 20],
    ];

    /**
     * @return iterable<string, array{string}>
     */
    public static function specifications(): iterable
    {
        foreach (array_keys(self::SETS) as $spec) {
            yield $spec => [$spec];
        }
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function endpoints(): iterable
    {
        foreach (array_keys(self::SETS) as $spec) {
            foreach (self::extractCalls($spec) as [$file, $method, $path]) {
                yield sprintf('%s %s (%s)', $method, $path, $file) => [$spec, $method, $path, $file];
            }
        }
    }

    #[DataProvider('endpoints')]
    public function test_the_endpoint_exists_in_the_specification(
        string $spec,
        string $method,
        string $path,
        string $file,
    ): void {
        $paths = self::paths($spec);

        $this->assertArrayHasKey($path, $paths, sprintf(
            '%s calls %s, which is not a path in %s.',
            $file,
            $path,
            $spec
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
     * The other direction: nothing in the specification is missing from this package.
     *
     * There is no allow-list of deliberate omissions, and adding an empty one now would be
     * speculation. If a future specification brings an endpoint that should NOT be wrapped,
     * the decision belongs here, in the diff, with the reason beside it - which is a better
     * record than a list nobody revisits.
     */
    #[DataProvider('specifications')]
    public function test_every_endpoint_in_the_specification_is_wrapped(string $spec): void
    {
        $wrapped = [];

        foreach (self::extractCalls($spec) as [, $method, $path]) {
            $wrapped[$method . ' ' . $path] = true;
        }

        $missing = [];

        $paths = self::paths($spec);

        foreach ($paths as $path => $operations) {
            foreach ($operations as $method => $operation) {
                if (!is_array($operation) || !isset($operation['responses'])) {
                    continue; // the shared `parameters` block, which is not an operation
                }

                $endpoint = strtoupper($method) . ' ' . $path;

                if (!isset($wrapped[$endpoint])) {
                    $missing[] = $endpoint;
                }
            }
        }

        $this->assertSame([], $missing, sprintf(
            "%s describes %d endpoint(s) no resource class calls:\n  %s\n"
                . 'Wrap them, or record here why not.',
            $spec,
            count($missing),
            implode("\n  ", $missing)
        ));
    }

    /**
     * Guards the extractor itself. If a refactor renamed the helpers and this stopped
     * finding calls, every test above would pass by finding nothing to check - which is the
     * failure mode a data-provider test has and a plain one does not.
     */
    #[DataProvider('specifications')]
    public function test_it_found_the_calls(string $spec): void
    {
        $this->assertGreaterThan(self::SETS[$spec][2], count(self::extractCalls($spec)));
    }

    /**
     * @return array<mixed>
     */
    private static function spec(string $name): array
    {
        static $specs = [];

        if (!isset($specs[$name])) {
            $specs[$name] = json_decode(
                (string) file_get_contents(dirname(__DIR__) . '/resources/' . $name),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        $spec = $specs[$name];

        if (!is_array($spec)) {
            throw new \LogicException("{$name} is not a JSON object.");
        }

        return $spec;
    }

    /**
     * The specification's paths block, narrowed once: a path name to its operations.
     *
     * @return array<string, array<mixed>>
     */
    private static function paths(string $spec): array
    {
        $paths = self::spec($spec)['paths'] ?? null;

        if (!is_array($paths)) {
            throw new \LogicException('The specification has no paths block.');
        }

        $narrowed = [];

        foreach ($paths as $path => $operations) {
            if (is_string($path) && is_array($operations)) {
                $narrowed[$path] = $operations;
            }
        }

        return $narrowed;
    }

    /**
     * Every API call in the endpoint classes a specification is held to, as
     * [file, method, path].
     *
     * Read statically out of the source: a path built as `'users/' . $userId . '/'` becomes
     * `/users/{id}/`, which is the form the specification uses.
     *
     * @return list<array{string, string, string}>
     */
    private static function extractCalls(string $spec): array
    {
        [$directory, $base] = self::SETS[$spec];

        $methods = [
            'apiGet' => 'GET',
            'apiGetRaw' => 'GET',
            'apiPaginate' => 'GET',
            'apiEach' => 'GET',
            'apiFind' => 'GET',
            'apiPost' => 'POST',
            'apiPut' => 'PUT',
            'apiDelete' => 'DELETE',
            'apiUpload' => 'POST',
            'get' => 'GET',
            'getRaw' => 'GET',
            'post' => 'POST',
            'put' => 'PUT',
            'delete' => 'DELETE',
            'postMultipart' => 'POST',
        ];

        $pattern = '/(?:\$this->(api(?:GetRaw|Get|Post|Put|Delete|Upload|Paginate|Each|Find))'
            . '|\$this->connection->(getRaw|get|post|put|delete|postMultipart))'
            . '\(\s*((?:\'(?:[^\'\\\\]|\\\\.)*\'|\s*\.\s*|\$[A-Za-z_]\w*)+)/';

        $calls = [];

        foreach (glob(dirname(__DIR__) . '/' . $directory . '/*.php') ?: [] as $file) {
            $name = basename($file);

            if ($name === $base) {
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
