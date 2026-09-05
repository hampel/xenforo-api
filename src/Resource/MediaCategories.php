<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Resource;

use Hampel\XenForo\Api\Generated\Schema\XFMG_Album;
use Hampel\XenForo\Api\Generated\Schema\XFMG_Category;
use Hampel\XenForo\Api\Generated\Schema\XFMG_MediaItem;
use Hampel\XenForo\Api\Result\Page;

/**
 * Media categories - the `Media categories` tag, from XenForo Media Gallery.
 *
 * The same tree shape as Nodes, built by the same \XF\Api\ControllerPlugin\CategoryTree,
 * so list() and flattened() answer here exactly as they do there. Writing needs a
 * super-user key with the `mediaGallery` admin permission - this is admin-panel data.
 *
 * An add-on's endpoints, absent from a forum without XFMG - see Media.
 */
final class MediaCategories extends Resource
{
    /**
     * The whole category tree: a flat list, plus the map describing its parentage.
     *
     * @return array{categories: list<XFMG_Category>, tree_map: array<mixed>}
     */
    public function list(): array
    {
        $response = $this->apiGet('media-categories/');

        $categories = [];
        foreach ($response->array('categories') as $category) {
            if (is_array($category)) {
                $categories[] = XFMG_Category::fromArray($category);
            }
        }

        return ['categories' => $categories, 'tree_map' => $response->array('tree_map')];
    }

    /**
     * The tree flattened into display order, each entry carrying its depth - what you want
     * to render a `<select>`.
     *
     * @return array<mixed>
     */
    public function flattened(): array
    {
        return $this->apiGet('media-categories/flattened')->array('categories_flat');
    }

    public function get(int $categoryId): XFMG_Category
    {
        return XFMG_Category::fromArray(
            $this->apiGet('media-categories/' . $categoryId . '/')->array('category')
        );
    }

    public function find(int $categoryId): ?XFMG_Category
    {
        return $this->apiFind(
            'media-categories/' . $categoryId . '/',
            [],
            'category',
            XFMG_Category::fromArray(...)
        );
    }

    /**
     * One page of a category's contents.
     *
     * Albums and media come back in separate keys sharing one pagination block, because
     * XFMG pages the two together as a single list of content - so the albums here are not
     * a complete list of the category's albums, they are the albums on this page.
     *
     * @return array{albums: list<XFMG_Album>, media: Page<XFMG_MediaItem>}
     */
    public function content(int $categoryId, int $page = 1): array
    {
        $response = $this->apiGet('media-categories/' . $categoryId . '/content', ['page' => max(1, $page)]);

        $albums = [];
        foreach ($response->array('albums') as $album) {
            if (is_array($album)) {
                $albums[] = XFMG_Album::fromArray($album);
            }
        }

        return [
            'albums' => $albums,
            'media' => Page::fromResponse($response->data, 'media', XFMG_MediaItem::fromArray(...)),
        ];
    }

    /**
     * Create a category. `title` and `parent_category_id` are required - the root is 0.
     *
     * @param  array<string, mixed>  $payload  title, parent_category_id, description,
     *         display_order, min_tags, category_type, allowed_types, auto_feature
     */
    public function create(array $payload): XFMG_Category
    {
        return XFMG_Category::fromArray($this->apiPost('media-categories/', $payload)->array('category'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(int $categoryId, array $payload): XFMG_Category
    {
        return XFMG_Category::fromArray(
            $this->apiPost('media-categories/' . $categoryId . '/', $payload)->array('category')
        );
    }

    /**
     * Delete a category.
     *
     * @param  bool  $deleteChildren  delete the child categories too. False moves them up
     *                                to this category's parent, which is XenForo's own
     *                                default and the safer one.
     */
    public function delete(int $categoryId, bool $deleteChildren = false): bool
    {
        return $this->apiDelete(
            'media-categories/' . $categoryId . '/',
            ['delete_children' => $deleteChildren ? '1' : '0']
        )->isSuccess();
    }
}
