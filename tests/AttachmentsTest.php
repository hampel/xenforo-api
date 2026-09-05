<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Exception\RuntimeException;
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

    public function test_it_downloads_the_file_itself(): void
    {
        $this->client->pushRaw(200, 'PNGDATA', [
            'Content-Type' => 'image/png',
            'Content-Length' => '7',
            'Content-Disposition' => 'inline; filename="screenshot.png"',
        ]);

        $download = $this->xenforo()->attachments()->download(7, 'abc123');

        $this->assertSame('PNGDATA', $download->contents());
        $this->assertSame('screenshot.png', $download->filename);
        $this->assertSame('image/png', $download->contentType);
        $this->assertSame(7, $download->size);
        $this->assertSame('https://forum.example.com/api/attachments/7/data?key=abc123', $this->sentUri());
    }

    /**
     * The body of a download is not JSON, so nothing must try to decode it - and it must
     * not be read into a string on the way past either, since the whole point of the
     * separate path is an attachment larger than memory.
     */
    public function test_the_downloaded_body_is_handed_over_unread(): void
    {
        $this->client->pushRaw(200, 'PNGDATA', ['Content-Type' => 'image/png']);

        $download = $this->xenforo()->attachments()->download(7);

        $this->assertSame(0, $download->stream->tell(), 'The stream should not have been read yet.');
        $this->assertSame('PNGDATA', $download->contents());
    }

    public function test_a_download_can_go_straight_to_disk(): void
    {
        $this->client->pushRaw(200, str_repeat('x', 20000), ['Content-Type' => 'application/octet-stream']);

        $path = tempnam(sys_get_temp_dir(), 'xf-download-');

        try {
            $written = $this->xenforo()->attachments()->download(7)->saveTo($path);

            $this->assertSame(20000, $written);
            $this->assertSame(20000, filesize($path));
        } finally {
            @unlink($path);
        }
    }

    /**
     * A 304 or a redirect means the body is not the file. Returning an empty Download would
     * write an empty file and say nothing.
     */
    public function test_anything_but_a_200_on_a_download_is_refused(): void
    {
        $this->client->pushRaw(304, '', []);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('answered 304 rather than the attachment');

        $this->xenforo()->attachments()->download(7);
    }

    public function test_a_thumbnail_url_comes_out_of_the_redirect(): void
    {
        $this->client->pushRaw(301, '', ['Location' => 'https://forum.example.com/data/thumb/7.jpg']);

        $this->assertSame(
            'https://forum.example.com/data/thumb/7.jpg',
            $this->xenforo()->attachments()->thumbnailUrl(7)
        );

        $this->assertSame('https://forum.example.com/api/attachments/7/thumbnail', $this->sentUri());
    }

    public function test_the_retina_thumbnail_is_its_own_endpoint(): void
    {
        $this->client->pushRaw(301, '', ['Location' => 'https://forum.example.com/data/thumb/7@2x.jpg']);

        $this->assertSame(
            'https://forum.example.com/data/thumb/7@2x.jpg',
            $this->xenforo()->attachments()->retinaThumbnailUrl(7)
        );

        $this->assertSame(
            'https://forum.example.com/api/attachments/7/retina-thumbnail',
            $this->sentUri()
        );
    }

    public function test_an_attachment_with_no_thumbnail_answers_null(): void
    {
        $this->client->pushError(404, [['code' => 'not_found']]);

        $this->assertNull($this->xenforo()->attachments()->thumbnailUrl(7));
    }

    /**
     * Where the client followed the 301 there is no way to recover the URL from a PSR-7
     * response, so this says so rather than returning something invented.
     */
    public function test_a_followed_redirect_is_reported_rather_than_guessed_at(): void
    {
        $this->client->pushRaw(200, 'JPEGDATA', ['Content-Type' => 'image/jpeg']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('the HTTP client followed it');

        $this->xenforo()->attachments()->thumbnailUrl(7);
    }

    public function test_find_answers_null_where_get_would_raise(): void
    {
        $this->client->pushError(404, [['code' => 'not_found']]);

        $this->assertNull($this->xenforo()->attachments()->find(99));
    }
}
