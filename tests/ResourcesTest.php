<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Endpoint\Resources;
use Hampel\XenForo\Api\Upload;

final class ResourcesTest extends TestCase
{
    public function test_it_pages_through_resources_with_filters(): void
    {
        $this->client->pushJson(200, [
            'resources' => [['resource_id' => 1], ['resource_id' => 2]],
            'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 2],
        ]);

        $page = $this->xenforo()->resources()->list(1, ['type' => 'local', 'creator_id' => 3]);

        $this->assertCount(2, $page);
        $this->assertSame(
            'https://forum.example.com/api/resources/?type=local&creator_id=3&page=1',
            $this->sentUri()
        );
    }

    /**
     * Versions are the one list here that is not paginated - XFRM returns the lot - so this
     * comes back as a plain array rather than a Page, and pretending otherwise would invent
     * a pagination block that is not there.
     */
    public function test_versions_come_back_as_a_plain_list(): void
    {
        $this->client->pushJson(200, ['versions' => [
            ['resource_version_id' => 1, 'version_string' => '1.0.0'],
            ['resource_version_id' => 2, 'version_string' => '1.1.0'],
        ]]);

        $versions = $this->xenforo()->resources()->versions(5);

        $this->assertCount(2, $versions);
        $this->assertSame('1.1.0', $versions[1]->version_string);
        $this->assertSame('https://forum.example.com/api/resources/5/versions', $this->sentUri());
    }

    public function test_updates_and_reviews_are_paginated(): void
    {
        $this->client->pushJson(200, [
            'updates' => [['resource_update_id' => 1]],
            'pagination' => ['current_page' => 1, 'last_page' => 2, 'per_page' => 1, 'total' => 2],
        ]);

        $this->assertTrue($this->xenforo()->resources()->updates(5)->hasMore());
        $this->assertSame('https://forum.example.com/api/resources/5/updates?page=1', $this->sentUri());

        $this->client->pushJson(200, [
            'reviews' => [['resource_rating_id' => 1]],
            'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
        ]);

        $this->assertCount(1, $this->xenforo()->resources()->reviews(5));
        $this->assertSame('https://forum.example.com/api/resources/5/reviews?page=1', $this->sentUri());
    }

    public function test_it_creates_a_local_resource_against_an_attachment_key(): void
    {
        $this->client->pushJson(200, ['success' => true, 'resource' => ['resource_id' => 5]]);

        $resource = $this->xenforo()->resources()->create([
            'resource_category_id' => 2,
            'title' => 'A library',
            'tag_line' => 'It does a thing',
            'description' => 'At greater length.',
            'resource_type' => 'local',
            'version_string' => '1.0.0',
            'version_attachment_key' => 'abc123',
        ]);

        $this->assertSame(5, $resource->resource_id);
        $this->assertSame('abc123', $this->sentBody()['version_attachment_key']);
        $this->assertSame(
            'application/x-www-form-urlencoded',
            $this->client->lastRequest()->getHeaderLine('Content-Type')
        );
    }

    public function test_featuring_a_resource_can_carry_an_image(): void
    {
        $this->client->pushJson(200, ['success' => true, 'feature' => ['featured_content_id' => 6]]);

        $this->xenforo()->resources()->feature(
            5,
            ['always_visible' => true],
            Upload::fromString('PNGDATA', 'banner.png', 'image/png')
        );

        $this->assertSame('banner.png', $this->sentParts()->parts['image']['filename']);
        $this->assertSame(['always_visible' => '1'], $this->sentParts()->asInput());
    }

    /**
     * The named accessor and the extension point are the same mechanism, so a class named
     * after its API tag is reachable both ways.
     */
    public function test_the_accessor_is_the_extension_point_under_a_shorter_name(): void
    {
        $xf = $this->xenforo();

        $this->assertInstanceOf(Resources::class, $xf->resources());
        $this->assertSame($xf->resources(), $xf->endpoint(Resources::class));
    }
}
