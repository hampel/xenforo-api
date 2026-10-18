<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint\DBTech\eCommerce;

use Hampel\XenForo\Api\Generated\Schema\DBTech\eCommerce\License;

/**
 * Licenses - from DragonByte eCommerce, a third-party add-on; see eCommerceEndpoint.
 *
 * The acting user's own licenses, read-only: `dbtech_ecommerce_license:read` and a
 * registered user. On a board that matches licenses to the board they are used on, only
 * the licenses for the identity's board appear - see Identity.
 */
final class Licenses extends eCommerceEndpoint
{
    /**
     * Every license the acting user holds, each with its Product and that product's
     * LatestVersion. Not paginated, and empty rather than an error when there are none
     * (from 5.4.2; before it, a 402).
     *
     * @param  array<string, scalar|array<mixed>|null>  $filters  category_ids[],
     *         platforms[] (product filter IDs)
     * @return list<License>
     */
    public function list(array $filters = []): array
    {
        return array_values(array_map(
            License::fromArray(...),
            array_filter($this->apiGet('dbtech-ecommerce/licenses/', $filters)->array('licenses'), 'is_array')
        ));
    }

    /**
     * A license by its key - the string a customer has, not the numeric license_id.
     */
    public function get(string $licenseKey): License
    {
        $key = rawurlencode($licenseKey);

        return License::fromArray($this->apiGet('dbtech-ecommerce/licenses/' . $key . '/')->array('license'));
    }

    public function find(string $licenseKey): ?License
    {
        $key = rawurlencode($licenseKey);

        return $this->apiFind('dbtech-ecommerce/licenses/' . $key . '/', [], 'license', License::fromArray(...));
    }
}
