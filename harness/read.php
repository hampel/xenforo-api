<?php

/**
 * Exercise: read a real forum - nodes, a page of threads, a thread's posts. Read-only.
 *
 * A demonstration rather than an investigation, and the thing to run first when a resource
 * class is not behaving: it prints what the forum actually returned, which is the half a
 * stubbed test can never show you. The entity fields that come back empty here are as
 * informative as the ones that do not - they are the fields this key may not see.
 *
 * Needs XENFORO_URL and XENFORO_API_KEY. XENFORO_NODE_ID picks the forum to read from;
 * without it the exercise uses the first Forum node it finds.
 *
 * @var Hampel\Rig\Io $io
 */

use Hampel\XenForo\Api\Exception\ExceptionInterface;

require __DIR__ . '/lib/client.php';

$io->title('xenforo-api · read');

$xf = harness_client($io);

try {
    $tree = $xf->nodes()->list();

    $io->value('nodes', count($tree['nodes']));

    $forums = array_values(array_filter(
        $tree['nodes'],
        static fn ($node): bool => $node->node_type_id === 'Forum'
    ));

    $io->value('of which forums', count($forums));

    $nodeId = (int) (getenv('XENFORO_NODE_ID') ?: ($forums[0]->node_id ?? 0));

    if ($nodeId === 0) {
        $io->warn('No Forum node found and XENFORO_NODE_ID is not set - stopping here.');

        exit(0);
    }

    $forum = $xf->forums()->get($nodeId);

    $io->line();
    $io->values([
        'forum node_id' => $forum->node_id,
        'forum title' => $forum->title,
    ]);

    $result = $xf->forums()->threads($nodeId);

    $io->values([
        'threads on page' => count($result['threads']),
        'sticky threads' => count($result['sticky']),
        'pages' => $result['threads']->lastPage,
        'threads in total' => $result['threads']->total,
    ]);

    $thread = $result['threads']->items[0] ?? $result['sticky'][0] ?? null;

    if ($thread === null || $thread->thread_id === null) {
        $io->line();
        $io->warn('No threads in that forum - nothing further to read.');

        exit(0);
    }

    $io->line();
    $io->values([
        'thread_id' => $thread->thread_id,
        'title' => $thread->title,
        'replies' => $thread->reply_count,
        'started by' => $thread->username,
    ]);

    $posts = $xf->threads()->posts($thread->thread_id);

    $io->values([
        'posts on page' => count($posts),
        'post pages' => $posts->lastPage,
    ]);

    $first = $posts->items[0] ?? null;

    if ($first !== null) {
        $io->line();
        $io->values([
            'first post_id' => $first->post_id,
            'first post by' => $first->username,
            'message length' => strlen((string) $first->message),
        ]);
    }
} catch (ExceptionInterface $e) {
    $io->line();
    $io->error('✗ ' . $e::class);
    $io->error($e->getMessage());

    exit(1);
}

$io->line();
$io->success('✓ read the forum');
