<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint\DBTech;

use Hampel\XenForo\Api\Client;
use Hampel\XenForo\Api\Endpoint\DBTech\eCommerce\Categories;
use Hampel\XenForo\Api\Endpoint\DBTech\eCommerce\Downloads;
use Hampel\XenForo\Api\Endpoint\DBTech\eCommerce\eCommerceEndpoint;
use Hampel\XenForo\Api\Endpoint\DBTech\eCommerce\Identity;
use Hampel\XenForo\Api\Endpoint\DBTech\eCommerce\Licenses;
use Hampel\XenForo\Api\Endpoint\DBTech\eCommerce\Products;
use Hampel\XenForo\Api\Endpoint\DBTech\eCommerce\Reviews;

/**
 * DragonByte eCommerce's endpoint groups, under one name.
 *
 *     $identity = Identity::forBoard('https://customer.example.org', 'XenForo', '2.3.12');
 *
 *     $xf->ecommerce()->identifiedAs($identity)->products()->latestVersion($productId);
 *
 * eCommerce refuses a request that does not identify its board and platform, so
 * identifiedAs() is the usual first step - see Identity. Without it, each accessor is
 * Client::endpoint() with the class written out, so `$xf->endpoint(Products::class)` is the
 * same instance.
 */
final class eCommerce
{
    public function __construct(
        private readonly Client $client,
        private readonly ?Identity $identity = null,
    ) {
    }

    /**
     * The same groups, each sending this identity. A new object, leaving this one and the
     * instances Client::endpoint() shares unidentified.
     */
    public function identifiedAs(Identity $identity): self
    {
        return new self($this->client, $identity);
    }

    public function identity(): ?Identity
    {
        return $this->identity;
    }

    public function products(): Products
    {
        return $this->identify($this->client->endpoint(Products::class));
    }

    public function categories(): Categories
    {
        return $this->identify($this->client->endpoint(Categories::class));
    }

    public function downloads(): Downloads
    {
        return $this->identify($this->client->endpoint(Downloads::class));
    }

    public function licenses(): Licenses
    {
        return $this->identify($this->client->endpoint(Licenses::class));
    }

    public function reviews(): Reviews
    {
        return $this->identify($this->client->endpoint(Reviews::class));
    }

    /**
     * @template T of eCommerceEndpoint
     * @param  T  $endpoint
     * @return T
     */
    private function identify(eCommerceEndpoint $endpoint): eCommerceEndpoint
    {
        return $this->identity === null ? $endpoint : $endpoint->identifiedAs($this->identity);
    }
}
