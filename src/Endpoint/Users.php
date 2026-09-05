<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Generated\Schema\ProfilePost;
use Hampel\XenForo\Api\Generated\Schema\User;
use Hampel\XenForo\Api\Result\Page;
use Hampel\XenForo\Api\Upload;

/**
 * Users - the `Users` tag in the XenForo API documentation.
 *
 * Most of what is here needs more than a guest key. Reading the member list requires the
 * forum to have one enabled and the acting user to be allowed to see it; creating, updating
 * and deleting users requires a super-user key. A key without the standing gets a 403 whose
 * error code names what it lacked, which is worth reading rather than assuming the user
 * does not exist.
 */
final class Users extends Endpoint
{
    /**
     * One page of the member list, alphabetically.
     *
     * @return Page<User>
     */
    public function list(int $page = 1): Page
    {
        return $this->apiPaginate('users/', 'users', User::fromArray(...), $page);
    }

    /**
     * Every user, a page at a time and only as far as it is consumed.
     *
     * @return \Generator<int, User>
     */
    public function each(): \Generator
    {
        yield from $this->apiEach('users/', 'users', User::fromArray(...));
    }

    public function get(int $userId): User
    {
        return User::fromArray($this->apiGet('users/' . $userId . '/')->array('user'));
    }

    /**
     * The same user, or null if there is no such user - or the acting credential may not
     * see them, which looks identical from here.
     */
    public function find(int $userId): ?User
    {
        return $this->apiFind('users/' . $userId . '/', [], 'user', User::fromArray(...));
    }

    /**
     * A user together with one page of their profile posts, in a single call.
     *
     * @return array{user: User, profile_posts: Page<ProfilePost>}
     */
    public function getWithProfilePosts(int $userId, int $page = 1): array
    {
        $response = $this->apiGet('users/' . $userId . '/', [
            'with_posts' => true,
            'page' => max(1, $page),
        ]);

        return [
            'user' => User::fromArray($response->array('user')),
            'profile_posts' => Page::fromResponse($response->data, 'profile_posts', ProfilePost::fromArray(...)),
        ];
    }

    /**
     * Find a user by their exact email address. Super-user keys only.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->apiFind('users/find-email', ['email' => $email], 'user', User::fromArray(...));
    }

    /**
     * Find a user by an exact username, or by a prefix of one.
     *
     * The endpoint answers both questions at once: `exact` is the user whose name matches
     * exactly, `recommendations` are the ones it is a prefix of. Both can be empty, and a
     * username shorter than two characters gets neither.
     *
     * @return array{exact: User|null, recommendations: list<User>}
     */
    public function findByName(string $username): array
    {
        $response = $this->apiGet('users/find-name', ['username' => $username]);

        $exact = $response->array('exact');

        $recommendations = [];
        foreach ($response->array('recommendations') as $user) {
            if (is_array($user)) {
                $recommendations[] = User::fromArray($user);
            }
        }

        return [
            'exact' => $exact === [] ? null : User::fromArray($exact),
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Create a user. Super-user keys only.
     *
     * The payload is passed through rather than modelled, and that is deliberate: the
     * fields XenForo accepts are its own plus every custom field the forum has defined,
     * plus whatever an add-on has added to the user data writer. A fixed signature here
     * would be a list of the fields one forum happened to have. Nested arrays are sent as
     * PHP bracket notation, which is what XenForo's parse_str() reads:
     *
     *     $users->create([
     *         'username' => 'Example',
     *         'email' => 'example@example.com',
     *         'password' => $password,
     *         'custom_fields' => ['location' => 'Sydney'],
     *     ]);
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): User
    {
        return User::fromArray($this->apiPost('users/', $payload)->array('user'));
    }

    /**
     * Update a user. Takes the same fields as create().
     *
     * @param  array<string, mixed>  $payload
     */
    public function update(int $userId, array $payload): User
    {
        return User::fromArray($this->apiPost('users/' . $userId . '/', $payload)->array('user'));
    }

    /**
     * Delete a user. Super-user keys only.
     *
     * @param  string|null  $renameTo  keep the user's content under this name rather than
     *                                 letting XenForo attribute it to a guest
     */
    public function delete(int $userId, ?string $renameTo = null): bool
    {
        return $this->apiDelete(
            'users/' . $userId . '/',
            $renameTo === null ? [] : ['rename_to' => $renameTo]
        )->isSuccess();
    }

    /**
     * @return Page<ProfilePost>
     */
    public function profilePosts(int $userId, int $page = 1): Page
    {
        return $this->apiPaginate(
            'users/' . $userId . '/profile-posts',
            'profile_posts',
            ProfilePost::fromArray(...),
            $page
        );
    }

    /**
     * Replace a user's avatar. Needs a super-user key, or a key acting as that user.
     *
     * As Me::uploadAvatar(): the filename's extension is what the forum judges, not the
     * content type the upload declares.
     */
    public function uploadAvatar(int $userId, Upload $avatar): bool
    {
        return $this->apiUpload('users/' . $userId . '/avatar', [], ['avatar' => $avatar])->isSuccess();
    }

    public function deleteAvatar(int $userId): bool
    {
        return $this->apiDelete('users/' . $userId . '/avatar')->isSuccess();
    }
}
