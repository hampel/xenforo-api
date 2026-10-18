<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests\DBTech\eCommerce;

use Hampel\XenForo\Api\Tests\TestCase;

final class CategoriesTest extends TestCase
{
    public function test_it_reads_the_category_tree(): void
    {
        $this->client->pushJson(200, [
            'tree_map' => ['0' => [1], '1' => [2]],
            'categories' => [['category_id' => 1, 'title' => 'Software'], ['category_id' => 2, 'title' => 'Plugins']],
        ]);

        $tree = $this->xenforo()->ecommerce()->categories()->list();

        $this->assertSame('Plugins', $tree['categories'][1]->title);
        $this->assertSame(['0' => [1], '1' => [2]], $tree['tree_map']);
        $this->assertSame('https://forum.example.com/api/dbtech-ecommerce/categories/', $this->sentUri());
    }

    /**
     * Before 5.4.2 this endpoint answered every product whatever the page, so walking it
     * fetched the whole category once per page. Two pages here are two different requests.
     */
    public function test_a_categorys_products_are_walked_a_page_at_a_time(): void
    {
        $this->client->pushJson(200, [
            'products' => [['product_id' => 1]],
            'pagination' => ['current_page' => 1, 'last_page' => 2, 'per_page' => 1, 'total' => 2],
        ]);
        $this->client->pushJson(200, [
            'products' => [['product_id' => 2]],
            'pagination' => ['current_page' => 2, 'last_page' => 2, 'per_page' => 1, 'total' => 2],
        ]);

        $ids = [];
        foreach ($this->xenforo()->ecommerce()->categories()->eachProduct(3, ['type' => 'free']) as $product) {
            $ids[] = $product->product_id;
        }

        $this->assertSame([1, 2], $ids);
        $this->assertSame(
            'https://forum.example.com/api/dbtech-ecommerce/categories/3/products?type=free&page=2',
            $this->sentUri()
        );
    }

    public function test_it_creates_a_category(): void
    {
        $this->client->pushJson(200, ['success' => true, 'category' => ['category_id' => 4, 'title' => 'Themes']]);

        $category = $this->xenforo()->ecommerce()->categories()->create([
            'title' => 'Themes',
            'parent_category_id' => 0,
        ]);

        $this->assertSame(4, $category->category_id);
        $this->assertSame('https://forum.example.com/api/dbtech-ecommerce/categories/', $this->sentUri());
        $this->assertSame(['title' => 'Themes', 'parent_category_id' => '0'], $this->sentBody());
    }

    public function test_a_delete_says_what_happens_to_the_children(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->assertTrue($this->xenforo()->ecommerce()->categories()->delete(4, true));
        $this->assertSame(
            'https://forum.example.com/api/dbtech-ecommerce/categories/4/?delete_children=1',
            $this->sentUri()
        );
    }
}
