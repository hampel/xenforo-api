<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Resource;

use Hampel\XenForo\Api\Generated\Schema\Search as SearchEntity;
use Hampel\XenForo\Api\Result\Page;

/**
 * Search - the `Search` tag in the XenForo API documentation.
 *
 * Two calls, not one. A search is created first and its results fetched afterwards by the
 * id it was given, because XenForo caches the result set server-side and pages through the
 * cache rather than re-running the query. That is why there is no `search($keywords)` that
 * hands back results directly: the second call is where the pagination lives, and hiding it
 * would mean re-running the search for every page.
 */
final class Search extends Resource
{
    /**
     * Run a search and get back the search record, whose id fetches the results.
     *
     * @param  array<string, mixed>  $options  search_type, c (the constraint array -
     *         c[title_only], c[nodes], c[users] and so on), order, grouped
     */
    public function create(string $keywords, array $options = []): SearchEntity
    {
        return SearchEntity::fromArray(
            $this->apiPost('search/', ['keywords' => $keywords] + $options)->array('search')
        );
    }

    /**
     * A member's own content.
     *
     * @param  array<string, mixed>  $options  content, type, before, thread_type, grouped
     */
    public function byMember(int $userId, array $options = []): SearchEntity
    {
        return SearchEntity::fromArray(
            $this->apiPost('search/member', ['user_id' => $userId] + $options)->array('search')
        );
    }

    /**
     * One page of a search's results.
     *
     * Results are heterogeneous - a thread, a post, a profile post, a media item - so they
     * come back as arrays rather than entities. `content_type` on each says which it is.
     *
     * @return Page<array<mixed>>
     */
    public function results(int $searchId, int $page = 1): Page
    {
        return $this->apiPaginate(
            'search/' . $searchId . '/',
            'results',
            static fn (array $result): array => $result,
            $page
        );
    }

    /**
     * Extend a search further back in time.
     *
     * XenForo bounds a search by date rather than by result count, so a search that returns
     * nothing is not necessarily a search with no matches - it may just not have reached
     * back far enough. The `get_older_results_date` in the results response is what to pass
     * here.
     */
    public function older(int $searchId, int $before): SearchEntity
    {
        return SearchEntity::fromArray($this->apiPost('search/' . $searchId . '/older', [
            'search_id' => $searchId,
            'before' => $before,
        ])->array('search'));
    }
}
