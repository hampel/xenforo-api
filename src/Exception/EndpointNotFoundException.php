<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Exception;

/**
 * A 404 that means the ROUTE is missing, not the record: XenForo's `endpoint_not_found`.
 *
 * XenForo answers a 404 for two different things and, unusually, tells them apart. A record
 * that is not there - or, on some controllers, one the acting user may not see - is
 * `requested_page_not_found`. A path or action the forum does not have at all is
 * `endpoint_not_found`, from \XF\Api\Controller\ErrorController - in 2.2 and 2.3 alike.
 *
 * The second is what an add-on's endpoint answers on a forum without the add-on, and it is a
 * configuration problem rather than an absent record - so apiFind() rethrows this where it
 * would turn the other into null. Under NotFoundException, so anything catching that still
 * sees it.
 */
final class EndpointNotFoundException extends NotFoundException
{
    public const CODE = 'endpoint_not_found';
}
