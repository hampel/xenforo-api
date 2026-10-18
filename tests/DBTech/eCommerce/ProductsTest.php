<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests\DBTech\eCommerce;

use Hampel\XenForo\Api\Exception\EndpointNotFoundException;
use Hampel\XenForo\Api\Generated\Schema\User;
use Hampel\XenForo\Api\Tests\TestCase;

final class ProductsTest extends TestCase
{
    public function test_it_pages_through_products_with_filters(): void
    {
        $this->client->pushJson(200, [
            'products' => [['product_id' => 1], ['product_id' => 2]],
            'pagination' => ['current_page' => 1, 'last_page' => 3, 'per_page' => 2, 'total' => 6],
        ]);

        $page = $this->xenforo()->ecommerce()->products()->list(1, ['type' => 'paid', 'order' => 'title']);

        $this->assertCount(2, $page);
        $this->assertTrue($page->hasMore());
        $this->assertSame(
            'https://forum.example.com/api/dbtech-ecommerce/?type=paid&order=title&page=1',
            $this->sentUri()
        );
    }

    /**
     * The entity refers to core ones - a product's owner is XenForo's own User - and those
     * come back as the core classes, not as arrays.
     */
    public function test_a_product_carries_its_category_and_owner(): void
    {
        $this->client->pushJson(200, ['product' => [
            'product_id' => 7,
            'title' => 'Widget',
            'rating_avg' => 4.5,
            'tags' => ['tools'],
            'Category' => ['category_id' => 2, 'title' => 'Plugins'],
            'User' => ['user_id' => 1, 'username' => 'Admin'],
        ]]);

        $product = $this->xenforo()->ecommerce()->products()->get(7);

        $this->assertSame('Widget', $product->title);
        $this->assertSame(4.5, $product->rating_avg);
        $this->assertSame(['tools'], $product->tags);
        $this->assertSame('Plugins', $product->Category?->title);
        $this->assertInstanceOf(User::class, $product->User);
        $this->assertSame('Admin', $product->User->username);
        $this->assertSame('https://forum.example.com/api/dbtech-ecommerce/7/', $this->sentUri());
    }

    public function test_all_fetches_every_product_in_one_request(): void
    {
        $this->client->pushJson(200, [
            'products' => [['product_id' => 1], ['product_id' => 2], ['product_id' => 3]],
            'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 3],
        ]);

        $products = $this->xenforo()->ecommerce()->products()->all(['creator_id' => 4]);

        $this->assertCount(3, $products);
        $this->assertCount(1, $this->client->requests);
        $this->assertSame('https://forum.example.com/api/dbtech-ecommerce/?all=1&creator_id=4', $this->sentUri());
    }

    /**
     * From 5.4.2 having no licenses is an empty list, not an error, so there is nothing
     * here to catch.
     */
    public function test_purchased_products_are_a_plain_list_and_may_be_empty(): void
    {
        $this->client->pushJson(200, ['products' => []]);

        $this->assertSame([], $this->xenforo()->ecommerce()->products()->purchased(['category_ids' => [1, 2]]));
        $this->assertSame(
            'https://forum.example.com/api/dbtech-ecommerce/purchased?category_ids%5B0%5D=1&category_ids%5B1%5D=2',
            $this->sentUri()
        );
    }

    public function test_downloads_are_paginated_and_filtered_by_version(): void
    {
        $this->client->pushJson(200, [
            'downloads' => [['download_id' => 3, 'version_string' => '2.0.0']],
            'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
        ]);

        $page = $this->xenforo()->ecommerce()->products()->downloads(7, 1, ['product_version_type' => 'full']);

        $this->assertSame('2.0.0', $page->items[0]->version_string);
        $this->assertSame(
            'https://forum.example.com/api/dbtech-ecommerce/7/downloads?product_version_type=full&page=1',
            $this->sentUri()
        );
    }

    /**
     * The one camelCase key in the API, which is how a mistyped key would show up: a null
     * where a download should be.
     */
    public function test_the_latest_version_is_read_from_its_camel_case_key(): void
    {
        $this->client->pushJson(200, ['latestVersion' => ['download_id' => 9, 'version_string' => '3.1.0']]);

        $latest = $this->xenforo()->ecommerce()->products()->latestVersion(7, '2.3', 'full');

        $this->assertSame('3.1.0', $latest?->version_string);
        $this->assertSame(
            'https://forum.example.com/api/dbtech-ecommerce/7/latest-version?product_version=2.3&product_version_type=full',
            $this->sentUri()
        );
    }

    public function test_no_latest_version_is_null(): void
    {
        $this->client->pushError(404, [['code' => 'no_downloadable_version_found']]);

        $this->assertNull($this->xenforo()->ecommerce()->products()->latestVersion(7));
        $this->assertSame('https://forum.example.com/api/dbtech-ecommerce/7/latest-version', $this->sentUri());
    }

    /**
     * From 5.4.2 a board with eCommerce's API switched off answers as a forum without the
     * add-on does, which is configuration and so is never dressed up as "no such product".
     */
    public function test_an_api_switched_off_is_not_a_missing_product(): void
    {
        $this->client->pushError(404, [['code' => 'endpoint_not_found']]);

        $this->expectException(EndpointNotFoundException::class);

        $this->xenforo()->ecommerce()->products()->find(7);
    }

    public function test_reviews_are_paginated(): void
    {
        $this->client->pushJson(200, [
            'reviews' => [['product_rating_id' => 1, 'rating' => 5]],
            'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
        ]);

        $this->assertSame(5, $this->xenforo()->ecommerce()->products()->reviews(7)->items[0]->rating);
        $this->assertSame('https://forum.example.com/api/dbtech-ecommerce/7/reviews?page=1', $this->sentUri());
    }

    public function test_an_update_is_a_post_to_the_product(): void
    {
        $this->client->pushJson(200, ['success' => true, 'product' => ['product_id' => 7, 'title' => 'Gadget']]);

        $product = $this->xenforo()->ecommerce()->products()->update(7, [
            'title' => 'Gadget',
            'add_tags' => ['new'],
        ]);

        $this->assertSame('Gadget', $product->title);
        $this->assertSame('POST', $this->client->lastRequest()->getMethod());
        $this->assertSame(['title' => 'Gadget', 'add_tags' => ['new']], $this->sentBody());
    }

    public function test_a_hard_delete_names_itself_in_the_query(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->assertTrue($this->xenforo()->ecommerce()->products()->delete(7, true, 'Spam'));
        $this->assertSame('DELETE', $this->client->lastRequest()->getMethod());
        $this->assertSame(
            'https://forum.example.com/api/dbtech-ecommerce/7/?hard_delete=1&reason=Spam',
            $this->sentUri()
        );
    }
}
