<?php

/**
 * Exercise: check the two pagination assumptions every list method rests on. Read-only.
 *
 * Two questions, and a stub cannot answer either - it would answer with whatever this
 * package assumed when the stub was written, which is the assumption under test.
 *
 *   1. Does XenForo really error past the last page? Resource::apiEach() terminates on
 *      hasMore() rather than on an empty page, because \XF\Api\Controller\
 *      AbstractController::assertValidApiPage() throws `invalid_page` rather than
 *      returning nothing. If that ever stopped being true the current code would still be
 *      correct - but a caller who wrote the obvious loop instead would be fine too, and
 *      the reason for the odd-looking termination would have quietly evaporated.
 *
 *   2. Is `page` actually honoured? This is the dangerous one, and the one that fails
 *      silently. An ignored page parameter does not error: it answers 200 with page 1 in
 *      it, every time, and a caller walking pages gets the first page repeatedly and
 *      believes it has read the lot. So the run fetches page 1 and page 2 over the same
 *      window and prints both current_page values and both first ids. Two identical
 *      answers is the failure.
 *
 * Probes are issued raw, with http_errors off, rather than through the package: the status
 * codes and error codes ARE the answer here, and this package turns them into exceptions.
 *
 * Needs XENFORO_URL and XENFORO_API_KEY. Reads users/ by default, which every forum has;
 * set XENFORO_PAGINATION_PATH to probe another list endpoint - an add-on's, for instance -
 * and XENFORO_PAGINATION_KEY to name the list it returns.
 *
 * @var Hampel\Rig\Io $io
 */

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\HttpFactory;
use Hampel\XenForo\Api\Config;

require __DIR__ . '/lib/client.php';

$io->title('xenforo-api · pagination');

$config = new Config(harness_forum_url($io));
$key = getenv('XENFORO_API_KEY');

if (!is_string($key) || $key === '') {
    $io->error('XENFORO_API_KEY is not set. Copy .env.example to .env beside the package.');

    exit(1);
}

$path = getenv('XENFORO_PAGINATION_PATH') ?: 'users/';
$listKey = getenv('XENFORO_PAGINATION_KEY') ?: 'users';

$guzzle = new Guzzle(['http_errors' => false]);
$factory = new HttpFactory();

/**
 * @return array{status: int, body: array<mixed>}
 */
$probe = static function (int $page) use ($guzzle, $factory, $config, $key, $path): array {
    $request = $factory->createRequest('GET', $config->resolve($path, ['page' => $page]))
        ->withHeader('XF-Api-Key', $key)
        ->withHeader('Accept', 'application/json');

    $response = $guzzle->sendRequest($request);
    $decoded = json_decode((string) $response->getBody(), true);

    return ['status' => $response->getStatusCode(), 'body' => is_array($decoded) ? $decoded : []];
};

$io->values(['endpoint' => $path, 'list key' => $listKey]);
$io->line();

$first = $probe(1);

if ($first['status'] !== 200) {
    $io->error(sprintf('✗ page 1 answered %d, so there is nothing to compare against.', $first['status']));
    $io->error(json_encode($first['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '');

    exit(1);
}

$pagination = is_array($first['body']['pagination'] ?? null) ? $first['body']['pagination'] : [];
$lastPage = (int) ($pagination['last_page'] ?? 1);

$io->values([
    'current_page' => $pagination['current_page'] ?? '(absent)',
    'last_page' => $lastPage,
    'per_page' => $pagination['per_page'] ?? '(absent)',
    'total' => $pagination['total'] ?? '(absent)',
]);

if ($pagination === []) {
    $io->line();
    $io->warn('That endpoint returned no pagination block, so neither question applies to it.');

    exit(0);
}

// ---- question 2, first, because it needs a second page to exist ----

$io->line();
$io->info('Is `page` honoured?');

$firstList = is_array($first['body'][$listKey] ?? null) ? $first['body'][$listKey] : [];
$identify = static fn (array $list): string => json_encode(reset($list) ?: null) === false
    ? '(unreadable)'
    : substr((string) json_encode(reset($list) ?: null), 0, 60);

if ($lastPage < 2) {
    $io->warn('Only one page of results, so this cannot be probed here.');
    $io->warn('Point XENFORO_PAGINATION_PATH at a longer list to answer it.');
} else {
    $second = $probe(2);
    $secondList = is_array($second['body'][$listKey] ?? null) ? $second['body'][$listKey] : [];
    $secondPagination = is_array($second['body']['pagination'] ?? null) ? $second['body']['pagination'] : [];

    $io->values([
        'page 1 current_page' => $pagination['current_page'] ?? '(absent)',
        'page 2 current_page' => $secondPagination['current_page'] ?? '(absent)',
        'page 1 first item' => $identify($firstList),
        'page 2 first item' => $identify($secondList),
    ]);

    $io->line();

    if ($identify($firstList) === $identify($secondList)) {
        $io->error('✗ Both pages returned the same first item. `page` is not being honoured.');
        $io->error('  Every paginated read in this package would silently return page 1 forever.');
    } else {
        $io->success('✓ the two pages differ, so `page` is honoured');
    }
}

// ---- question 1 ----

$io->line();
$io->info('Does a page past the end error, rather than returning nothing?');

$beyond = $probe($lastPage + 1);
$codes = [];

foreach ($beyond['body']['errors'] ?? [] as $error) {
    if (is_array($error) && isset($error['code']) && is_scalar($error['code'])) {
        $codes[] = (string) $error['code'];
    }
}

$io->values([
    'requested page' => $lastPage + 1,
    'status' => $beyond['status'],
    'error codes' => $codes === [] ? '(none)' : implode(', ', $codes),
]);

$io->line();

if (in_array('invalid_page', $codes, true)) {
    $io->success('✓ invalid_page, as assumed - terminating on hasMore() is what keeps each() out of it');
} elseif ($beyond['status'] === 200) {
    $io->warn('! A page past the end answered 200. XenForo no longer errors there.');
    $io->warn('  Nothing in this package breaks - each() stops before reaching it either way -');
    $io->warn('  but the comment explaining why it stops that way is now describing history.');
} else {
    $io->warn(sprintf('! Answered %d with codes [%s], which is neither outcome this exercise knows about.', $beyond['status'], implode(', ', $codes)));
}
