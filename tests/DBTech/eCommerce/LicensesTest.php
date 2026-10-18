<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests\DBTech\eCommerce;

use Hampel\XenForo\Api\Tests\TestCase;

final class LicensesTest extends TestCase
{
    public function test_licenses_come_back_as_a_plain_list_with_their_product(): void
    {
        $this->client->pushJson(200, ['licenses' => [
            ['license_id' => 1, 'license_key' => 'ABC-123', 'Product' => ['product_id' => 7, 'title' => 'Widget']],
        ]]);

        $licenses = $this->xenforo()->ecommerce()->licenses()->list(['platforms' => ['xf2']]);

        $this->assertSame('Widget', $licenses[0]->Product?->title);
        $this->assertSame(
            'https://forum.example.com/api/dbtech-ecommerce/licenses/?platforms%5B0%5D=xf2',
            $this->sentUri()
        );
    }

    public function test_no_licenses_is_an_empty_list(): void
    {
        $this->client->pushJson(200, ['licenses' => []]);

        $this->assertSame([], $this->xenforo()->ecommerce()->licenses()->list());
    }

    /**
     * The route takes the key as a string segment. One with a slash in it would otherwise
     * reach a different path altogether.
     */
    public function test_a_license_is_found_by_its_key_encoded(): void
    {
        $this->client->pushJson(200, ['license' => ['license_key' => 'AB/C D']]);

        $license = $this->xenforo()->ecommerce()->licenses()->get('AB/C D');

        $this->assertSame('AB/C D', $license->license_key);
        $this->assertSame('https://forum.example.com/api/dbtech-ecommerce/licenses/AB%2FC%20D/', $this->sentUri());
    }

    public function test_an_unknown_key_is_null(): void
    {
        $this->client->pushError(404, [['code' => 'requested_page_not_found']]);

        $this->assertNull($this->xenforo()->ecommerce()->licenses()->find('nope'));
    }
}
