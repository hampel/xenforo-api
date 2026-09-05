<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Generated\Schema\Thread;
use Hampel\XenForo\Api\Result\Page;

/**
 * Search forums - the `Search forums` tag in the XenForo API documentation.
 *
 * A search forum is a node that looks like a forum and is a saved search: its threads are
 * whatever currently matches its criteria, from anywhere on the board. The id is a node_id,
 * the same number that addresses it in Nodes.
 *
 * TWO THINGS ABOUT IT THAT ARE NOT TRUE OF A FORUM:
 *
 * Its results always respect the acting user's permissions, even under a super-user key
 * with api_bypass_permissions - XenForo builds a per-user cache and serves from that, so
 * this is the one list in the API a bypass does not widen.
 *
 * And it has no sticky threads. The specification says it does, because the annotation the
 * docs were compiled from is shared with the real forum endpoint, but
 * SearchForumController::getThreadsInSearchForumPaginated() returns `threads` and
 * `pagination` and nothing else. Nothing here models a sticky list, deliberately.
 */
final class SearchForums extends Endpoint
{
    /**
     * The search forum's own record.
     *
     * Returned as the array the forum sent. XenForo's specification carries no schema for a
     * search forum - there is no SearchForum among its components - so there is nothing to
     * generate from and inventing a class here would be a guess at fields that differ by
     * version. What comes back is search_criteria, sort_order, sort_direction, max_results
     * and the node's own fields.
     *
     * @return array<mixed>
     */
    public function get(int $nodeId): array
    {
        return $this->apiGet('search-forums/' . $nodeId . '/')->array('search_forum');
    }

    /**
     * The search forum together with one page of its threads, in a single call.
     *
     * Needs the `thread:read` scope as well as `node:read`, which the plain get() does not -
     * the controller asserts it only when threads are asked for.
     *
     * @return array{search_forum: array<mixed>, threads: Page<Thread>}
     */
    public function withThreads(int $nodeId, int $page = 1): array
    {
        $response = $this->apiGet('search-forums/' . $nodeId . '/', [
            'with_threads' => '1',
            'page' => max(1, $page),
        ]);

        return [
            'search_forum' => $response->array('search_forum'),
            'threads' => Page::fromResponse($response->data, 'threads', Thread::fromArray(...)),
        ];
    }

    /**
     * One page of the threads currently matching.
     *
     * @return Page<Thread>
     */
    public function threads(int $nodeId, int $page = 1): Page
    {
        return $this->apiPaginate(
            'search-forums/' . $nodeId . '/threads',
            'threads',
            Thread::fromArray(...),
            $page
        );
    }

    /**
     * Every matching thread, a page at a time.
     *
     * The match is re-evaluated as you go rather than frozen at the first page, so a thread
     * that stops matching mid-traversal can be missed and one that starts matching can be
     * seen twice. That is true of any list in this API and more likely here, the criteria
     * usually being about recency.
     *
     * @return \Generator<int, Thread>
     */
    public function eachThread(int $nodeId): \Generator
    {
        yield from $this->apiEach(
            'search-forums/' . $nodeId . '/threads',
            'threads',
            Thread::fromArray(...)
        );
    }
}
