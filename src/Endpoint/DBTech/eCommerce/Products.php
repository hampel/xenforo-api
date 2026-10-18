<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint\DBTech\eCommerce;

use Hampel\XenForo\Api\Generated\Schema\DBTech\eCommerce\Download;
use Hampel\XenForo\Api\Generated\Schema\DBTech\eCommerce\Product;
use Hampel\XenForo\Api\Generated\Schema\DBTech\eCommerce\ProductRating;
use Hampel\XenForo\Api\Result\Page;

/**
 * Products - from DragonByte eCommerce, a third-party add-on; see eCommerceEndpoint.
 *
 * Everything here needs `dbtech_ecommerce_product:read` or `:write`, and the lists hanging
 * off a product need a second scope as well - `dbtech_ecommerce_download:read` for its
 * downloads, `dbtech_ecommerce_rating:read` for its reviews.
 */
final class Products extends eCommerceEndpoint
{
    /**
     * One page of products. Featured products always sort first.
     *
     * @param  array<string, scalar|array<mixed>|null>  $filters  prefix_id, type ('free',
     *         'paid'), creator_id, platforms[] (product filter IDs), order ('last_update',
     *         'creation_date', 'rating_weighted', 'title'), direction
     * @return Page<Product>
     */
    public function list(int $page = 1, array $filters = []): Page
    {
        return $this->apiPaginate('dbtech-ecommerce/', 'products', Product::fromArray(...), $page, $filters);
    }

    /**
     * @param  array<string, scalar|array<mixed>|null>  $filters
     * @return \Generator<int, Product>
     */
    public function each(array $filters = []): \Generator
    {
        yield from $this->apiEach('dbtech-ecommerce/', 'products', Product::fromArray(...), $filters);
    }

    /**
     * Every product in one request - eCommerce's `all` flag, which skips paging.
     *
     * @param  array<string, scalar|array<mixed>|null>  $filters  as list()
     * @return list<Product>
     */
    public function all(array $filters = []): array
    {
        return self::products($this->apiGet('dbtech-ecommerce/', ['all' => '1'] + $filters)->array('products'));
    }

    /**
     * The products the acting user holds a license for - not paginated, and empty rather
     * than an error when there are none (from 5.4.2; before it, a 402). Needs
     * `dbtech_ecommerce_license:read` too, and a registered user.
     *
     * @param  array<string, scalar|array<mixed>|null>  $filters  category_ids[],
     *         platforms[], and the filters list() takes
     * @return list<Product>
     */
    public function purchased(array $filters = []): array
    {
        return self::products($this->apiGet('dbtech-ecommerce/purchased', $filters)->array('products'));
    }

    public function get(int $productId): Product
    {
        return Product::fromArray($this->apiGet('dbtech-ecommerce/' . $productId . '/')->array('product'));
    }

    public function find(int $productId): ?Product
    {
        return $this->apiFind('dbtech-ecommerce/' . $productId . '/', [], 'product', Product::fromArray(...));
    }

    /**
     * One page of a product's downloads, newest first. Empty when no version matches the
     * filters (from 5.4.2; before it, a 404 `no_downloadable_version_found`).
     *
     * @param  array<string, scalar|array<mixed>|null>  $filters  product_version,
     *         product_version_type ('full', 'demo')
     * @return Page<Download>
     */
    public function downloads(int $productId, int $page = 1, array $filters = []): Page
    {
        return $this->apiPaginate(
            'dbtech-ecommerce/' . $productId . '/downloads',
            'downloads',
            Download::fromArray(...),
            $page,
            $filters
        );
    }

    /**
     * @param  array<string, scalar|array<mixed>|null>  $filters
     * @return \Generator<int, Download>
     */
    public function eachDownload(int $productId, array $filters = []): \Generator
    {
        yield from $this->apiEach(
            'dbtech-ecommerce/' . $productId . '/downloads',
            'downloads',
            Download::fromArray(...),
            $filters
        );
    }

    /**
     * The newest download, optionally of one product version or version type - with its
     * Product. Null when there is none, and when the product is not there either; both are a
     * 404 to eCommerce. (Before 5.4.2 a product with no downloads at all answered a 500.)
     */
    public function latestVersion(int $productId, ?string $productVersion = null, ?string $type = null): ?Download
    {
        return $this->apiFind(
            'dbtech-ecommerce/' . $productId . '/latest-version',
            array_filter([
                'product_version' => $productVersion,
                'product_version_type' => $type,
            ], static fn ($value): bool => $value !== null),
            'latestVersion',
            Download::fromArray(...)
        );
    }

    /**
     * @return Page<ProductRating>
     */
    public function reviews(int $productId, int $page = 1): Page
    {
        return $this->apiPaginate(
            'dbtech-ecommerce/' . $productId . '/reviews',
            'reviews',
            ProductRating::fromArray(...),
            $page
        );
    }

    /**
     * @return \Generator<int, ProductRating>
     */
    public function eachReview(int $productId): \Generator
    {
        yield from $this->apiEach(
            'dbtech-ecommerce/' . $productId . '/reviews',
            'reviews',
            ProductRating::fromArray(...)
        );
    }

    /**
     * There is no create: eCommerce makes products in its admin control panel only.
     *
     * @param  array<string, mixed>  $payload  title, tagline, description, description_full,
     *         product_specification, copyright_info, requirements (comma-separated),
     *         prefix_id, is_listed, is_discountable, has_demo, product_fields[...],
     *         product_filters[], add_tags[], remove_tags[], attachment_key,
     *         description_attachment_key, specification_attachment_key,
     *         copyright_attachment_key, author_alert, author_alert_reason, and the rest the
     *         specification lists
     */
    public function update(int $productId, array $payload): Product
    {
        return Product::fromArray(
            $this->apiPost('dbtech-ecommerce/' . $productId . '/', $payload)->array('product')
        );
    }

    /**
     * A soft delete needs `dbtech_ecommerce_product:write`; a hard one
     * `dbtech_ecommerce_product:delete_hard` as well.
     *
     * @param  array<string, scalar|null>  $options  author_alert, author_alert_reason
     */
    public function delete(int $productId, bool $hard = false, ?string $reason = null, array $options = []): bool
    {
        return $this->apiDelete(
            'dbtech-ecommerce/' . $productId . '/',
            self::deleteQuery($hard, $reason, $options)
        )->isSuccess();
    }

    /**
     * @param  array<mixed>  $items
     * @return list<Product>
     */
    private static function products(array $items): array
    {
        return array_values(array_map(Product::fromArray(...), array_filter($items, 'is_array')));
    }
}
