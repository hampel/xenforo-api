<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

final class ResourceCategoriesTest extends TestCase
{
    public function test_the_tree_comes_back_flat_with_its_parentage_beside_it(): void
    {
        $this->client->pushJson(200, [
            'categories' => [['resource_category_id' => 1, 'title' => 'Libraries']],
            'tree_map' => ['0' => [1]],
        ]);

        $tree = $this->xenforo()->resourceCategories()->list();

        $this->assertSame('Libraries', $tree['categories'][0]->title);
        $this->assertSame(['0' => [1]], $tree['tree_map']);
    }

    public function test_a_categorys_resources_are_paginated(): void
    {
        $this->client->pushJson(200, [
            'resources' => [['resource_id' => 5]],
            'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
        ]);

        $page = $this->xenforo()->resourceCategories()->resources(2);

        $this->assertCount(1, $page);
        $this->assertSame(
            'https://forum.example.com/api/resource-categories/2/resources?page=1',
            $this->sentUri()
        );
    }

    /**
     * A category decides which resource types may be created in it, so a create refused by
     * Resources is often this rather than a permission.
     */
    public function test_it_creates_a_category_with_its_type_permissions(): void
    {
        $this->client->pushJson(200, ['success' => true, 'category' => ['resource_category_id' => 2]]);

        $this->xenforo()->resourceCategories()->create([
            'title' => 'Libraries',
            'parent_category_id' => 0,
            'allow_local' => true,
            'allow_external' => false,
        ]);

        $this->assertSame([
            'title' => 'Libraries',
            'parent_category_id' => '0',
            'allow_local' => '1',
            'allow_external' => '0',
        ], $this->sentBody());
    }
}
