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
 * Needs XENFORO_URL and XENFORO_API_KEY. Reads threads/ by default, which every forum
 * allows - users/ looks like the obvious choice and is not, because the member list is an
 * option (enableMemberList) that answers 403 for everyone when it is off. Set
 * XENFORO_PAGINATION_PATH to probe another list endpoint - an add-on's, for instance - and
 * XENFORO_PAGINATION_KEY to name the list it returns.
 *
 * @var Hampel\Rig\Io $io
 */

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\HttpFactory;

require __DIR__ . '/lib/client.php';
require __DIR__ . '/lib/content.php';

$io->title('xenforo-api · pagination');

$xf = harness_client($io);
$config = $xf->config();
$key = (string) getenv('XENFORO_API_KEY');
$actingAs = getenv('XENFORO_API_USER');

// A forum's own thread list, not the global threads/ - which quietly applies a
// last-activity cutoff when unfiltered and answers 200 with total 0 on a quiet forum, so
// the probe would be comparing pages of nothing. The per-forum list applies no cutoff.
$path = getenv('XENFORO_PAGINATION_PATH') ?: 'forums/' . harness_forum_node_id($xf, $io) . '/threads';
$listKey = getenv('XENFORO_PAGINATION_KEY') ?: 'threads';

$guzzle = new Guzzle(['http_errors' => false]);
$factory = new HttpFactory();

/**
 * @return array{status: int, body: array<mixed>}
 */
$probe = static function (int $page) use ($guzzle, $factory, $config, $key, $path, $actingAs): array {
    $request = $factory->createRequest('GET', $config->resolve($path, ['page' => $page]))
        ->withHeader('XF-Api-Key', $key)
        ->withHeader('Accept', 'application/json');

    if (is_string($actingAs) && ctype_digit($actingAs)) {
        $request = $request->withHeader('XF-Api-User', $actingAs);
    }

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

/**
 * Something that names each item and differs between items. The first `*_id` field on it,
 * which every XenForo entity has - NOT a prefix of its JSON, which begins with `can_edit`
 * and the other permission booleans and reads identically for every thread on the forum.
 * The first version of this exercise compared such a prefix and reported `page` as
 * ignored on a forum where it was working; the fingerprint has to be the thing that is
 * unique, not the thing that comes first.
 */
// The item's own id: `thread_id` on a list called `threads`, and so on. Falls back to the
// first `*_id` field, which on a thread is first_post_id - unique too, just less readable.
$idField = rtrim($listKey, 's') . '_id';

/** @return string|null one item's id, or null if it has none */
$idOf = static function (mixed $item) use ($idField): ?string {
    if (!is_array($item)) {
        return null;
    }

    if (isset($item[$idField]) && is_scalar($item[$idField])) {
        return (string) $item[$idField];
    }

    foreach ($item as $field => $value) {
        if (is_string($field) && str_ends_with($field, '_id') && is_scalar($value)) {
            return (string) $value;
        }
    }

    return null;
};

$identify = static function (array $list) use ($idOf, $idField): string {
    $item = reset($list);

    if ($item === false) {
        return '(empty)';
    }

    return ($idOf($item) === null) ? 'md5:' . md5((string) json_encode($item)) : $idField . '=' . $idOf($item);
};

/** @return list<string> the ids on a page, in the order the forum sent them */
$ids = static fn (array $list): array => array_values(array_filter(array_map($idOf, $list), is_string(...)));

if ($lastPage < 1) {
    $io->warn('The list is empty, so neither question can be asked of it. If this is the default');
    $io->warn('target, the forum has no threads; the write exercise can give it some.');
} elseif ($lastPage < 2) {
    $io->warn('Only one page of results, so this cannot be probed here.');
    $io->warn('Point XENFORO_PAGINATION_PATH at a longer list, or seed one with the write exercise.');
} else {
    $second = $probe(2);
    $secondList = is_array($second['body'][$listKey] ?? null) ? $second['body'][$listKey] : [];
    $secondPagination = is_array($second['body']['pagination'] ?? null) ? $second['body']['pagination'] : [];

    $overlap = array_intersect($ids($firstList), $ids($secondList));

    $io->values([
        'page 1 current_page' => $pagination['current_page'] ?? '(absent)',
        'page 2 current_page' => $secondPagination['current_page'] ?? '(absent)',
        'page 1 first item' => $identify($firstList),
        'page 2 first item' => $identify($secondList),
        'page 1 ids' => implode(', ', $ids($firstList)),
        'page 2 ids' => implode(', ', $ids($secondList)),
        'ids on both pages' => $overlap === [] ? '(none - as it should be)' : implode(', ', $overlap),
    ]);

    $io->line();

    if ($ids($firstList) === $ids($secondList)) {
        $io->error('✗ Both pages returned the same items. `page` is not being honoured.');
        $io->error('  Every paginated read in this package would silently return page 1 forever.');
    } elseif ($overlap !== []) {
        // Not the same failure, and the first version of this exercise reported it as one.
        // `page` is honoured - the pages differ - but an item is on both, which means
        // another is on neither. XenForo's thread list sorts by last_post_date with no
        // tiebreaker, and MySQL does not promise a stable order among ties across LIMIT
        // pages; enough threads sharing a second - a seed, an import, a bulk move - and a
        // walk repeats one and skips one. each() cannot see this. A caller who needs every
        // item de-duplicates by id and knows the walk is a sample, not a snapshot.
        $io->warn('! `page` is honoured, but the pages OVERLAP: an item is on both, so another is on');
        $io->warn('  neither. The sort key has ties and no tiebreaker, and MySQL orders ties as it');
        $io->warn('  likes from one LIMIT to the next. A walk over this list is a sample, not a');
        $io->warn('  snapshot - de-duplicate by id, and expect a miss when many items share a second.');
    } else {
        $io->success('✓ the two pages differ and share nothing, so `page` is honoured and the walk is stable');
    }
}

// ---- question 1 ----

$io->line();
$io->info('Does a page past the end error, rather than returning nothing?');

if ($lastPage < 1) {
    $io->warn('An empty list has no end to go past - page 1 of nothing is valid, not beyond.');

    exit(0);
}

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
