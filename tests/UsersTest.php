<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

final class UsersTest extends TestCase
{
    public function test_it_gets_a_user(): void
    {
        $this->client->pushJson(200, ['user' => [
            'user_id' => 4264,
            'username' => 'sim',
            'is_staff' => true,
            'message_count' => '1234',
            'register_date' => 1757030400,
        ]]);

        $user = $this->xenforo()->users()->get(4264);

        $this->assertSame('https://forum.example.com/api/users/4264/', $this->sentUri());
        $this->assertSame(4264, $user->user_id);
        $this->assertSame('sim', $user->username);
        $this->assertTrue($user->is_staff);
        $this->assertSame(1234, $user->message_count, 'A numeric string from the database is still an integer.');
    }

    /**
     * An add-on can add fields to any entity by extending its toApiResult(). Losing those
     * would make this package unusable against exactly the forums it is aimed at.
     */
    public function test_fields_the_specification_does_not_describe_survive_in_raw(): void
    {
        $this->client->pushJson(200, ['user' => [
            'user_id' => 1,
            'username' => 'sim',
            'custom_addon_field' => 'a value no specification mentions',
        ]]);

        $user = $this->xenforo()->users()->get(1);

        $this->assertSame('a value no specification mentions', $user->raw['custom_addon_field']);
    }

    /**
     * A quiet-verbosity result carries a fraction of the fields, and that is normal rather
     * than an error - the same endpoint returns more to a key that may see more.
     */
    public function test_absent_fields_are_null_rather_than_an_error(): void
    {
        $this->client->pushJson(200, ['user' => ['user_id' => 1, 'username' => 'sim']]);

        $user = $this->xenforo()->users()->get(1);

        $this->assertNull($user->email);
        $this->assertNull($user->is_staff);
    }

    public function test_it_finds_a_user_by_email(): void
    {
        $this->client->pushJson(200, ['user' => ['user_id' => 7]]);

        $user = $this->xenforo()->users()->findByEmail('sim@example.com');

        $this->assertSame(
            'https://forum.example.com/api/users/find-email?email=sim%40example.com',
            $this->sentUri()
        );
        $this->assertNotNull($user);
        $this->assertSame(7, $user->user_id);
    }

    public function test_finding_by_name_separates_the_exact_match_from_the_suggestions(): void
    {
        $this->client->pushJson(200, [
            'exact' => ['user_id' => 1, 'username' => 'sim'],
            'recommendations' => [['user_id' => 2, 'username' => 'simon'], ['user_id' => 3, 'username' => 'simple']],
        ]);

        $result = $this->xenforo()->users()->findByName('sim');

        $this->assertNotNull($result['exact']);
        $this->assertSame('sim', $result['exact']->username);
        $this->assertCount(2, $result['recommendations']);
        $this->assertSame('simon', $result['recommendations'][0]->username);
    }

    public function test_no_exact_match_is_null_rather_than_an_empty_entity(): void
    {
        $this->client->pushJson(200, ['exact' => [], 'recommendations' => []]);

        $result = $this->xenforo()->users()->findByName('nobody');

        $this->assertNull($result['exact']);
        $this->assertSame([], $result['recommendations']);
    }

    public function test_it_creates_a_user_with_nested_custom_fields(): void
    {
        $this->client->pushJson(200, ['success' => true, 'user' => ['user_id' => 99, 'username' => 'New']]);

        $user = $this->xenforo()->users()->create([
            'username' => 'New',
            'email' => 'new@example.com',
            'custom_fields' => ['location' => 'Sydney'],
        ]);

        $this->assertSame(99, $user->user_id);
        $this->assertSame('POST', $this->client->lastRequest()->getMethod());
        $this->assertSame([
            'username' => 'New',
            'email' => 'new@example.com',
            'custom_fields' => ['location' => 'Sydney'],
        ], $this->sentBody());
    }

    /**
     * XenForo takes an update as a POST, not a PUT. Getting that wrong is a 404 on a route
     * that exists.
     */
    public function test_an_update_is_a_post(): void
    {
        $this->client->pushJson(200, ['success' => true, 'user' => ['user_id' => 1]]);

        $this->xenforo()->users()->update(1, ['username' => 'Renamed']);

        $this->assertSame('POST', $this->client->lastRequest()->getMethod());
        $this->assertSame('https://forum.example.com/api/users/1/', $this->sentUri());
    }

    public function test_a_delete_can_keep_the_users_content_under_a_name(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->assertTrue($this->xenforo()->users()->delete(5, renameTo: 'Former member'));

        $this->assertSame('DELETE', $this->client->lastRequest()->getMethod());
        $this->assertSame(
            'https://forum.example.com/api/users/5/?rename_to=Former%20member',
            $this->sentUri()
        );
    }
}
