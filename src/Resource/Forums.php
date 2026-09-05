<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Resource;

use Hampel\XenForo\Api\Generated\Schema\Node;
use Hampel\XenForo\Api\Generated\Schema\Thread;
use Hampel\XenForo\Api\Result\Page;

/**
 * Forums - the `Forums` tag in the XenForo API documentation.
 *
 * A forum is a node of type `Forum`, and its id is a node_id: the same number addresses it
 * here and in Nodes. There is no forum list endpoint, because the node tree is the list -
 * see Nodes::list().
 */
final class Forums extends Resource
{
    public function get(int $nodeId): Node
    {
        return Node::fromArray($this->apiGet('forums/' . $nodeId . '/')->array('forum'));
    }

    public function find(int $nodeId): ?Node
    {
        return $this->apiFind('forums/' . $nodeId . '/', [], 'forum', Node::fromArray(...));
    }

    /**
     * One page of a forum's threads.
     *
     * Sticky threads come back in a separate `sticky` key rather than inside the page, so
     * they are returned alongside it - they are not part of the pagination and repeat on
     * every page if you treat them as though they were.
     *
     * @param  array<string, scalar|array<mixed>|null>  $filters  prefix_id, starter_id,
     *         last_days, unread, thread_type, order, direction
     * @return array{threads: Page<Thread>, sticky: list<Thread>}
     */
    public function threads(int $nodeId, int $page = 1, array $filters = []): array
    {
        $filters['page'] = max(1, $page);

        $response = $this->apiGet('forums/' . $nodeId . '/threads', $filters);

        $sticky = [];
        foreach ($response->array('sticky') as $thread) {
            if (is_array($thread)) {
                $sticky[] = Thread::fromArray($thread);
            }
        }

        return [
            'threads' => Page::fromResponse($response->data, 'threads', Thread::fromArray(...)),
            'sticky' => $sticky,
        ];
    }

    /**
     * Every thread in a forum, sticky threads excluded - they are not paginated, so
     * including them here would repeat them once per page.
     *
     * @param  array<string, scalar|array<mixed>|null>  $filters
     * @return \Generator<int, Thread>
     */
    public function eachThread(int $nodeId, array $filters = []): \Generator
    {
        yield from $this->apiEach(
            'forums/' . $nodeId . '/threads',
            'threads',
            Thread::fromArray(...),
            $filters
        );
    }

    public function markRead(int $nodeId, ?int $date = null): bool
    {
        return $this->apiPost('forums/' . $nodeId . '/mark-read', $date === null ? [] : ['date' => $date])
            ->isSuccess();
    }
}
