<?php

/**
 * Exercise: create a user, threads, replies and reactions on a real forum, and read them back. Writes, and leaves it all.
 *
 * The first live run of every write wrapper - Users::create(), Threads::create(),
 * Posts::create(), Posts::react(), Threads::update(), Threads::markRead() - against a forum
 * rather than a stub that answers whatever it is asked. Nothing here is a demonstration;
 * each step reads back what it wrote, because the failure shape on a write is the 200 that
 * did something other than what was asked.
 *
 * THE QUESTIONS
 *
 *   1. Does content land as the user the key is acting as? A super-user key with
 *      XF-Api-User set should create a thread whose starter IS that user. Created as guest,
 *      or as the key's owner, it would still be a 200.
 *   2. Does actingAs() switch the author mid-process? With XENFORO_WRITE_USER=1 a user is
 *      created and the replies are posted through $xf->actingAs($newUserId). The reply's
 *      username read back is the answer, and "same as the thread starter" is the failure.
 *   3. Is what was written readable immediately, through the same API, by id? A thread
 *      and its posts fetched straight after creation, with the ids the create returned.
 *   4. Does a reaction register, and an edit stick, and does mark-read answer success?
 *
 * WHAT IT LEAVES BEHIND: everything. That is deliberate and it is why this exercise is for
 * a development forum and nothing else. Every title carries a stamp so what it made is
 * recognisable, and it never deletes anything - least of all a user. XENFORO_WRITE_THREADS
 * sets how many threads to create (default 1); 25 or so is enough to give pagination a
 * second page on a fresh forum.
 *
 * Needs XENFORO_URL, a SUPER-USER XENFORO_API_KEY, and XENFORO_API_USER naming the user to
 * act as - a super key acts as a guest otherwise, and a guest cannot post.
 * Set XENFORO_WRITE=1 to run it. Under an agent, XENFORO_AGENT_MAY_WRITE=1 as well, on the
 * command line and never in .env.
 *
 * @var Hampel\Rig\Io $io
 */

use Hampel\XenForo\Api\Exception\EndpointNotFoundException;
use Hampel\XenForo\Api\Exception\ExceptionInterface;

require __DIR__ . '/lib/agent.php';
require __DIR__ . '/lib/client.php';
require __DIR__ . '/lib/content.php';

$io->title('xenforo-api · write');

/**
 * @return array{bool, string}
 */
$mode = static function (): array {
    if (getenv('XENFORO_WRITE') !== '1') {
        return [false, 'refused - set XENFORO_WRITE=1 to write to the forum'];
    }

    if (harness_agent_refuses('XENFORO_AGENT_MAY_WRITE')) {
        return [false, 'refused - XENFORO_WRITE ignored in an agent session'];
    }

    return [true, 'writing - content will be created on the forum and left there'];
};

[$proceed, $description] = $mode();

$io->value('mode', $description);

if (!$proceed) {
    $io->line();
    $io->warn('Nothing was sent, so this run answers none of the four questions above.');

    exit(0);
}

$xf = harness_client($io);

$io->value('forum', $xf->config()->baseUri);
$io->value('credential', $xf->authentication()->describe());
$io->line();

$actingAs = getenv('XENFORO_API_USER');

if (!is_string($actingAs) || !ctype_digit($actingAs)) {
    $io->error('XENFORO_API_USER is not set. A super-user key acts as a guest without it, and a guest');
    $io->error('cannot post - so there is nothing this exercise could write.');

    exit(1);
}

$actingAs = (int) $actingAs;
$nodeId = harness_forum_node_id($xf, $io);
$stamp = gmdate('Ymd-His');
$tag = 'xfapi-' . bin2hex(random_bytes(3));
$threadCount = max(1, (int) (getenv('XENFORO_WRITE_THREADS') ?: 1));
$createUser = getenv('XENFORO_WRITE_USER') === '1';

$io->values([
    'node_id' => $nodeId,
    'acting as' => 'user ' . $actingAs,
    'threads to create' => $threadCount,
    'create a user' => $createUser ? 'yes' : 'no (XENFORO_WRITE_USER=1)',
    'stamp' => $stamp,
]);
$io->line();

$failure = null;

try {
    // ---- 1. a thread, as the acting user ---------------------------------------------
    $io->info('1. threads/ - create, as the acting user');

    $thread = $xf->threads()->create([
        'node_id' => $nodeId,
        'title' => sprintf('%s harness thread %s', $tag, $stamp),
        'message' => sprintf('Opening post, written by the API harness at %s.', $stamp),
    ]);

    if ($thread->thread_id === null) {
        throw new \RuntimeException('the create came back without a thread_id');
    }

    $io->values([
        'thread_id' => $thread->thread_id,
        'starter user_id' => $thread->user_id,
        'starter' => $thread->username,
        'first post_id' => $thread->first_post_id,
    ]);

    $io->line($thread->user_id === $actingAs
        ? '     ✓ started by the acting user'
        : sprintf('     ✗ started by user %s, not the acting user %d - XF-Api-User was not honoured', var_export($thread->user_id, true), $actingAs));
    $io->line();

    // ---- 2. optionally a user, who then replies --------------------------------------
    $replier = $xf;
    $replierId = $actingAs;

    if ($createUser) {
        $io->info('2. users/ - create, then act as them');

        $username = $tag . '-' . substr($stamp, -6);
        $user = $xf->users()->create([
            'username' => $username,
            'email' => $tag . '@example.com',
            'password' => bin2hex(random_bytes(12)),
        ]);

        if ($user->user_id === null) {
            throw new \RuntimeException('the user create came back without a user_id');
        }

        $io->values([
            'user_id' => $user->user_id,
            'username' => $user->username,
            'user_state' => $user->user_state ?? '(not returned)',
        ]);

        $replier = $xf->actingAs($user->user_id);
        $replierId = $user->user_id;

        $io->line('     ✓ created; the replies below are posted as this user');
        $io->line();
    }

    // ---- 3. replies, a reaction, an edit, mark-read ----------------------------------
    $io->info($createUser ? '3. posts/ - reply as the new user, react, edit' : '2. posts/ - reply, react, edit');

    $reply = $replier->posts()->create($thread->thread_id, sprintf('First reply, at %s.', $stamp));
    $second = $replier->posts()->create($thread->thread_id, sprintf('Second reply, at %s.', $stamp));

    $io->values([
        'reply post_id' => $reply->post_id,
        'reply by user_id' => $reply->user_id,
        'reply by' => $reply->username,
    ]);

    $io->line($reply->user_id === $replierId
        ? sprintf('     ✓ replied as user %d', $replierId)
        : sprintf('     ✗ replied as user %s, expected %d', var_export($reply->user_id, true), $replierId));

    if ($reply->post_id === null || $second->post_id === null) {
        throw new \RuntimeException('a reply came back without a post_id');
    }

    // React as the ORIGINAL acting user to the new user's post - one cannot react to
    // one's own post, so this only works cross-user, which is what makes it a test.
    $reacted = $xf->posts()->react($reply->post_id);
    $io->line($reacted ? '     ✓ reacted to the reply' : '     ✗ react answered success false');

    $edited = $replier->posts()->update($second->post_id, sprintf('Second reply, EDITED at %s.', $stamp));
    $io->line(str_contains((string) $edited->message, 'EDITED') ? '     ✓ edit stuck' : '     ✗ edit did not stick: ' . var_export($edited->message, true));

    $retitled = $xf->threads()->update($thread->thread_id, ['title' => sprintf('%s harness thread %s (retitled)', $tag, $stamp)]);
    $io->line(str_ends_with((string) $retitled->title, '(retitled)') ? '     ✓ thread retitled' : '     ✗ retitle did not stick');

    // threads/{id}/mark-read arrived in 2.2. On 2.1 it is a missing route, which is a
    // finding about the forum's version and not about the client, so say so and go on.
    try {
        $io->line($xf->threads()->markRead($thread->thread_id) ? '     ✓ mark-read answered success' : '     ✗ mark-read answered success false');
    } catch (EndpointNotFoundException) {
        $io->line('     - mark-read is not an endpoint on this forum (added in XenForo 2.2)');
    }
    $io->line();

    // ---- 4. read it all back by id ---------------------------------------------------
    $io->info('Read back by id, through a second request each:');

    $again = $xf->threads()->get($thread->thread_id);
    $posts = $xf->threads()->posts($thread->thread_id);

    $io->values([
        'thread title' => $again->title,
        'reply_count' => $again->reply_count,
        'posts on page 1' => count($posts),
        'post ids' => implode(', ', array_map(static fn ($p) => (string) $p->post_id, $posts->items)),
    ]);

    $expectedPosts = 3;
    $io->line(count($posts) === $expectedPosts
        ? sprintf('     ✓ %d posts read back, as written', $expectedPosts)
        : sprintf('     ✗ expected %d posts, read %d', $expectedPosts, count($posts)));

    $reacted = null;
    foreach ($posts->items as $p) {
        if ($p->post_id === $reply->post_id) {
            $reacted = $p;
        }
    }

    if ($reacted !== null) {
        $io->line(sprintf('     reaction_score on the reacted post: %s', var_export($reacted->reaction_score, true)));
    }

    $io->line();

    // ---- 5. more threads, for pagination ---------------------------------------------
    if ($threadCount > 1) {
        $io->info(sprintf('Seeding %d more threads so the forum has a second page:', $threadCount - 1));

        for ($i = 2; $i <= $threadCount; $i++) {
            $xf->threads()->create([
                'node_id' => $nodeId,
                'title' => sprintf('%s harness thread %s #%d', $tag, $stamp, $i),
                'message' => sprintf('Seed thread %d of %d, written by the API harness at %s.', $i, $threadCount, $stamp),
            ]);
        }

        $page = $xf->forums()->threads($nodeId);

        $io->values([
            'threads in forum' => $page['threads']->total,
            'pages' => $page['threads']->lastPage,
        ]);
        $io->line($page['threads']->lastPage > 1
            ? '     ✓ the forum now paginates - run the pagination exercise'
            : '     ! still one page; raise XENFORO_WRITE_THREADS');
        $io->line();
    }
} catch (ExceptionInterface $e) {
    $failure = $e;

    $io->line();
    $io->error(sprintf('✗ %s', $e::class));
    $io->error('  ' . $e->getMessage());
}

if ($failure !== null) {
    exit(1);
}

$io->success(sprintf('✓ done - everything above is tagged %s and has been left on the forum', $tag));
