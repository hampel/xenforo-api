<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests\DBTech\eCommerce;

use Hampel\XenForo\Api\Exception\NotPermittedException;
use Hampel\XenForo\Api\Exception\RuntimeException;
use Hampel\XenForo\Api\Tests\TestCase;

final class DownloadsTest extends TestCase
{
    public function test_it_releases_a_download_against_its_product(): void
    {
        $this->client->pushJson(200, ['success' => true, 'download' => ['download_id' => 3]]);

        $download = $this->xenforo()->ecommerce()->downloads()->create(7, [
            'download_type' => 'dbtech_ecommerce_external',
            'version_string' => '2.0.0',
            'handler_data' => ['download_url' => 'https://example.com/w.zip'],
        ]);

        $this->assertSame(3, $download->download_id);
        $this->assertSame('https://forum.example.com/api/dbtech-ecommerce/downloads/', $this->sentUri());
        $this->assertSame([
            'product_id' => '7',
            'download_type' => 'dbtech_ecommerce_external',
            'version_string' => '2.0.0',
            'handler_data' => ['download_url' => 'https://example.com/w.zip'],
        ], $this->sentBody());
    }

    public function test_a_download_carries_its_versions(): void
    {
        $this->client->pushJson(200, ['download' => [
            'download_id' => 3,
            'Versions' => [['download_version_id' => 1, 'product_version' => '2.3', 'product_version_type' => 'full']],
        ]]);

        $download = $this->xenforo()->ecommerce()->downloads()->get(3);

        $this->assertCount(1, $download->Versions);
        $this->assertSame('https://forum.example.com/api/dbtech-ecommerce/downloads/3/', $this->sentUri());
    }

    public function test_it_downloads_one_versions_file(): void
    {
        $this->client->pushRaw(200, 'ZIPDATA', [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="widget-2.0.0.zip"',
        ]);

        $file = $this->xenforo()->ecommerce()->downloads()->download(3, '2.3', 'full', 11);

        $this->assertSame('ZIPDATA', $file->contents());
        $this->assertSame('widget-2.0.0.zip', $file->filename);
        $this->assertSame(
            'https://forum.example.com/api/dbtech-ecommerce/downloads/3/download?product_version=2.3&product_version_type=full&file=11',
            $this->sentUri()
        );
    }

    /**
     * An external download answers with a redirect to wherever it is hosted. Saying so,
     * with the address, beats writing an empty file.
     */
    public function test_an_external_download_says_where_it_is(): void
    {
        $this->client->pushRaw(301, '', ['Location' => 'https://example.com/w.zip']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('pointing at https://example.com/w.zip');

        $this->xenforo()->ecommerce()->downloads()->download(3, '2.3');
    }

    /**
     * From 5.4.2 a refusal is a 403 - a permission, which is what it is - rather than the 402
     * it used to be. The reason is in the code either way.
     */
    public function test_a_refused_download_is_not_permitted(): void
    {
        $this->client->pushError(403, [['code' => 'license_does_not_permit_download']]);

        try {
            $this->xenforo()->ecommerce()->downloads()->download(3, '2.3');
            $this->fail('A refused download should raise.');
        } catch (NotPermittedException $e) {
            $this->assertTrue($e->hasCode('license_does_not_permit_download'));
        }
    }
}
