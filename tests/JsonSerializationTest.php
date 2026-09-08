<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Generated\Schema\User;
use Hampel\XenForo\Api\Result\ApiResponse;
use Hampel\XenForo\Api\Result\ResponseMeta;
use Hampel\XenForo\Api\Result\SiteInfo;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * An entity serialises to the payload it was built from, and to nothing else.
 *
 * Before this, json_encode() of an entity emitted every typed field - some sixty on a user,
 * most of them null - plus the whole payload again under `raw`. Reported by the Laravel
 * wrapper's author, who measured a seven-field user at over a kilobyte; but the size was
 * the lesser problem. XenForo OMITS a field the credential may not see rather than sending
 * it as null, which is why ApiResponse::has() exists, and serialising the typed fields
 * rendered every absent field as null - destroying downstream the one distinction the
 * package is careful about everywhere else.
 */
final class JsonSerializationTest extends BaseTestCase
{
    /** @var array<string, mixed> a realistic answer: seven fields, one an add-on's */
    private const PAYLOAD = [
        'user_id' => 1,
        'username' => 'sim',
        'message_count' => 42,
        'is_staff' => true,
        'register_date' => 1_700_000_000,
        'avatar_urls' => ['s' => 'https://forum.example.com/data/avatars/s/0/1.jpg'],
        'custom_addon_field' => 'kept',
    ];

    public function test_an_entity_serialises_to_its_payload_and_nothing_else(): void
    {
        $user = User::fromArray(self::PAYLOAD);

        $this->assertSame(json_encode(self::PAYLOAD), json_encode($user));
        $this->assertCount(7, json_decode((string) json_encode($user), true));
    }

    /**
     * The distinction that matters. `email` was not in the answer - the credential may not
     * see it - and it must not appear in the JSON as null, because downstream nothing could
     * tell that null from a null the forum actually sent.
     */
    public function test_a_field_the_credential_could_not_see_stays_absent(): void
    {
        $encoded = json_decode((string) json_encode(User::fromArray(self::PAYLOAD)), true);

        $this->assertArrayNotHasKey('email', $encoded);
        $this->assertArrayNotHasKey('user_state', $encoded);
        $this->assertArrayNotHasKey('raw', $encoded, 'The payload is the output, not a key within it.');
    }

    public function test_an_add_on_field_survives(): void
    {
        $encoded = json_decode((string) json_encode(User::fromArray(self::PAYLOAD)), true);

        $this->assertSame('kept', $encoded['custom_addon_field']);
    }

    /**
     * Serialising and rebuilding gives back an equal entity - which is what makes an entity
     * safe to cache or queue. The typed fields could not do this: stripping their nulls to
     * shrink them would be guessing which nulls meant absent, and the add-on field would be
     * gone regardless.
     */
    public function test_an_entity_round_trips(): void
    {
        $user = User::fromArray(self::PAYLOAD);

        $rebuilt = User::fromArray(json_decode((string) json_encode($user), true));

        $this->assertEquals($user, $rebuilt);
        $this->assertSame($user->raw, $rebuilt->raw);
    }

    public function test_a_hand_written_result_with_a_raw_payload_does_the_same(): void
    {
        $payload = ['site_title' => 'Example', 'version_id' => 2031270, 'key' => ['type' => 'super']];

        $this->assertSame(json_encode($payload), json_encode(SiteInfo::fromArray($payload)));
    }

    /**
     * ApiResponse serialises to the body. Status and version headers are about the
     * exchange, not the answer, and are not in it.
     */
    public function test_a_response_serialises_to_its_body(): void
    {
        $response = new ApiResponse(['user' => ['user_id' => 1]], 200, new ResponseMeta(1, 2));

        $this->assertSame('{"user":{"user_id":1}}', json_encode($response));
    }
}
