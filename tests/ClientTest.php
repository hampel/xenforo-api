<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Authentication\ApiKey;
use Hampel\XenForo\Api\Authentication\SuperUserKey;
use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Hampel\XenForo\Api\Resource\Alerts;
use Hampel\XenForo\Api\Resource\Attachments;
use Hampel\XenForo\Api\Resource\Auth;
use Hampel\XenForo\Api\Resource\ConversationMessages;
use Hampel\XenForo\Api\Resource\Conversations;
use Hampel\XenForo\Api\Resource\Forums;
use Hampel\XenForo\Api\Resource\Index;
use Hampel\XenForo\Api\Resource\Me;
use Hampel\XenForo\Api\Resource\Media;
use Hampel\XenForo\Api\Resource\MediaAlbums;
use Hampel\XenForo\Api\Resource\MediaCategories;
use Hampel\XenForo\Api\Resource\MediaComments;
use Hampel\XenForo\Api\Resource\Nodes;
use Hampel\XenForo\Api\Resource\OAuth2;
use Hampel\XenForo\Api\Resource\Posts;
use Hampel\XenForo\Api\Resource\ProfilePostComments;
use Hampel\XenForo\Api\Resource\ProfilePosts;
use Hampel\XenForo\Api\Resource\ResourceCategories;
use Hampel\XenForo\Api\Resource\ResourceItems;
use Hampel\XenForo\Api\Resource\ResourceReviews;
use Hampel\XenForo\Api\Resource\ResourceUpdates;
use Hampel\XenForo\Api\Resource\ResourceVersions;
use Hampel\XenForo\Api\Resource\Search;
use Hampel\XenForo\Api\Resource\Threads;
use Hampel\XenForo\Api\Resource\Users;
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
     * @param  class-string<\Hampel\XenForo\Api\Resource\Resource>  $class
     */
    #[DataProvider('accessors')]
    public function test_a_named_accessor_is_resource_with_the_class(string $accessor, string $class): void
    {
        $xf = $this->xenforo();

        $this->assertInstanceOf($class, $xf->{$accessor}());
        $this->assertSame($xf->resource($class), $xf->{$accessor}());
    }

    /**
     * @return iterable<string, array{string, class-string<\Hampel\XenForo\Api\Resource\Resource>}>
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
            'attachments' => Attachments::class,
            'media' => Media::class,
            'mediaAlbums' => MediaAlbums::class,
            'mediaCategories' => MediaCategories::class,
            'mediaComments' => MediaComments::class,
            'resourceItems' => ResourceItems::class,
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
}
