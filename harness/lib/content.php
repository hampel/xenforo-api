<?php

/**
 * Not an exercise - see lib/agent.php. What an exercise that writes needs from the forum
 * before it can: somewhere to put content, and something to upload.
 */

use Hampel\Rig\Io;
use Hampel\XenForo\Api\Client;
use Hampel\XenForo\Api\Exception\ExceptionInterface;

/**
 * The forum node to write into: XENFORO_NODE_ID, or the first Forum in the tree.
 *
 * Exits rather than throws, and is meant to be called before any try/finally that has a
 * cleanup in it - PHP does not run a finally on exit().
 */
function harness_forum_node_id(Client $xf, Io $io): int
{
    try {
        $tree = $xf->nodes()->list();
    } catch (ExceptionInterface $e) {
        $io->error(sprintf('✗ could not read the node tree: %s', $e->getMessage()));

        exit(1);
    }

    $forums = array_values(array_filter(
        $tree['nodes'],
        static fn ($node): bool => $node->node_type_id === 'Forum'
    ));

    $nodeId = (int) (getenv('XENFORO_NODE_ID') ?: ($forums[0]->node_id ?? 0));

    if ($nodeId === 0) {
        $io->warn('No Forum node found and XENFORO_NODE_ID is not set - stopping here.');

        exit(0);
    }

    return $nodeId;
}

/**
 * The smallest valid PNG: one transparent pixel. Real image data, because the forum
 * inspects the contents as well as the name, and a file of random bytes named .png would
 * be refused for the wrong reason.
 */
function harness_one_pixel_png(): string
{
    return (string) base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        true
    );
}
