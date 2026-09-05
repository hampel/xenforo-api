<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Exception\ClientException;
use Hampel\XenForo\Api\Result\Embed;

/**
 * oEmbed - the `oEmbed` tag in the XenForo API documentation.
 *
 * THE URL MUST BE ONE OF THIS FORUM'S OWN. XenForo resolves it through its own public
 * router - \XF\Repository\EmbedResolverRepository::getEntityFromUrl() routes the URL to a
 * controller and asks that controller for embeddable content - so the forum is acting as an
 * oEmbed provider for its own threads, posts and profile posts. Handing it a YouTube link
 * gets `requested_content_unavailable`, not a YouTube embed.
 *
 * WHETHER IT NEEDS A KEY IS AN ADMIN'S DECISION. OEmbedController's
 * allowUnauthenticatedRequest() returns the board's `allowExternalEmbed` option, so the
 * same call succeeds without a credential on one forum and answers 400
 * `no_api_key_in_request` on another. That is configuration rather than anything a client
 * can detect in advance.
 */
final class OEmbed extends Endpoint
{
    /**
     * The oEmbed document for a URL on this forum.
     */
    public function get(string $url): Embed
    {
        return Embed::fromArray($this->apiGet('oembed/', ['url' => $url])->data);
    }

    /**
     * The same, answering null where the forum will not embed the URL.
     *
     * A URL the router cannot resolve, content the acting user may not see, and content
     * whose type has no embed handler all arrive as the same 400
     * `requested_content_unavailable` - so this cannot tell you which, only that there is
     * nothing to embed. Anything else still raises.
     */
    public function find(string $url): ?Embed
    {
        try {
            return $this->get($url);
        } catch (ClientException $e) {
            if ($e->hasCode('requested_content_unavailable')) {
                return null;
            }

            throw $e;
        }
    }
}
