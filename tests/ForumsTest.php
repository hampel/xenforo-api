<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

final class ForumsTest extends TestCase
{
    /**
     * Sticky threads come back outside the pagination, so treating them as part of the page
     * repeats them once for every page walked.
     */
    public function test_sticky_threads_are_separated_from_the_paginated_ones(): void
    {
        $this->client->pushJson(200, [
            'threads' => [['thread_id' => 10], ['thread_id' => 11]],
            'sticky' => [['thread_id' => 1, 'title' => 'Rules']],
            'pagination' => ['current_page' => 1, 'last_page' => 4, 'per_page' => 2, 'total' => 8],
        ]);

        $result = $this->xenforo()->forums()->threads(2);

        $this->assertCount(2, $result['threads']);
        $this->assertCount(1, $result['sticky']);
        $this->assertSame('Rules', $result['sticky'][0]->title);
        $this->assertTrue($result['threads']->hasMore());
    }

    public function test_walking_every_thread_never_yields_the_sticky_ones(): void
    {
        $this->client
            ->pushJson(200, [
                'threads' => [['thread_id' => 10]],
                'sticky' => [['thread_id' => 1]],
                'pagination' => ['current_page' => 1, 'last_page' => 2, 'per_page' => 1, 'total' => 2],
            ])
            ->pushJson(200, [
                'threads' => [['thread_id' => 11]],
                'sticky' => [['thread_id' => 1]],
                'pagination' => ['current_page' => 2, 'last_page' => 2, 'per_page' => 1, 'total' => 2],
            ]);

        $ids = [];
        foreach ($this->xenforo()->forums()->eachThread(2) as $thread) {
            $ids[] = $thread->thread_id;
        }

        $this->assertSame([10, 11], $ids);
    }
}
