<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Exception\RuntimeException;

final class ResourceVersionsTest extends TestCase
{
    public function test_it_releases_a_version_against_an_attachment_key(): void
    {
        $this->client->pushJson(200, ['success' => true, 'version' => ['resource_version_id' => 3]]);

        $version = $this->xenforo()->resourceVersions()->create(5, [
            'version_string' => '2.0.0',
            'version_attachment_key' => 'abc123',
        ]);

        $this->assertSame(3, $version->resource_version_id);
        $this->assertSame([
            'resource_id' => '5',
            'version_string' => '2.0.0',
            'version_attachment_key' => 'abc123',
        ], $this->sentBody());
    }

    /**
     * A version can hold several files, so the download names which one - the ids are in
     * `$version->files`.
     */
    public function test_it_downloads_one_of_a_versions_files(): void
    {
        $this->client->pushRaw(200, 'ZIPDATA', [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="library-2.0.0.zip"',
        ]);

        $download = $this->xenforo()->resourceVersions()->download(3, 11);

        $this->assertSame('ZIPDATA', $download->contents());
        $this->assertSame('library-2.0.0.zip', $download->filename);
        $this->assertSame(
            'https://forum.example.com/api/resource-versions/3/download?file=11',
            $this->sentUri()
        );
    }

    /**
     * A version whose download_url is set lives somewhere else entirely and XFRM answers
     * with a redirect rather than bytes. Saying so beats writing an empty file, or - if the
     * client followed the redirect - handing back whatever a third-party host served.
     */
    public function test_a_version_hosted_elsewhere_says_where_to_look(): void
    {
        $this->client->pushRaw(302, '', ['Location' => 'https://example.com/library-2.0.0.zip']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Version 3 is hosted elsewhere');

        $this->xenforo()->resourceVersions()->download(3, 11);
    }
}
