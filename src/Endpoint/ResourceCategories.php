<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Generated\Schema\XFRM_Category;
use Hampel\XenForo\Api\Generated\Schema\XFRM_ResourceItem;
use Hampel\XenForo\Api\Result\Page;

/**
 * Resource categories - the `Resource categories` tag, from XenForo Resource Manager.
 *
 * The same tree shape as Nodes and MediaCategories, built by the same
 * \XF\Api\ControllerPlugin\CategoryTree. Writing needs a super-user key with the
 * `resourceManager` admin permission.
 *
 * A category decides which resource types may be created in it - `allow_local`,
 * `allow_external`, `allow_commercial_external`, `allow_fileless` - so a create refused by
 * Resources is often this rather than a permission.
 *
 * An add-on's endpoints, absent from a forum without XFRM - see Resources.
 */
final class ResourceCategories extends Endpoint
{
    /**
     * @return array{categories: list<XFRM_Category>, tree_map: array<mixed>}
     */
    public function list(): array
    {
        $response = $this->apiGet('resource-categories/');

        $categories = [];
        foreach ($response->array('categories') as $category) {
            if (is_array($category)) {
                $categories[] = XFRM_Category::fromArray($category);
            }
        }

        return ['categories' => $categories, 'tree_map' => $response->array('tree_map')];
    }

    /**
     * @return array<mixed>
     */
    public function flattened(): array
    {
        return $this->apiGet('resource-categories/flattened')->array('categories_flat');
    }

    public function get(int $categoryId): XFRM_Category
    {
        return XFRM_Category::fromArray(
            $this->apiGet('resource-categories/' . $categoryId . '/')->array('category')
        );
    }

    public function find(int $categoryId): ?XFRM_Category
    {
        return $this->apiFind(
            'resource-categories/' . $categoryId . '/',
            [],
            'category',
            XFRM_Category::fromArray(...)
        );
    }

    /**
     * @return Page<XFRM_ResourceItem>
     */
    public function resources(int $categoryId, int $page = 1): Page
    {
        return $this->apiPaginate(
            'resource-categories/' . $categoryId . '/resources',
            'resources',
            XFRM_ResourceItem::fromArray(...),
            $page
        );
    }

    /**
     * @return \Generator<int, XFRM_ResourceItem>
     */
    public function eachResource(int $categoryId): \Generator
    {
        yield from $this->apiEach(
            'resource-categories/' . $categoryId . '/resources',
            'resources',
            XFRM_ResourceItem::fromArray(...)
        );
    }

    /**
     * Create a category. `title` and `parent_category_id` are required - the root is 0.
     *
     * @param  array<string, mixed>  $payload  title, parent_category_id, description,
     *         display_order, allow_local, allow_external, allow_commercial_external,
     *         allow_fileless, enable_versioning, enable_support_url,
     *         always_moderate_create, always_moderate_update, min_tags, thread_node_id,
     *         thread_prefix_id, require_prefix, auto_feature
     */
    public function create(array $payload): XFRM_Category
    {
        return XFRM_Category::fromArray($this->apiPost('resource-categories/', $payload)->array('category'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(int $categoryId, array $payload): XFRM_Category
    {
        return XFRM_Category::fromArray(
            $this->apiPost('resource-categories/' . $categoryId . '/', $payload)->array('category')
        );
    }

    /**
     * @param  bool  $deleteChildren  delete the child categories too. False moves them up
     *                                to this category's parent, which is XenForo's default.
     */
    public function delete(int $categoryId, bool $deleteChildren = false): bool
    {
        return $this->apiDelete(
            'resource-categories/' . $categoryId . '/',
            ['delete_children' => $deleteChildren ? '1' : '0']
        )->isSuccess();
    }
}
