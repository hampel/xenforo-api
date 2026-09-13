<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Authentication\ApiKey;
use Hampel\XenForo\Api\Authentication\SuperUserKey;
use Hampel\XenForo\Api\Endpoint\Alerts;
use Hampel\XenForo\Api\Endpoint\Attachments;
use Hampel\XenForo\Api\Endpoint\Auth;
use Hampel\XenForo\Api\Endpoint\ConversationMessages;
use Hampel\XenForo\Api\Endpoint\Conversations;
use Hampel\XenForo\Api\Endpoint\Featured;
use Hampel\XenForo\Api\Endpoint\Forums;
use Hampel\XenForo\Api\Endpoint\Index;
use Hampel\XenForo\Api\Endpoint\Me;
use Hampel\XenForo\Api\Endpoint\Media;
use Hampel\XenForo\Api\Endpoint\MediaAlbums;
use Hampel\XenForo\Api\Endpoint\MediaCategories;
use Hampel\XenForo\Api\Endpoint\MediaComments;
use Hampel\XenForo\Api\Endpoint\Nodes;
use Hampel\XenForo\Api\Endpoint\OAuth2;
use Hampel\XenForo\Api\Endpoint\OEmbed;
use Hampel\XenForo\Api\Endpoint\Posts;
use Hampel\XenForo\Api\Endpoint\ProfilePostComments;
use Hampel\XenForo\Api\Endpoint\ProfilePosts;
use Hampel\XenForo\Api\Endpoint\ResourceCategories;
use Hampel\XenForo\Api\Endpoint\ResourceReviews;
use Hampel\XenForo\Api\Endpoint\ResourceUpdates;
use Hampel\XenForo\Api\Endpoint\ResourceVersions;
use Hampel\XenForo\Api\Endpoint\Resources;
use Hampel\XenForo\Api\Endpoint\Search;
use Hampel\XenForo\Api\Endpoint\SearchForums;
use Hampel\XenForo\Api\Endpoint\Stats;
use Hampel\XenForo\Api\Endpoint\Threads;
use Hampel\XenForo\Api\Endpoint\Users;
use Hampel\XenForo\Api\Client;
use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

final class ClientTest extends TestCase
{
    public function test_resources_are_memoised(): void
    {
        $xf = $this->xenforo();

        $this->assertSame($xf->users(), $xf->users());
        $this->assertSame($xf->threads(), $xf->threads());
        $this->assertNotSame($xf->users(), $this->xenforo()->users());
    }

    public function test_acting_as_returns_a_new_client_leaving_the_original_alone(): void
    {
        $xf = $this->xenforo(new SuperUserKey('super', actingAs: 1));
        $other = $xf->actingAs(2);

        $this->assertNotSame($xf, $other);

        $this->client->pushJson(200, ['me' => ['user_id' => 2]])->pushJson(200, ['me' => ['user_id' => 1]]);

        $other->me()->get();
        $this->assertSame('2', $this->client->lastRequest()->getHeaderLine('XF-Api-User'));

        $xf->me()->get();
        $this->assertSame('1', $this->client->lastRequest()->getHeaderLine('XF-Api-User'));
    }

    /**
     * Only a super-user key can act as somebody else. Failing here rather than at the forum
     * turns a 403 in production into an error at the point the mistake was made.
     */
    public function test_only_a_super_user_key_can_act_as_another_user(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only a super-user key can act as another user');

        $this->xenforo(new ApiKey('plain'))->actingAs(2);
    }

    /**
     * Every named accessor is the same thing as resource() with the class - they are
     * shorthand, not a second mechanism - so a mistyped one would be found here rather than
     * at runtime against a forum.
     *
     * @param  class-string<\Hampel\XenForo\Api\Endpoint\Endpoint>  $class
     */
    #[DataProvider('accessors')]
    public function test_a_named_accessor_is_endpoint_with_the_class(string $accessor, string $class): void
    {
        $xf = $this->xenforo();

        $this->assertInstanceOf($class, $xf->{$accessor}());
        $this->assertSame($xf->endpoint($class), $xf->{$accessor}());
    }

    /**
     * @return iterable<string, array{string, class-string<\Hampel\XenForo\Api\Endpoint\Endpoint>}>
     */
    public static function accessors(): iterable
    {
        $accessors = [
            'index' => Index::class,
            'auth' => Auth::class,
            'me' => Me::class,
            'users' => Users::class,
            'threads' => Threads::class,
            'posts' => Posts::class,
            'forums' => Forums::class,
            'nodes' => Nodes::class,
            'conversations' => Conversations::class,
            'conversationMessages' => ConversationMessages::class,
            'alerts' => Alerts::class,
            'oauth2' => OAuth2::class,
            'profilePosts' => ProfilePosts::class,
            'profilePostComments' => ProfilePostComments::class,
            'search' => Search::class,
            'searchForums' => SearchForums::class,
            'featured' => Featured::class,
            'stats' => Stats::class,
            'oembed' => OEmbed::class,
            'attachments' => Attachments::class,
            'media' => Media::class,
            'mediaAlbums' => MediaAlbums::class,
            'mediaCategories' => MediaCategories::class,
            'mediaComments' => MediaComments::class,
            'resources' => Resources::class,
            'resourceCategories' => ResourceCategories::class,
            'resourceReviews' => ResourceReviews::class,
            'resourceUpdates' => ResourceUpdates::class,
            'resourceVersions' => ResourceVersions::class,
        ];

        foreach ($accessors as $accessor => $class) {
            yield $accessor => [$accessor, $class];
        }
    }

    public function test_it_exposes_what_it_was_configured_with(): void
    {
        $xf = $this->xenforo();

        $this->assertSame('forum.example.com', $xf->config()->host());
        $this->assertSame('API key', $xf->authentication()->describe());
    }

    public function test_the_short_form_is_a_forum_a_key_and_a_transport(): void
    {
        $xf = Client::withKey('https://forum.example.com', 'secret', $this->client);

        $this->assertSame('forum.example.com', $xf->config()->host());
        $this->assertInstanceOf(ApiKey::class, $xf->authentication());
        $this->assertNull($xf->config()->version);

        $this->client->pushJson(200, ['me' => ['user_id' => 1]]);
        $xf->me()->get();

        $this->assertSame('secret', $this->client->lastRequest()->getHeaderLine('XF-Api-Key'));
        $this->assertSame('https://forum.example.com/api/me/', $this->sentUri());
    }
}
