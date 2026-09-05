<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

final class MediaCategoriesTest extends TestCase
{
    /**
     * The same tree shape Nodes answers with, because both come out of XenForo's own
     * CategoryTree plugin: a flat list plus the map describing its parentage, rather than
     * anything nested.
     */
    public function test_the_tree_comes_back_flat_with_its_parentage_beside_it(): void
    {
        $this->client->pushJson(200, [
            'categories' => [
                ['category_id' => 1, 'title' => 'Photos'],
                ['category_id' => 2, 'title' => 'Landscapes'],
            ],
            'tree_map' => ['0' => [1], '1' => [2]],
        ]);

        $tree = $this->xenforo()->mediaCategories()->list();

        $this->assertCount(2, $tree['categories']);
        $this->assertSame('Landscapes', $tree['categories'][1]->title);
        $this->assertSame(['0' => [1], '1' => [2]], $tree['tree_map']);
    }

    public function test_the_flattened_tree_is_returned_as_the_forum_sent_it(): void
    {
        $this->client->pushJson(200, ['categories_flat' => [
            ['category' => ['category_id' => 1], 'depth' => 0],
            ['category' => ['category_id' => 2], 'depth' => 1],
        ]]);

        $flat = $this->xenforo()->mediaCategories()->flattened();

        $this->assertCount(2, $flat);
        $this->assertSame('https://forum.example.com/api/media-categories/flattened', $this->sentUri());
    }

    /**
     * Albums and media share one pagination block, because XFMG pages them together as a
     * single list of content - so the albums are the albums on this page, not all of them.
     */
    public function test_a_categorys_content_pages_albums_and_media_together(): void
    {
        $this->client->pushJson(200, [
            'albums' => [['album_id' => 4]],
            'media' => [['media_id' => 1], ['media_id' => 2]],
            'pagination' => ['current_page' => 1, 'last_page' => 3, 'per_page' => 3, 'total' => 9],
        ]);

        $content = $this->xenforo()->mediaCategories()->content(2);

        $this->assertCount(1, $content['albums']);
        $this->assertCount(2, $content['media']);
        $this->assertTrue($content['media']->hasMore());
        $this->assertSame('https://forum.example.com/api/media-categories/2/content?page=1', $this->sentUri());
    }

    /**
     * False moves the children up rather than deleting them, and is sent explicitly rather
     * than omitted - the endpoint's own default is the same, but saying so leaves nothing
     * to a filter that reads an absent value as false by accident.
     */
    public function test_deleting_a_category_says_what_to_do_with_its_children(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->xenforo()->mediaCategories()->delete(2);
        $this->assertSame(
            'https://forum.example.com/api/media-categories/2/?delete_children=0',
            $this->sentUri()
        );

        $this->client->pushJson(200, ['success' => true]);

        $this->xenforo()->mediaCategories()->delete(2, deleteChildren: true);
        $this->assertSame(
            'https://forum.example.com/api/media-categories/2/?delete_children=1',
            $this->sentUri()
        );
    }
}
