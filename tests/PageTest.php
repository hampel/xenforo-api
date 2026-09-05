<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Generated\Schema\User;
use Hampel\XenForo\Api\Result\Page;
use PHPUnit\Framework\TestCase as BaseTestCase;

final class PageTest extends BaseTestCase
{
    public function test_it_reads_xenforos_pagination_block(): void
    {
        $page = Page::fromResponse([
            'users' => [['user_id' => 1], ['user_id' => 2]],
            'pagination' => [
                'current_page' => 2,
                'last_page' => 5,
                'per_page' => 20,
                'shown' => 2,
                'total' => 97,
            ],
        ], 'users', User::fromArray(...));

        $this->assertCount(2, $page);
        $this->assertSame(2, $page->currentPage);
        $this->assertSame(5, $page->lastPage);
        $this->assertSame(20, $page->perPage);
        $this->assertSame(97, $page->total);
        $this->assertTrue($page->hasMore());
        $this->assertFalse($page->isEmpty());
        $this->assertSame(1, $page->items[0]->user_id);
    }

    public function test_the_last_page_has_no_more(): void
    {
        $page = Page::fromResponse([
            'users' => [['user_id' => 1]],
            'pagination' => ['current_page' => 5, 'last_page' => 5, 'per_page' => 20, 'total' => 81],
        ], 'users', User::fromArray(...));

        $this->assertFalse($page->hasMore());
    }

    /**
     * Not every list endpoint paginates - nodes/ does not - and a Page over one of those
     * should still be a usable single page rather than a division by zero.
     */
    public function test_a_response_with_no_pagination_block_is_one_page(): void
    {
        $page = Page::fromResponse(['nodes' => [['node_id' => 1], ['node_id' => 2]]], 'nodes', fn (array $n): array => $n);

        $this->assertCount(2, $page);
        $this->assertSame(1, $page->currentPage);
        $this->assertSame(1, $page->lastPage);
        $this->assertSame(2, $page->total);
        $this->assertFalse($page->hasMore());
    }

    public function test_an_absent_key_is_an_empty_page(): void
    {
        $page = Page::fromResponse(['pagination' => ['current_page' => 1, 'last_page' => 1]], 'users', User::fromArray(...));

        $this->assertTrue($page->isEmpty());
        $this->assertCount(0, $page);
    }

    public function test_it_is_iterable(): void
    {
        $page = Page::fromResponse([
            'users' => [['user_id' => 1], ['user_id' => 2]],
        ], 'users', User::fromArray(...));

        $ids = [];
        foreach ($page as $user) {
            $ids[] = $user->user_id;
        }

        $this->assertSame([1, 2], $ids);
    }
}
