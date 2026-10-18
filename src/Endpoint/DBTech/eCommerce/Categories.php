<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint\DBTech\eCommerce;

use Hampel\XenForo\Api\Generated\Schema\DBTech\eCommerce\Category;
use Hampel\XenForo\Api\Generated\Schema\DBTech\eCommerce\Product;
use Hampel\XenForo\Api\Result\Page;

/**
 * Product categories - from DragonByte eCommerce, a third-party add-on; see eCommerceEndpoint.
 *
 * The same tree shape as Nodes and ResourceCategories, built by the same
 * \XF\Api\ControllerPlugin\CategoryTree. Writing needs the `dbtechEcomCategory` admin
 * permission, so in practice a super-user key, and deleting needs
 * `dbtech_ecommerce_category:delete` - categories have no soft delete, so unlike the rest of
 * eCommerce a delete is not a write.
 */
final class Categories extends eCommerceEndpoint
{
    /**
     * @return array{categories: list<Category>, tree_map: array<mixed>}
     */
    public function list(): array
    {
        $response = $this->apiGet('dbtech-ecommerce/categories/');

        $categories = [];
        foreach ($response->array('categories') as $category) {
            if (is_array($category)) {
                $categories[] = Category::fromArray($category);
            }
        }

        return ['categories' => $categories, 'tree_map' => $response->array('tree_map')];
    }

    /**
     * @return array<mixed>
     */
    public function flattened(): array
    {
        return $this->apiGet('dbtech-ecommerce/categories/flattened')->array('categories_flat');
    }

    public function get(int $categoryId): Category
    {
        return Category::fromArray(
            $this->apiGet('dbtech-ecommerce/categories/' . $categoryId . '/')->array('category')
        );
    }

    public function find(int $categoryId): ?Category
    {
        return $this->apiFind(
            'dbtech-ecommerce/categories/' . $categoryId . '/',
            [],
            'category',
            Category::fromArray(...)
        );
    }

    /**
     * One page of a category's products. Needs `dbtech_ecommerce_product:read` too.
     *
     * Paginated from eCommerce 5.4.2. Before it this answered every product while its
     * pagination block claimed twenty a page, so eachProduct() against an older forum
     * yields the whole category once per page it believes there to be.
     *
     * @param  array<string, scalar|array<mixed>|null>  $filters  as Products::list()
     * @return Page<Product>
     */
    public function products(int $categoryId, int $page = 1, array $filters = []): Page
    {
        return $this->apiPaginate(
            'dbtech-ecommerce/categories/' . $categoryId . '/products',
            'products',
            Product::fromArray(...),
            $page,
            $filters
        );
    }

    /**
     * @param  array<string, scalar|array<mixed>|null>  $filters
     * @return \Generator<int, Product>
     */
    public function eachProduct(int $categoryId, array $filters = []): \Generator
    {
        yield from $this->apiEach(
            'dbtech-ecommerce/categories/' . $categoryId . '/products',
            'products',
            Product::fromArray(...),
            $filters
        );
    }

    /**
     * Create a category. `title` and `parent_category_id` are required - the root is 0.
     *
     * @param  array<string, mixed>  $payload  title, parent_category_id, description,
     *         display_order, enable_classifieds, always_moderate_create,
     *         always_moderate_update, thread_node_id, thread_prefix_id, require_prefix,
     *         min_tags, product_update_notify ('thread', 'reply')
     */
    public function create(array $payload): Category
    {
        return Category::fromArray($this->apiPost('dbtech-ecommerce/categories/', $payload)->array('category'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(int $categoryId, array $payload): Category
    {
        return Category::fromArray(
            $this->apiPost('dbtech-ecommerce/categories/' . $categoryId . '/', $payload)->array('category')
        );
    }

    /**
     * @param  bool  $deleteChildren  delete the child categories too. False moves them up
     *                                to this category's parent.
     */
    public function delete(int $categoryId, bool $deleteChildren = false): bool
    {
        return $this->apiDelete(
            'dbtech-ecommerce/categories/' . $categoryId . '/',
            ['delete_children' => $deleteChildren ? '1' : '0']
        )->isSuccess();
    }
}
