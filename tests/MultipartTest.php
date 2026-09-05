<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use Hampel\XenForo\Api\Connection;
use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Hampel\XenForo\Api\Multipart;
use Hampel\XenForo\Api\Upload;

final class MultipartTest extends TestCase
{
    public function test_it_carries_its_boundary_in_the_content_type(): void
    {
        $multipart = new Multipart(['key' => 'abc']);

        $this->assertStringStartsWith('multipart/form-data; boundary="', $multipart->contentType());
        $this->assertStringContainsString($multipart->boundary, $multipart->contentType());
    }

    public function test_each_body_gets_its_own_boundary(): void
    {
        $this->assertNotSame((new Multipart())->boundary, (new Multipart())->boundary);
    }

    public function test_it_sends_fields_and_files_in_one_body(): void
    {
        $this->client->pushJson(200, ['attachment' => ['attachment_id' => 7]]);

        $this->connection()->postMultipart(
            'attachments/',
            ['key' => 'abc123'],
            ['attachment' => Upload::fromString('PNGDATA', 'screenshot.png', 'image/png')]
        );

        $parts = $this->sentParts();

        $this->assertSame('abc123', $parts->parts['key']['value']);
        $this->assertNull($parts->parts['key']['filename'], 'An ordinary field is not a file.');

        $this->assertSame('PNGDATA', $parts->parts['attachment']['value']);
        $this->assertSame('screenshot.png', $parts->parts['attachment']['filename']);
        $this->assertSame('image/png', $parts->parts['attachment']['type']);
    }

    /**
     * The invariant that matters, and the reason Payload does the naming for both
     * encodings: XenForo reads a multipart body and a form-encoded one through the same
     * filter() call, so the two have to arrive as the same $_POST.
     */
    public function test_a_multipart_body_reaches_php_as_the_same_input_a_form_body_would(): void
    {
        $payload = [
            'type' => 'post',
            'context' => ['thread_id' => 42, 'post_id' => null],
            'node_ids' => [2, 3],
            'custom_fields' => ['location' => 'Sydney NSW'],
            'notify' => true,
            'silent' => false,
        ];

        $this->client->pushJson(200, ['success' => true]);
        $this->connection()->post('threads/', $payload);
        $form = $this->sentBody();

        $this->client->pushJson(200, ['success' => true]);
        $this->connection()->postMultipart('threads/', $payload);
        $multipart = $this->sentParts()->asInput();

        $this->assertSame($form, $multipart);

        // and, so that a shared bug in both cannot pass unnoticed, the expected shape
        $this->assertSame([
            'type' => 'post',
            'context' => ['thread_id' => '42'],
            'node_ids' => ['2', '3'],
            'custom_fields' => ['location' => 'Sydney NSW'],
            'notify' => '1',
            'silent' => '0',
        ], $multipart);
    }

    /**
     * flatten() gets its part names and values by encoding the payload and decoding it
     * again, so anything the encoder escapes has to survive the round trip exactly - a `+`
     * that came back as a space would be a corrupted field nobody would see until a forum
     * stored it.
     */
    public function test_values_the_encoder_escapes_come_back_unchanged(): void
    {
        $payload = [
            'title' => 'a + b = c & more',
            'body' => "spaces  and	tabs and a % sign",
            'unicode' => 'Καλημέρα · 日本語',
            'brackets' => 'literal[0] and "quotes"',
        ];

        $this->client->pushJson(200, ['success' => true]);
        $this->connection()->postMultipart('threads/', $payload);

        $this->assertSame($payload, $this->sentParts()->asInput());
    }

    /**
     * A part's content is framed by the boundary, not escaped, so it may contain anything
     * at all - including the CRLFs and null bytes that would end a header.
     */
    public function test_binary_content_survives_intact(): void
    {
        $binary = "\x00\x01\r\n--not-the-boundary\r\n\xff\xfe" . random_bytes(64);

        $this->client->pushJson(200, ['success' => true]);

        $this->connection()->postMultipart(
            'attachments/',
            [],
            ['attachment' => Upload::fromString($binary, 'blob.bin')]
        );

        $this->assertSame($binary, $this->sentParts()->parts['attachment']['value']);
    }

    public function test_a_file_larger_than_one_chunk_is_written_whole(): void
    {
        $contents = str_repeat('abcdefgh', 4096); // 32KB, four times the copy chunk

        $this->client->pushJson(200, ['success' => true]);

        $this->connection()->postMultipart(
            'attachments/',
            [],
            ['attachment' => Upload::fromString($contents, 'big.txt')]
        );

        $this->assertSame($contents, $this->sentParts()->parts['attachment']['value']);
    }

    public function test_a_file_declares_octet_stream_when_it_is_not_told_otherwise(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->connection()->postMultipart('me/avatar', [], ['avatar' => Upload::fromString('x', 'me.jpg')]);

        $this->assertSame('application/octet-stream', $this->sentParts()->parts['avatar']['type']);
    }

    public function test_it_refuses_anything_that_is_not_an_upload(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The file "avatar" must be a Hampel\XenForo\Api\Upload, not string');

        new Multipart([], ['avatar' => '/tmp/avatar.jpg']);
    }

    public function test_it_refuses_a_file_key_that_would_break_out_of_the_header(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A multipart field name cannot contain a quote');

        new Multipart([], ['av"atar' => Upload::fromString('x', 'a.jpg')]);
    }

    public function test_it_refuses_a_field_whose_name_would_break_out_of_the_header(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A multipart field name cannot contain a quote');

        (new Multipart(['ti"tle' => 'Hello']))->stream(new HttpFactory());
    }

    public function test_an_empty_body_is_still_a_well_formed_one(): void
    {
        $body = (string) (new Multipart())->stream(new HttpFactory());

        $this->assertMatchesRegularExpression('/^--[^\r\n]+--\r\n$/', $body);
    }

    /**
     * The header this one MUST carry a parameter on, unlike the form-encoded body.
     */
    public function test_the_content_type_constant_is_the_bare_type(): void
    {
        $this->assertSame('multipart/form-data', Connection::MULTIPART_CONTENT_TYPE);
    }
}
