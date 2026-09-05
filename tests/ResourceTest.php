<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Exception\ClientException;
use Hampel\XenForo\Api\Exception\NotPermittedException;

/**
 * The pagination machinery in the Resource base class, driven through Users because it has
 * the simplest list endpoint. It behaves the same for every resource, including an add-on's
 * - which is the reason it lives in the base class at all.
 */
final class ResourceTest extends TestCase
{
    /**
     * @param  list<int>  $ids
     * @return array<mixed>
     */
    private function usersPage(array $ids, int $currentPage, int $lastPage, int $total): array
    {
        return [
            'users' => array_map(static fn (int $id): array => ['user_id' => $id, 'username' => 'u' . $id], $ids),
            'pagination' => [
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => 2,
                'shown' => count($ids),
                'total' => $total,
            ],
        ];
    }

    public function test_it_asks_for_the_page_it_was_given(): void
    {
        $this->client->pushJson(200, $this->usersPage([3, 4], 2, 3, 5));

        $page = $this->xenforo()->users()->list(2);

        $this->assertSame('https://forum.example.com/api/users/?page=2', $this->sentUri());
        $this->assertSame(2, $page->currentPage);
        $this->assertTrue($page->hasMore());
    }

    public function test_a_page_below_one_is_clamped(): void
    {
        $this->client->pushJson(200, $this->usersPage([1], 1, 1, 1));

        $this->xenforo()->users()->list(0);

        $this->assertSame('https://forum.example.com/api/users/?page=1', $this->sentUri());
    }

    /**
     * Walking every page is the thing most consumers actually want, and the thing most
     * likely to be written wrongly by hand.
     */
    public function test_each_walks_every_page(): void
    {
        $this->client
            ->pushJson(200, $this->usersPage([1, 2], 1, 3, 5))
            ->pushJson(200, $this->usersPage([3, 4], 2, 3, 5))
            ->pushJson(200, $this->usersPage([5], 3, 3, 5));

        $ids = [];
        foreach ($this->xenforo()->users()->each() as $user) {
            $ids[] = $user->user_id;
        }

        $this->assertSame([1, 2, 3, 4, 5], $ids);
        $this->assertCount(3, $this->client->requests);
    }

    /**
     * It stops at last_page rather than fetching until a page comes back empty. XenForo
     * answers `invalid_page` past the end - assertValidApiPage() throws - so the naive loop
     * ends in an exception on every complete traversal.
     */
    public function test_each_stops_before_the_page_that_would_be_an_error(): void
    {
        $this->client
            ->pushJson(200, $this->usersPage([1, 2], 1, 2, 3))
            ->pushJson(200, $this->usersPage([3], 2, 2, 3))
            ->pushError(400, [['code' => 'invalid_page', 'message' => 'The requested page could not be found.']]);

        $ids = [];
        foreach ($this->xenforo()->users()->each() as $user) {
            $ids[] = $user->user_id;
        }

        $this->assertSame([1, 2, 3], $ids);
        $this->assertCount(2, $this->client->requests, 'The third page must never be requested.');
    }

    /**
     * A generator is lazy, so a caller that stops early pays for the pages it read.
     */
    public function test_each_fetches_only_as_far_as_it_is_consumed(): void
    {
        $this->client->pushJson(200, $this->usersPage([1, 2], 1, 10, 20));

        foreach ($this->xenforo()->users()->each() as $user) {
            $this->assertSame(1, $user->user_id);

            break;
        }

        $this->assertCount(1, $this->client->requests);
    }

    /**
     * A page that claims a successor and then returns nothing would otherwise loop for as
     * long as the forum kept saying so.
     */
    public function test_each_stops_on_an_empty_page_that_claims_a_successor(): void
    {
        $this->client
            ->pushJson(200, $this->usersPage([1], 1, 9, 20))
            ->pushJson(200, $this->usersPage([], 2, 9, 20));

        $ids = [];
        foreach ($this->xenforo()->users()->each() as $user) {
            $ids[] = $user->user_id;
        }

        $this->assertSame([1], $ids);
        $this->assertCount(2, $this->client->requests);
    }

    public function test_find_turns_a_404_into_null(): void
    {
        $this->client->pushError(404, [['code' => 'requested_page_not_found']]);

        $this->assertNull($this->xenforo()->users()->find(99));
    }

    /**
     * Only a 404. A permission failure is not "no such user" and must not read as one.
     */
    public function test_find_does_not_swallow_other_failures(): void
    {
        $this->client->pushError(403, [['code' => 'api_key_inactive']]);

        $this->expectException(NotPermittedException::class);

        $this->xenforo()->users()->find(99);
    }

    public function test_get_throws_where_find_returns_null(): void
    {
        $this->client->pushError(404, [['code' => 'requested_page_not_found']]);

        $this->expectException(ClientException::class);

        $this->xenforo()->users()->get(99);
    }
}
