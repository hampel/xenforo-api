<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api;

use Hampel\XenForo\Api\Authentication\Authentication;
use Hampel\XenForo\Api\Authentication\SuperUserKey;
use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Hampel\XenForo\Api\Resource\Alerts;
use Hampel\XenForo\Api\Resource\Auth;
use Hampel\XenForo\Api\Resource\ConversationMessages;
use Hampel\XenForo\Api\Resource\Conversations;
use Hampel\XenForo\Api\Resource\Forums;
use Hampel\XenForo\Api\Resource\Index;
use Hampel\XenForo\Api\Resource\Me;
use Hampel\XenForo\Api\Resource\Nodes;
use Hampel\XenForo\Api\Resource\Posts;
use Hampel\XenForo\Api\Resource\Resource;
use Hampel\XenForo\Api\Resource\Search;
use Hampel\XenForo\Api\Resource\Threads;
use Hampel\XenForo\Api\Resource\Users;
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
 * The one to reach for when the endpoint is used more than once is a Resource subclass,
 * which this class will construct for you:
 *
 *     $xf->resource(UserFindCriteria::class)->byEmail($email);
 *
 * @see Resource for what a subclass gets and how to write one
 */
final class Client
{
    private readonly Connection $connection;

    /** @var array<class-string<Resource>, Resource> */
    private array $resources = [];

    public function __construct(
        private readonly Config $config,
        private readonly Authentication $authentication,
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        $this->connection = new Connection(
            $this->config,
            $this->authentication,
            $client,
            $requestFactory,
            $streamFactory,
            $this->logger
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
     * Any Resource subclass, constructed and memoised.
     *
     * This is the extension point. A third-party package ships a Resource subclass for the
     * endpoints its add-on adds, a consumer names the class, and static analysis follows
     * the return type through - there is nothing to register, no container and no string
     * keys. The named accessors below are the same mechanism with a shorter name.
     *
     * @template T of Resource
     * @param  class-string<T>  $class
     * @return T
     */
    public function resource(string $class): Resource
    {
        if (!isset($this->resources[$class])) {
            // Both halves earn their place. The first catches a class that is not a
            // Resource at all, which static analysis already rejects but a caller without
            // it can still write. The second catches Resource itself and any abstract
            // subclass - both of which satisfy class-string<Resource>, so nothing but this
            // stands between them and a fatal error on `new`.
            if (!is_subclass_of($class, Resource::class) || !(new \ReflectionClass($class))->isInstantiable()) {
                throw new InvalidArgumentException(sprintf(
                    '%s cannot be constructed as an API resource: it must be a concrete subclass of %s.',
                    $class,
                    Resource::class
                ));
            }

            $this->resources[$class] = new $class($this->connection, $this->logger);
        }

        /** @var T $resource */
        $resource = $this->resources[$class];

        return $resource;
    }

    public function index(): Index
    {
        return $this->resource(Index::class);
    }

    public function auth(): Auth
    {
        return $this->resource(Auth::class);
    }

    public function me(): Me
    {
        return $this->resource(Me::class);
    }

    public function users(): Users
    {
        return $this->resource(Users::class);
    }

    public function threads(): Threads
    {
        return $this->resource(Threads::class);
    }

    public function posts(): Posts
    {
        return $this->resource(Posts::class);
    }

    public function forums(): Forums
    {
        return $this->resource(Forums::class);
    }

    public function nodes(): Nodes
    {
        return $this->resource(Nodes::class);
    }

    public function conversations(): Conversations
    {
        return $this->resource(Conversations::class);
    }

    public function conversationMessages(): ConversationMessages
    {
        return $this->resource(ConversationMessages::class);
    }

    public function alerts(): Alerts
    {
        return $this->resource(Alerts::class);
    }

    public function search(): Search
    {
        return $this->resource(Search::class);
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
