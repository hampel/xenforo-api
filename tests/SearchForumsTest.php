<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

final class SearchForumsTest extends TestCase
{
    /**
     * The specification carries no SearchForum schema, so this comes back as the array the
     * forum sent rather than as an invented class - search_criteria and the sort settings
     * are its own fields, and they are not a node's.
     */
    public function test_the_record_comes_back_as_the_forum_sent_it(): void
    {
        $this->client->pushJson(200, ['search_forum' => [
            'node_id' => 7,
            'search_criteria' => ['keywords' => 'release notes'],
            'sort_order' => 'last_post_date',
            'sort_direction' => 'desc',
            'max_results' => 200,
        ]]);

        $searchForum = $this->xenforo()->searchForums()->get(7);

        $this->assertSame(7, $searchForum['node_id']);
        $this->assertSame(['keywords' => 'release notes'], $searchForum['search_criteria']);
        $this->assertSame('https://forum.example.com/api/search-forums/7/', $this->sentUri());
    }

    public function test_the_record_and_a_page_of_threads_come_back_together(): void
    {
        $this->client->pushJson(200, [
            'search_forum' => ['node_id' => 7],
            'threads' => [['thread_id' => 1], ['thread_id' => 2]],
            'pagination' => ['current_page' => 1, 'last_page' => 3, 'per_page' => 2, 'total' => 6],
        ]);

        $result = $this->xenforo()->searchForums()->withThreads(7);

        $this->assertSame(7, $result['search_forum']['node_id']);
        $this->assertCount(2, $result['threads']);
        $this->assertTrue($result['threads']->hasMore());
        $this->assertSame(
            'https://forum.example.com/api/search-forums/7/?with_threads=1&page=1',
            $this->sentUri()
        );
    }

    public function test_threads_can_be_paged_on_their_own(): void
    {
        $this->client->pushJson(200, [
            'threads' => [['thread_id' => 1]],
            'pagination' => ['current_page' => 2, 'last_page' => 2, 'per_page' => 1, 'total' => 2],
        ]);

        $page = $this->xenforo()->searchForums()->threads(7, 2);

        $this->assertSame(2, $page->currentPage);
        $this->assertFalse($page->hasMore());
        $this->assertSame('https://forum.example.com/api/search-forums/7/threads?page=2', $this->sentUri());
    }

    /**
     * A search forum has no sticky threads, whatever the specification says: the annotation
     * the docs were compiled from is shared with the real forum endpoint, and
     * getThreadsInSearchForumPaginated() returns `threads` and `pagination` and nothing
     * else. Nothing here reads a sticky key, so a forum that started sending one would
     * change nothing rather than half-work.
     */
    public function test_nothing_here_looks_for_sticky_threads(): void
    {
        $this->client->pushJson(200, [
            'threads' => [['thread_id' => 1]],
            'sticky' => [['thread_id' => 99]],
            'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
        ]);

        $page = $this->xenforo()->searchForums()->threads(7);

        $this->assertCount(1, $page);
        $this->assertSame(1, $page->items[0]->thread_id);
    }
}
