<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests\DBTech\eCommerce;

use Hampel\XenForo\Api\Endpoint\DBTech\eCommerce\Identity;
use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Hampel\XenForo\Api\Tests\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * eCommerce logs every API request before dispatching it, and the log refuses one missing
 * any of these four headers - so they have to ride on every kind of request the endpoints
 * make, not only the license reads they also filter.
 */
final class IdentityTest extends TestCase
{
    private const HEADERS = [
        'X-DragonByte-BoardUrl' => 'https://customer.example.org/community',
        'X-DragonByte-HttpHost' => 'customer.example.org',
        'X-DragonByte-Software' => 'XenForo',
        'X-DragonByte-SoftwareVersion' => '2.3.12',
    ];

    private static function identity(): Identity
    {
        return Identity::forBoard('https://customer.example.org/community', 'XenForo', '2.3.12');
    }

    private function assertIdentified(RequestInterface $request): void
    {
        foreach (self::HEADERS as $name => $value) {
            $this->assertSame($value, $request->getHeaderLine($name), $name);
        }
    }

    public function test_a_get_carries_the_identity_and_the_credential(): void
    {
        $this->client->pushJson(200, ['licenses' => []]);

        $this->xenforo()->ecommerce()->licenses()->identifiedAs(self::identity())->list();

        $this->assertIdentified($this->client->lastRequest());
        $this->assertSame('test-api-key', $this->client->lastRequest()->getHeaderLine('XF-Api-Key'));
    }

    /**
     * apiPaginate() and apiFind() go through apiGet(), so they carry it without saying so.
     */
    public function test_a_paginated_get_and_a_find_carry_the_identity(): void
    {
        $products = $this->xenforo()->ecommerce()->products()->identifiedAs(self::identity());

        $this->client->pushJson(200, ['downloads' => [], 'pagination' => ['current_page' => 1, 'last_page' => 1]]);
        $products->downloads(7);
        $this->assertIdentified($this->client->lastRequest());

        $this->client->pushError(404, [['code' => 'no_downloadable_version_found']]);
        $this->assertNull($products->latestVersion(7));
        $this->assertIdentified($this->client->lastRequest());
    }

    public function test_a_post_carries_the_identity_and_its_form_body(): void
    {
        $this->client->pushJson(200, ['success' => true, 'review' => ['product_rating_id' => 2]]);

        $this->xenforo()->ecommerce()->reviews()->identifiedAs(self::identity())->create(7, 5);

        $request = $this->client->lastRequest();

        $this->assertIdentified($request);
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertSame('7', $this->sentBody()['product_id']);
    }

    public function test_a_delete_carries_the_identity_and_its_query(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->xenforo()->ecommerce()->downloads()->identifiedAs(self::identity())->delete(3, false, 'Withdrawn');

        $this->assertIdentified($this->client->lastRequest());
        $this->assertSame(
            'https://forum.example.com/api/dbtech-ecommerce/downloads/3/?reason=Withdrawn',
            $this->sentUri()
        );
    }

    public function test_a_raw_download_carries_the_identity(): void
    {
        $this->client->pushRaw(200, 'ZIPDATA', ['Content-Type' => 'application/zip']);

        $this->xenforo()->ecommerce()->downloads()->identifiedAs(self::identity())->download(3, '2.3');

        $this->assertIdentified($this->client->lastRequest());
    }

    /**
     * Identifying the facade once identifies every group reached through it, which is the
     * point of it: no eCommerce request gets far without the identity.
     */
    public function test_identifying_the_facade_identifies_every_group(): void
    {
        $ecommerce = $this->xenforo()->ecommerce()->identifiedAs(self::identity());

        $this->assertEquals(self::identity(), $ecommerce->identity());

        foreach (['products', 'categories', 'downloads', 'licenses', 'reviews'] as $group) {
            $this->assertEquals(self::identity(), $ecommerce->{$group}()->identity(), $group);
        }

        $this->client->pushJson(200, ['tree_map' => [], 'categories' => []]);
        $ecommerce->categories()->list();

        $this->assertIdentified($this->client->lastRequest());
    }

    /**
     * Client::endpoint() hands every caller the same instance, so an identity set on it would
     * be every caller's. identifiedAs() is a copy, on the facade and on an endpoint alike.
     */
    public function test_the_shared_instances_are_left_alone(): void
    {
        $xf = $this->xenforo();

        $identified = $xf->ecommerce()->identifiedAs(self::identity());

        $this->assertNotSame($xf->ecommerce(), $identified);
        $this->assertNull($xf->ecommerce()->identity());
        $this->assertNotSame($xf->ecommerce()->licenses(), $identified->licenses());
        $this->assertNull($xf->ecommerce()->licenses()->identity());

        $this->client->pushJson(200, ['licenses' => []]);
        $xf->ecommerce()->licenses()->list();

        foreach (array_keys(self::HEADERS) as $name) {
            $this->assertFalse($this->client->lastRequest()->hasHeader($name), $name);
        }
    }

    public function test_for_board_takes_the_host_from_the_url(): void
    {
        $identity = Identity::forBoard('https://shop.example.net:8443/forum/', 'XenForo', '2.3.12');

        $this->assertSame('shop.example.net', $identity->httpHost);
        $this->assertSame('https://shop.example.net:8443/forum/', $identity->boardUrl);
    }

    /**
     * An empty value is a guaranteed refusal from the forum, so it is refused here, by name,
     * rather than sent to come back as four field names in a 400.
     */
    public function test_an_empty_value_is_refused_by_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('needs a softwareVersion');

        new Identity('https://customer.example.org', 'customer.example.org', 'XenForo', ' ');
    }

    public function test_a_board_url_without_a_host_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('customer.example.org has no host');

        Identity::forBoard('customer.example.org', 'XenForo', '2.3.12');
    }
}
