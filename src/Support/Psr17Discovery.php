<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Support;

use Hampel\XenForo\Api\Exception\RuntimeException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Finds a PSR-17 factory when the caller did not pass one.
 *
 * The package requires the PSR-17 interfaces and no implementation, on purpose - it does
 * not care which HTTP library an application uses. But a consumer who does not care either
 * should not have to name one to construct a Client, so when none is given this looks for
 * the implementations in common use, in the order they are likely to be installed.
 *
 * By class NAME, then checked with instanceof, rather than by referencing the classes.
 * That is what keeps the package's declared dependencies honest: composer-require-checker
 * sees no undeclared symbol because none is written, and the analysis run with the dev
 * dependencies removed has nothing to miss. It also means a class that exists but is not a
 * factory - or a factory whose package has changed its interfaces - is skipped rather than
 * returned.
 */
final class Psr17Discovery
{
    /**
     * Request-factory class and stream-factory class, per implementation. Guzzle and
     * Nyholm each ship one class doing both roles; Diactoros splits them.
     *
     * @var list<array{string, string}>
     */
    public const CANDIDATES = [
        ['GuzzleHttp\Psr7\HttpFactory', 'GuzzleHttp\Psr7\HttpFactory'],
        ['Nyholm\Psr7\Factory\Psr17Factory', 'Nyholm\Psr7\Factory\Psr17Factory'],
        ['Laminas\Diactoros\RequestFactory', 'Laminas\Diactoros\StreamFactory'],
    ];

    /**
     * @return array{RequestFactoryInterface, StreamFactoryInterface}
     */
    public static function find(): array
    {
        return self::from(self::CANDIDATES);
    }

    /**
     * The same search over a given list, so the not-found path can be exercised on a
     * machine that has Guzzle installed - which is every machine this package is developed
     * on.
     *
     * @param  list<array{string, string}>  $candidates
     * @return array{RequestFactoryInterface, StreamFactoryInterface}
     */
    public static function from(array $candidates): array
    {
        foreach ($candidates as [$requestClass, $streamClass]) {
            if (!class_exists($requestClass) || !class_exists($streamClass)) {
                continue;
            }

            $request = new $requestClass();
            $stream = $requestClass === $streamClass ? $request : new $streamClass();

            if ($request instanceof RequestFactoryInterface && $stream instanceof StreamFactoryInterface) {
                return [$request, $stream];
            }
        }

        throw new RuntimeException(
            'No PSR-17 factory was given to the Client and none could be found. Pass a '
                . RequestFactoryInterface::class . ' and a ' . StreamFactoryInterface::class
                . ' to its constructor, or install one of guzzlehttp/psr7, nyholm/psr7 or '
                . 'laminas/laminas-diactoros, which are discovered automatically.'
        );
    }
}
