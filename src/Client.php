<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api;

use Hampel\XenForo\Api\Authentication\ApiKey;
use Hampel\XenForo\Api\Authentication\Authentication;
use Hampel\XenForo\Api\Authentication\SuperUserKey;
use Hampel\XenForo\Api\Endpoint\Alerts;
use Hampel\XenForo\Api\Endpoint\Attachments;
use Hampel\XenForo\Api\Endpoint\Auth;
use Hampel\XenForo\Api\Endpoint\ConversationMessages;
use Hampel\XenForo\Api\Endpoint\Conversations;
use Hampel\XenForo\Api\Endpoint\Endpoint;
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
use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Hampel\XenForo\Api\Support\Psr17Discovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The entry point. Hand it a forum, a credential and any PSR-18 client:
 *
 *     $guzzle  = new GuzzleHttp\Client();
 *     $factory = new GuzzleHttp\Psr7\HttpFactory();   // PSR-17, both roles
 *
 *     $xf = new Client(
 *         new Config('https://forum.example.com'),
 *         new ApiKey($key),
 *         $guzzle, $factory, $factory
 *     );
 *
 *     $thread = $xf->threads()->get(1234);
 *
 * EXTENDING IT
 *
 * A XenForo forum's API is whatever its add-ons say it is, so this class cannot be the
 * complete list of what a given forum offers - and any design where it tried would be
 * wrong on the first forum that installed something. There are two ways past it, and
 * neither needs a release of this package.
 *
 * The quick one is connection(), which will call anything:
 *
 *     $xf->connection()->get('users/find-criteria', ['email' => $email])->data;
 *
 * The one to reach for when the endpoint is used more than once is a Endpoint subclass,
 * which this class will construct for you:
 *
 *     $xf->endpoint(UserFindCriteria::class)->byEmail($email);
 *
 * @see Endpoint for what a subclass gets and how to write one
 */
final class Client
{
    private readonly Connection $connection;

    /** @var array<class-string<Endpoint>, Endpoint> */
    private array $endpoints = [];

    /**
     * @param  RequestFactoryInterface|null  $requestFactory  PSR-17. Leave both null and the
     *         package finds one - Guzzle's, Nyholm's or Diactoros', whichever is installed;
     *         see Psr17Discovery. Pass them to choose, or when none of those is present.
     */
    public function __construct(
        private readonly Config $config,
        private readonly Authentication $authentication,
        ClientInterface $client,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        if ($requestFactory === null || $streamFactory === null) {
            [$foundRequest, $foundStream] = Psr17Discovery::find();

            $requestFactory ??= $foundRequest;
            $streamFactory ??= $foundStream;
        }

        $this->connection = new Connection(
            $this->config,
            $this->authentication,
            $client,
            $requestFactory,
            $streamFactory,
            $this->logger
        );
    }

    /**
     * The short form: a forum, an API key, and a transport.
     *
     * Everything the long constructor takes is still available on it; this exists because
     * naming a Config and an ApiKey to accept both defaults is ceremony, and ceremony in an
     * example is what gets copied. Named after the credential, as elsewhere: a SuperUserKey,
     * a BearerToken or ClientCredentials takes more than a string, so those keep the long
     * form, and withCredential() gets from this client to one of them.
     *
     * @param  string  $baseUri  the board URL or the API URL, as Config takes it
     */
    public static function withKey(
        string $baseUri,
        #[\SensitiveParameter] string $key,
        ClientInterface $client,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?LoggerInterface $logger = null,
    ): self {
        return new self(
            new Config($baseUri),
            new ApiKey($key),
            $client,
            $requestFactory,
            $streamFactory,
            $logger ?? new NullLogger()
        );
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function authentication(): Authentication
    {
        return $this->authentication;
    }

    /**
     * The same forum and transport, acting as a different user.
     *
     * Only meaningful for a super-user key, which is the only credential XenForo lets act
     * as somebody else. Returns a new client rather than mutating this one, so a request
     * made on behalf of one user cannot leak into the next - which matters most in exactly
     * the situation this method exists for, a loop over users in a long-running process.
     */
    public function actingAs(?int $userId): self
    {
        if (!$this->authentication instanceof SuperUserKey) {
            throw new InvalidArgumentException(sprintf(
                'Only a super-user key can act as another user; this client is using %s.',
                $this->authentication->describe()
            ));
        }

        return new self(
            $this->config,
            $this->authentication->actingAs($userId),
            $this->connection->client(),
            $this->connection->requestFactory(),
            $this->connection->streamFactory(),
            $this->logger
        );
    }

    /**
     * The same forum and transport, under a different credential.
     *
     * What the OAuth2 flow ends with: the client that exchanged the code holds no key of
     * its own, and the token it now has belongs to a user rather than to the integration.
     *
     *     $token = $xf->oauth2()->exchangeCode($clientId, $code, $redirectUri, $secret);
     *     $asUser = $xf->withCredential($token->credential());
     *
     * A new client rather than a mutation, for the reason actingAs() gives: two credentials
     * in one long-running process must not be able to leak into each other's requests.
     * Resources are not carried over - they hold the connection this one is replacing.
     */
    public function withCredential(Authentication $authentication): self
    {
        return new self(
            $this->config,
            $authentication,
            $this->connection->client(),
            $this->connection->requestFactory(),
            $this->connection->streamFactory(),
            $this->logger
        );
    }

    /**
     * Any Endpoint subclass, constructed and memoised.
     *
     * This is the extension point. A third-party package ships a Endpoint subclass for the
     * endpoints its add-on adds, a consumer names the class, and static analysis follows
     * the return type through - there is nothing to register, no container and no string
     * keys. The named accessors below are the same mechanism with a shorter name.
     *
     * @template T of Endpoint
     * @param  class-string<T>  $class
     * @return T
     */
    public function endpoint(string $class): Endpoint
    {
        if (!isset($this->endpoints[$class])) {
            // Both halves earn their place. The first catches a class that is not a
            // Endpoint at all, which static analysis already rejects but a caller without
            // it can still write. The second catches Endpoint itself and any abstract
            // subclass - both of which satisfy class-string<Endpoint>, so nothing but this
            // stands between them and a fatal error on `new`.
            if (!is_subclass_of($class, Endpoint::class) || !(new \ReflectionClass($class))->isInstantiable()) {
                throw new InvalidArgumentException(sprintf(
                    '%s cannot be constructed as an API resource: it must be a concrete subclass of %s.',
                    $class,
                    Endpoint::class
                ));
            }

            $this->endpoints[$class] = new $class($this->connection, $this->logger);
        }

        /** @var T $endpoint */
        $endpoint = $this->endpoints[$class];

        return $endpoint;
    }

    public function index(): Index
    {
        return $this->endpoint(Index::class);
    }

    public function auth(): Auth
    {
        return $this->endpoint(Auth::class);
    }

    public function me(): Me
    {
        return $this->endpoint(Me::class);
    }

    public function users(): Users
    {
        return $this->endpoint(Users::class);
    }

    public function threads(): Threads
    {
        return $this->endpoint(Threads::class);
    }

    public function posts(): Posts
    {
        return $this->endpoint(Posts::class);
    }

    public function forums(): Forums
    {
        return $this->endpoint(Forums::class);
    }

    public function nodes(): Nodes
    {
        return $this->endpoint(Nodes::class);
    }

    public function conversations(): Conversations
    {
        return $this->endpoint(Conversations::class);
    }

    public function conversationMessages(): ConversationMessages
    {
        return $this->endpoint(ConversationMessages::class);
    }

    public function alerts(): Alerts
    {
        return $this->endpoint(Alerts::class);
    }

    public function attachments(): Attachments
    {
        return $this->endpoint(Attachments::class);
    }

    /**
     * The OAuth2 token endpoints - exchanging a code, refreshing, introspecting, revoking.
     *
     * These need no credential of their own, so this is reachable from a client built with
     * a Guest, which is how an integration that has no key yet gets its first token.
     */
    public function oauth2(): OAuth2
    {
        return $this->endpoint(OAuth2::class);
    }

    public function profilePosts(): ProfilePosts
    {
        return $this->endpoint(ProfilePosts::class);
    }

    public function profilePostComments(): ProfilePostComments
    {
        return $this->endpoint(ProfilePostComments::class);
    }

    public function search(): Search
    {
        return $this->endpoint(Search::class);
    }

    public function searchForums(): SearchForums
    {
        return $this->endpoint(SearchForums::class);
    }

    public function featured(): Featured
    {
        return $this->endpoint(Featured::class);
    }

    public function stats(): Stats
    {
        return $this->endpoint(Stats::class);
    }

    public function oembed(): OEmbed
    {
        return $this->endpoint(OEmbed::class);
    }

    /**
     * XenForo Media Gallery, if the forum has it.
     *
     * XFMG and XFRM below are add-ons rather than core, and every endpoint behind these
     * accessors answers 404 on a forum without them - see the resource classes. They are
     * wrapped here as a convenience, not as a promise: this is the same extension mechanism
     * described on Client::resource(), and these classes are what a third party's own
     * Endpoint subclass would look like.
     */
    public function media(): Media
    {
        return $this->endpoint(Media::class);
    }

    public function mediaAlbums(): MediaAlbums
    {
        return $this->endpoint(MediaAlbums::class);
    }

    public function mediaCategories(): MediaCategories
    {
        return $this->endpoint(MediaCategories::class);
    }

    public function mediaComments(): MediaComments
    {
        return $this->endpoint(MediaComments::class);
    }

    /**
     * XenForo Resource Manager's resources, if the forum has it.
     */
    public function resources(): Resources
    {
        return $this->endpoint(Resources::class);
    }

    public function resourceCategories(): ResourceCategories
    {
        return $this->endpoint(ResourceCategories::class);
    }

    public function resourceReviews(): ResourceReviews
    {
        return $this->endpoint(ResourceReviews::class);
    }

    public function resourceUpdates(): ResourceUpdates
    {
        return $this->endpoint(ResourceUpdates::class);
    }

    public function resourceVersions(): ResourceVersions
    {
        return $this->endpoint(ResourceVersions::class);
    }

    /**
     * For an endpoint nothing here wraps - an add-on's, or one added to XenForo after this
     * release. Call it directly rather than waiting for a version of this package.
     */
    public function connection(): Connection
    {
        return $this->connection;
    }
}
