<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Exception;

/**
 * 404. No such record - or no such route, which is what an add-on endpoint that is not
 * installed on this forum looks like, and what a base URI pointing at the forum's front end
 * rather than its API looks like too. The three are not distinguishable from here.
 */
final class NotFoundException extends ClientException
{
}
