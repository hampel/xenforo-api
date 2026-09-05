<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Hampel\XenForo\Api\Exception\RuntimeException;
use Hampel\XenForo\Api\Result\Download;
use PHPUnit\Framework\Attributes\DataProvider;

final class DownloadTest extends TestCase
{
    public function test_it_reads_the_name_the_type_and_the_size_off_the_response(): void
    {
        $download = Download::fromResponse(new Response(200, [
            'Content-Type' => 'image/png',
            'Content-Length' => '7',
            'Content-Disposition' => 'inline; filename="screenshot.png"',
        ], 'PNGDATA'));

        $this->assertSame('screenshot.png', $download->filename);
        $this->assertSame('image/png', $download->contentType);
        $this->assertSame(7, $download->size);
    }

    /**
     * XenForo sends the type with an empty charset parameter - `image/png; ` - and a caller
     * asking what the file is does not want the parameter either way.
     */
    public function test_the_type_is_the_type_without_its_parameters(): void
    {
        $download = Download::fromResponse(new Response(200, ['Content-Type' => 'image/png; charset=UTF-8']));

        $this->assertSame('image/png', $download->contentType);
    }

    /**
     * RFC 6266: filename* is the one that can carry a name outside ASCII, so it wins where
     * both are present - which for XenForo is any filename with a byte above 0x7F.
     */
    public function test_the_utf8_filename_wins_where_both_are_sent(): void
    {
        $download = Download::fromResponse(new Response(200, [
            'Content-Disposition' => 'attachment; filename="rsum.pdf"; filename*=UTF-8\'\'r%C3%A9sum%C3%A9.pdf',
        ]));

        $this->assertSame('résumé.pdf', $download->filename);
    }

    #[DataProvider('absentNames')]
    public function test_a_response_that_names_nothing_answers_null(string $disposition): void
    {
        $download = Download::fromResponse(new Response(200, array_filter(['Content-Disposition' => $disposition])));

        $this->assertNull($download->filename);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function absentNames(): iterable
    {
        yield 'no header at all' => [''];
        yield 'no filename in it' => ['attachment'];
        yield 'an empty filename' => ['attachment; filename=""'];
    }

    public function test_a_response_without_a_length_answers_null_rather_than_zero(): void
    {
        $this->assertNull(Download::fromResponse(new Response(200, [], 'body'))->size);
    }

    public function test_it_writes_to_disk_a_chunk_at_a_time(): void
    {
        $contents = random_bytes(30000);
        $download = new Download(Utils::streamFor($contents));

        $path = tempnam(sys_get_temp_dir(), 'xf-download-');

        try {
            $this->assertSame(30000, $download->saveTo($path));
            $this->assertSame($contents, file_get_contents($path));
        } finally {
            @unlink($path);
        }
    }

    public function test_a_path_it_cannot_write_says_so(): void
    {
        $download = new Download(Utils::streamFor('x'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not open');

        $download->saveTo(sys_get_temp_dir() . '/no-such-directory-' . bin2hex(random_bytes(4)) . '/file.bin');
    }
}
