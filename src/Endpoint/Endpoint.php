<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Connection;
use Hampel\XenForo\Api\Exception\NotFoundException;
use Hampel\XenForo\Api\Result\ApiResponse;
use Hampel\XenForo\Api\Result\Page;
use Hampel\XenForo\Api\Upload;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The base class for everything that groups a set of endpoints - this package's own
 * resources, and anybody else's.
 *
 * EXTENDING THE API
 *
 * A XenForo forum's API is whatever its add-ons say it is. An add-on that extends
 * \XF\Api\Controller\UsersController with an actionGetFindCriteria() adds
 * `GET users/find-criteria` to that forum and to no other, and no amount of work in this
 * package can anticipate it. So the extension point is a class, not a registration:
 *
 *     final class UserFindCriteria extends Endpoint
 *     {
 *         public function byEmail(string $email): array
 *         {
 *             return $this->get('users/find-criteria', ['email' => $email])->array('user');
 *         }
 *     }
 *
 *     $xf->endpoint(UserFindCriteria::class)->byEmail('sim@example.com');
 *
 * There is nothing to register, nothing to boot and no container. The class IS the
 * registration, so a third-party package ships one, a consumer type-hints it, and static
 * analysis follows the return type all the way through. Client::endpoint() memoises by
 * class name, so repeated calls hand back the same instance.
 *
 * What subclassing buys over calling Connection directly is the pagination handling below.
 * Every list endpoint in XenForo - core, first-party add-on, third-party add-on - returns
 * the same pagination block, so paginate() and each() work for an endpoint this package
 * has never heard of.
 */
abstract class Endpoint
{
    public function __construct(
        protected readonly Connection $connection,
        protected readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * Every helper here carries an `api` prefix, which looks redundant inside a class whose
     * whole job is the API and is not. A resource wants to call its own methods get(),
     * post() and delete() - those are the natural names for "fetch a user", "create a
     * thread" - and PHP will not let a subclass redeclare an inherited method with a
     * different signature. Prefixing the inherited ones leaves those names free, for this
     * package's resources and for anybody else's.
     *
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    protected function apiGet(string $path, array $query = []): ApiResponse
    {
        return $this->connection->get($path, $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    protected function apiPost(string $path, array $payload = [], array $query = []): ApiResponse
    {
        return $this->connection->post($path, $payload, $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    protected function apiPut(string $path, array $payload = [], array $query = []): ApiResponse
    {
        return $this->connection->put($path, $payload, $query);
    }

    /**
     * A GET whose answer is not JSON - a file, or a redirect to one.
     *
     * The response comes back whole and unread, so a large attachment never becomes a PHP
     * string on the way past. Connection::sendRaw() has the detail, including why a
     * redirect counts as a success here and not in apiGet().
     *
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    protected function apiGetRaw(string $path, array $query = []): ResponseInterface
    {
        return $this->connection->getRaw($path, $query);
    }

    /**
     * Upload a file - an attachment, an avatar, a featured-content image.
     *
     * Separate from apiPost() rather than folded into it, because the difference is not
     * only how the body is encoded: multipart works on a POST and on nothing else, for
     * reasons upstream of XenForo. Connection::postMultipart() has the detail.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, Upload>  $files  keyed by the input name the endpoint reads
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    protected function apiUpload(string $path, array $payload, array $files, array $query = []): ApiResponse
    {
        return $this->connection->postMultipart($path, $payload, $files, $query);
    }

    /**
     * XenForo's delete endpoints take their arguments in the query string rather than the
     * body - `hard_delete`, `reason`, `rename_to` - so that is the argument order here.
     *
     * @param  array<string, scalar|array<mixed>|null>  $query
     * @param  array<string, mixed>  $payload
     */
    protected function apiDelete(string $path, array $query = [], array $payload = []): ApiResponse
    {
        return $this->connection->delete($path, $payload, $query);
    }

    /**
     * A lookup where "no such thing" is an ordinary answer rather than a failure.
     *
     * XenForo answers 404 for a record that is not there, for a record the acting user may
     * not see, and for a route that does not exist on this forum - an add-on endpoint that
     * is not installed. All three are indistinguishable here, which is worth knowing before
     * reading a null as "no such user".
     *
     * @template TItem
     * @param  array<string, scalar|array<mixed>|null>  $query
     * @param  callable(array<mixed>): TItem  $map
     * @return TItem|null
     */
    protected function apiFind(string $path, array $query, string $key, callable $map): mixed
    {
        try {
            $data = $this->apiGet($path, $query)->array($key);
        } catch (NotFoundException) {
            return null;
        }

        return $data === [] ? null : $map($data);
    }

    /**
     * The same lookup, handing back the whole response rather than one mapped key.
     *
     * For the endpoint whose answer is an envelope. Core XenForo mostly answers with a
     * single named object, which is what apiFind() is shaped for - but an add-on endpoint
     * is free to put three things at the top level, and often does: a user AND the URLs to
     * reach them by, a record AND the field it was matched on. Mapping one key would throw
     * the rest away, and the rest is frequently the point. Same 404-to-null rule as
     * apiFind(), same three indistinguishable causes.
     *
     * @param  array<string, scalar|array<mixed>|null>  $query
     */
    protected function apiFindResponse(string $path, array $query = []): ?ApiResponse
    {
        try {
            return $this->apiGet($path, $query);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * One page of a list endpoint.
     *
     * @template TItem
     * @param  string  $key  the response key holding the list, e.g. "users"
     * @param  callable(array<mixed>): TItem  $map
     * @param  array<string, scalar|array<mixed>|null>  $query
     * @return Page<TItem>
     */
    protected function apiPaginate(string $path, string $key, callable $map, int $page = 1, array $query = []): Page
    {
        $query['page'] = max(1, $page);

        return Page::fromResponse($this->apiGet($path, $query)->data, $key, $map);
    }

    /**
     * Every item across every page, fetched a page at a time and only as far as it is
     * consumed.
     *
     * Terminates on hasMore(), never on an empty page: XenForo answers `invalid_page`
     * rather than an empty list once you pass the end, so a loop waiting for emptiness
     * would end in an exception instead of a return. isEmpty() is checked as well, so a
     * page that claims a successor and then returns nothing stops the loop rather than
     * spinning on somebody else's bug.
     *
     * @template TItem
     * @param  callable(array<mixed>): TItem  $map
     * @param  array<string, scalar|array<mixed>|null>  $query
     * @return \Generator<int, TItem>
     */
    protected function apiEach(string $path, string $key, callable $map, array $query = []): \Generator
    {
        $page = 1;

        while (true) {
            $result = $this->apiPaginate($path, $key, $map, $page, $query);

            yield from $result->items;

            if (!$result->hasMore() || $result->isEmpty()) {
                return;
            }

            $page++;
        }
    }
}
