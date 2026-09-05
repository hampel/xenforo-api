<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Result\SiteStats;

/**
 * Stats - the `Stats` tag in the XenForo API documentation.
 *
 * One endpoint, and the cheapest interesting call in the API: the thread, message and user
 * totals come from the forum's cached statistics rather than from a count, so this costs
 * about what `index/` does no matter how large the board is.
 *
 * The totals are the cache's, which the forum rebuilds periodically - they are the numbers
 * shown at the foot of the forum, not a live count, and can lag by minutes.
 */
final class Stats extends Endpoint
{
    public function get(): SiteStats
    {
        return SiteStats::fromArray($this->apiGet('stats/')->data);
    }
}
