<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Utils;
use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Hampel\XenForo\Api\Upload;
use PHPUnit\Framework\Attributes\DataProvider;

final class UploadTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = tempnam(sys_get_temp_dir(), 'xf-upload-') . '.png';
        file_put_contents($this->path, 'PNGDATA');
    }

    protected function tearDown(): void
    {
        @unlink($this->path);

        parent::tearDown();
    }

    public function test_a_file_on_disk_is_named_after_itself(): void
    {
        $upload = Upload::fromPath($this->path);

        $this->assertSame(basename($this->path), $upload->filename);
        $this->assertSame('application/octet-stream', $upload->contentType);
        $this->assertSame('PNGDATA', (string) $upload->stream(new HttpFactory()));
    }

    public function test_a_file_on_disk_can_be_given_another_name(): void
    {
        $upload = Upload::fromPath($this->path, 'avatar.png', 'image/png');

        $this->assertSame('avatar.png', $upload->filename);
        $this->assertSame('image/png', $upload->contentType);
    }

    /**
     * Checked at construction rather than at send time, so the error names the path rather
     * than arriving as a stream failure from inside the request builder.
     */
    public function test_a_missing_file_is_refused_before_anything_is_sent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('there is no readable file there');

        Upload::fromPath($this->path . '.nope');
    }

    public function test_a_directory_is_not_a_file(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Upload::fromPath(sys_get_temp_dir());
    }

    /**
     * Nothing is read until the body is built, so an attachment larger than memory is
     * streamed rather than loaded - and a file that disappears in between fails then.
     */
    public function test_the_bytes_are_not_read_at_construction(): void
    {
        $upload = Upload::fromPath($this->path);

        file_put_contents($this->path, 'CHANGED');

        $this->assertSame('CHANGED', (string) $upload->stream(new HttpFactory()));
    }

    public function test_content_this_process_already_holds(): void
    {
        $upload = Upload::fromString('hello', 'note.txt', 'text/plain');

        $this->assertSame('note.txt', $upload->filename);
        $this->assertSame('text/plain', $upload->contentType);
        $this->assertSame('hello', (string) $upload->stream(new HttpFactory()));
    }

    public function test_an_already_open_stream_is_passed_through_untouched(): void
    {
        $stream = Utils::streamFor('streamed');

        $upload = Upload::fromStream($stream, 'note.txt');

        $this->assertSame($stream, $upload->stream(new HttpFactory()));
    }

    /**
     * A filename is written into a header, so the characters that would end one early are
     * refused rather than escaped or quietly stripped - an upload that succeeded under a
     * name the caller never chose is worse than an exception.
     */
    #[DataProvider('unsafeFilenames')]
    public function test_a_filename_that_would_break_the_header_is_refused(string $filename): void
    {
        $this->expectException(InvalidArgumentException::class);

        Upload::fromString('x', $filename);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsafeFilenames(): iterable
    {
        yield 'a quote' => ['my"file.png'];
        yield 'a carriage return' => ["my\rfile.png"];
        yield 'a line feed' => ["my\nfile.png"];
        yield 'a null byte' => ["my\0file.png"];
        yield 'nothing at all' => [''];
    }
}
