<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Exception;

/**
 * 404. No such record - or, on some controllers, a record the acting user may not see,
 * which XenForo answers identically so as not to confirm it exists. Those two are not
 * distinguishable from here.
 *
 * A route that does not exist is a 404 as well, but XenForo marks that one
 * `endpoint_not_found` and it arrives as the EndpointNotFoundException subclass - so a
 * base URI pointing at the forum's front end, or an add-on endpoint on a forum without the
 * add-on, can be told from an absent record after all. Not final, for that reason.
 */
class NotFoundException extends ClientException
{
}
