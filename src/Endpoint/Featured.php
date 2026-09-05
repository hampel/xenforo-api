<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Generated\Schema\FeaturedContent;
use Hampel\XenForo\Api\Result\Page;

/**
 * Featured content - the `Featured content` tag in the XenForo API documentation.
 *
 * The read side of the `feature` endpoints on Threads, Media and Resources: one list
 * holding every kind of featured content together, which is why an entry names its
 * `content_type` and `content_id` rather than being a thread or a resource.
 *
 * NEWER THAN 2.3. Featured content has been in XenForo's front end since 2.2, but its API
 * arrived later - a 2.3.12 install has no `featured/` route and no feature endpoints
 * either, and answers 404. Index::get()->version is how to tell before reading a 404 as
 * "nothing is featured".
 */
final class Featured extends Endpoint
{
    /**
     * One page of featured content, newest first.
     *
     * @param  array<string, scalar|array<mixed>|null>  $filters  content_type ('thread',
     *         'xfmg_media', 'xfrm_resource', or whatever an add-on registers), user_id
     * @return Page<FeaturedContent>
     */
    public function list(int $page = 1, array $filters = []): Page
    {
        return $this->apiPaginate('featured/', 'features', FeaturedContent::fromArray(...), $page, $filters);
    }

    /**
     * @param  array<string, scalar|array<mixed>|null>  $filters
     * @return \Generator<int, FeaturedContent>
     */
    public function each(array $filters = []): \Generator
    {
        yield from $this->apiEach('featured/', 'features', FeaturedContent::fromArray(...), $filters);
    }
}
