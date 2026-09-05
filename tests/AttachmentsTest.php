<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Upload;

final class AttachmentsTest extends TestCase
{
    public function test_it_creates_a_key_for_the_content_the_attachment_will_belong_to(): void
    {
        $this->client->pushJson(200, ['key' => 'abc123']);

        $result = $this->xenforo()->attachments()->newKey('post', ['thread_id' => 42]);

        $this->assertSame('abc123', $result['key']);
        $this->assertNull($result['attachment'], 'No file was sent, so there is nothing to describe.');

        $request = $this->client->lastRequest();

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://forum.example.com/api/attachments/new-key', (string) $request->getUri());
        $this->assertSame(
            ['type' => 'post', 'context' => ['thread_id' => '42']],
            $this->sentParts()->asInput()
        );
    }

    public function test_a_key_can_be_created_with_its_first_file(): void
    {
        $this->client->pushJson(200, [
            'key' => 'abc123',
            'attachment' => ['attachment_id' => 7, 'filename' => 'screenshot.png'],
        ]);

        $result = $this->xenforo()->attachments()->newKey(
            'post',
            ['thread_id' => 42],
            Upload::fromString('PNGDATA', 'screenshot.png', 'image/png')
        );

        $this->assertSame(7, $result['attachment']?->attachment_id);

        $parts = $this->sentParts();

        $this->assertSame('screenshot.png', $parts->parts['attachment']['filename']);
        $this->assertSame('PNGDATA', $parts->parts['attachment']['value']);
        $this->assertSame('post', $parts->parts['type']['value']);
    }

    public function test_it_uploads_against_an_existing_key(): void
    {
        $this->client->pushJson(200, ['attachment' => ['attachment_id' => 8, 'filename' => 'two.png']]);

        $attachment = $this->xenforo()->attachments()->upload('abc123', Upload::fromString('x', 'two.png'));

        $this->assertSame(8, $attachment->attachment_id);
        $this->assertSame('https://forum.example.com/api/attachments/', $this->sentUri());
        $this->assertSame('abc123', $this->sentParts()->parts['key']['value']);
    }

    public function test_it_lists_what_has_been_uploaded_against_a_key(): void
    {
        $this->client->pushJson(200, ['attachments' => [
            ['attachment_id' => 7],
            ['attachment_id' => 8],
        ]]);

        $attachments = $this->xenforo()->attachments()->list('abc123');

        $this->assertCount(2, $attachments);
        $this->assertSame([7, 8], array_map(static fn ($a): ?int => $a->attachment_id, $attachments));
        $this->assertSame('https://forum.example.com/api/attachments/?key=abc123', $this->sentUri());
    }

    /**
     * An attachment that has not been associated with content yet is invisible without the
     * key it was uploaded against - the forum has no other way to know the caller is the
     * one who uploaded it.
     */
    public function test_the_key_is_carried_through_to_reads_and_deletes(): void
    {
        $this->client->pushJson(200, ['attachment' => ['attachment_id' => 7]]);
        $this->xenforo()->attachments()->get(7, 'abc123');
        $this->assertSame('https://forum.example.com/api/attachments/7/?key=abc123', $this->sentUri());

        $this->client->pushJson(200, ['success' => true]);
        $this->assertTrue($this->xenforo()->attachments()->delete(7, 'abc123'));
        $this->assertSame('DELETE', $this->client->lastRequest()->getMethod());
        $this->assertSame('https://forum.example.com/api/attachments/7/?key=abc123', $this->sentUri());
    }

    public function test_find_answers_null_where_get_would_raise(): void
    {
        $this->client->pushError(404, [['code' => 'not_found']]);

        $this->assertNull($this->xenforo()->attachments()->find(99));
    }
}
