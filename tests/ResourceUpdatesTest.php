<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

final class ResourceUpdatesTest extends TestCase
{
    public function test_it_posts_an_update_against_a_resource(): void
    {
        $this->client->pushJson(200, ['success' => true, 'update' => ['resource_update_id' => 4]]);

        $update = $this->xenforo()->resourceUpdates()->create(5, 'Version 2', 'What changed.', 'abc123');

        $this->assertSame(4, $update->resource_update_id);
        $this->assertSame([
            'resource_id' => '5',
            'title' => 'Version 2',
            'message' => 'What changed.',
            'attachment_key' => 'abc123',
        ], $this->sentBody());
    }

    /**
     * A null attachment key is dropped rather than sent empty: XenForo reads an empty
     * string as a key that does not exist rather than as no key at all.
     */
    public function test_an_absent_attachment_key_is_not_sent(): void
    {
        $this->client->pushJson(200, ['success' => true, 'update' => ['resource_update_id' => 4]]);

        $this->xenforo()->resourceUpdates()->create(5, 'Version 2', 'What changed.');

        $this->assertArrayNotHasKey('attachment_key', $this->sentBody());
    }
}
