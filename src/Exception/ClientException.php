<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Exception;

/**
 * A 4xx: the forum answered, and the caller is the one who has to change something.
 *
 * Not final, and the parent of the three specific 4xx types below it, so a consumer can
 * catch this to mean "anything the caller got wrong" and let a ServerException through -
 * a distinction that matters for retry policy, since a 4xx will fail again identically.
 *
 * XenForo uses 400 for most of these, including a missing required input, a validation
 * failure on the entity and a page past the end of a result set. Read the error code
 * rather than the status: $e->hasCode('invalid_page') is nearly always the test wanted.
 */
class ClientException extends ApiException
{
}
