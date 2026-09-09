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
        $paths = self::paths();

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
     * The other direction: nothing in the specification is missing from this package.
     *
     * There is no allow-list of deliberate omissions, and adding an empty one now would be
     * speculation. If a future specification brings an endpoint that should NOT be wrapped,
     * the decision belongs here, in the diff, with the reason beside it - which is a better
     * record than a list nobody revisits.
     */
    public function test_every_endpoint_in_the_specification_is_wrapped(): void
    {
        $wrapped = [];

        foreach (self::extractCalls() as [, $method, $path]) {
            $wrapped[$method . ' ' . $path] = true;
        }

        $missing = [];

        $paths = self::paths();

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
            "The specification describes %d endpoint(s) no resource class calls:\n  %s\n"
                . 'Wrap them, or record here why not.',
            count($missing),
            implode("\n  ", $missing)
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
     * The specification's paths block, narrowed once: a path name to its operations.
     *
     * @return array<string, array<mixed>>
     */
    private static function paths(): array
    {
        $paths = self::spec()['paths'] ?? null;

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
     * Every API call in src/Endpoint, as [file, method, path].
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

        foreach (glob(dirname(__DIR__) . '/src/Endpoint/*.php') ?: [] as $file) {
            $name = basename($file);

            if ($name === 'Endpoint.php') {
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
