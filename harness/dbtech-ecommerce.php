<?php

/**
 * Exercise: read DragonByte eCommerce on a real forum - categories, products, a product's
 * downloads and latest version, and the acting user's licenses. Read-only.
 *
 * eCommerce is a third-party add-on, so the first thing this says is whether the key may
 * reach it at all. A forum without it, or with its API switched off, answers
 * `endpoint_not_found` for every path, and that stops the exercise with the reason.
 *
 * Every eCommerce request has to identify the board and platform it is on behalf of, or the
 * forum refuses it - see Identity. This exercise identifies as the forum itself unless told
 * otherwise, and prints what it sent:
 *
 *   XENFORO_ECOMMERCE_BOARD_URL         default: the board URL the forum's index reports
 *   XENFORO_ECOMMERCE_HTTP_HOST         default: that URL's host
 *   XENFORO_ECOMMERCE_SOFTWARE          default: XenForo
 *   XENFORO_ECOMMERCE_SOFTWARE_VERSION  default: the version the forum's index reports
 *
 * The board URL is also what a board that matches licenses to boards filters by, so on such a
 * board set it to the customer board whose licenses you mean to see.
 *
 * Needs XENFORO_URL and XENFORO_API_KEY. Licenses belong to a user, so a super-user key
 * wants XENFORO_API_USER as well. XENFORO_ECOMMERCE_PRODUCT_ID picks the product; without it
 * the first one listed is used.
 *
 * @var Hampel\Rig\Io $io
 */

use Hampel\XenForo\Api\Endpoint\DBTech\eCommerce\Identity;
use Hampel\XenForo\Api\Exception\EndpointNotFoundException;
use Hampel\XenForo\Api\Exception\ExceptionInterface;

require __DIR__ . '/lib/client.php';

$io->title('xenforo-api · dbtech-ecommerce');

$xf = harness_client($io);

/**
 * An environment variable, or the fallback when it is unset or empty.
 */
function harness_ecommerce_env(string $name, ?string $fallback): string
{
    $value = getenv($name);

    return is_string($value) && $value !== '' ? $value : (string) $fallback;
}

try {
    $info = $xf->index()->get();

    $boardUrl = harness_ecommerce_env('XENFORO_ECOMMERCE_BOARD_URL', $info->baseUrl);
    $host = parse_url($boardUrl, PHP_URL_HOST);

    $values = [
        'boardUrl' => $boardUrl,
        'httpHost' => harness_ecommerce_env('XENFORO_ECOMMERCE_HTTP_HOST', is_string($host) ? $host : null),
        'software' => harness_ecommerce_env('XENFORO_ECOMMERCE_SOFTWARE', 'XenForo'),
        'softwareVersion' => harness_ecommerce_env('XENFORO_ECOMMERCE_SOFTWARE_VERSION', $info->version()),
    ];

    $io->values([
        'forum' => $xf->config()->baseUri,
        'product:read' => $info->hasScope('dbtech_ecommerce_product:read'),
        'license:read' => $info->hasScope('dbtech_ecommerce_license:read'),
    ]);

    $io->line();
    $io->values([
        'board url' => $values['boardUrl'],
        'http host' => $values['httpHost'],
        'software' => $values['software'],
        'software version' => $values['softwareVersion'],
    ]);

    $missing = array_keys(array_filter($values, static fn (string $value): bool => $value === ''));

    if ($missing !== []) {
        $io->line();
        $io->error(sprintf(
            '✗ No %s to identify as: the forum did not report one. Set the XENFORO_ECOMMERCE_* variable for it.',
            implode(', ', $missing)
        ));

        exit(1);
    }

    $ecommerce = $xf->ecommerce()->identifiedAs(new Identity(...$values));

    $categories = $ecommerce->categories()->list();

    $io->line();
    $io->value('categories', count($categories['categories']));

    $products = $ecommerce->products()->list();

    $io->values([
        'products on page' => count($products),
        'product pages' => $products->lastPage,
        'products in total' => $products->total,
    ]);

    $productId = (int) (getenv('XENFORO_ECOMMERCE_PRODUCT_ID') ?: ($products->items[0]->product_id ?? 0));

    if ($productId !== 0) {
        $product = $ecommerce->products()->get($productId);
        $downloads = $ecommerce->products()->downloads($productId);
        $latest = $ecommerce->products()->latestVersion($productId);

        $io->line();
        $io->values([
            'product_id' => $product->product_id,
            'title' => $product->title,
            'category' => $product->Category?->title,
            'downloads in total' => $downloads->total,
            'latest version' => $latest === null ? '(none)' : $latest->version_string,
            'latest can_download' => $latest?->can_download,
        ]);

        $category = $product->product_category_id;

        if ($category !== null) {
            $inCategory = $ecommerce->categories()->products($category);

            // The 5.4.2 pagination fix, observed: a page never holds more than it says it may.
            $io->values([
                'products in its category' => $inCategory->total,
                'on the first page' => count($inCategory),
                'per page' => $inCategory->perPage,
            ]);

            if (count($inCategory) > $inCategory->perPage) {
                $io->warn('The category answered more products than a page holds - eCommerce older than 5.4.2.');
            }
        }
    } else {
        $io->warn('No products listed and XENFORO_ECOMMERCE_PRODUCT_ID is not set - skipping the product.');
    }

    try {
        $owned = $ecommerce->licenses()->list();

        $io->line();
        $io->value('licenses held', count($owned));

        foreach (array_slice($owned, 0, 5) as $license) {
            $io->value('  ' . $license->license_key, $license->Product?->title);
        }
    } catch (ExceptionInterface $e) {
        // A guest has no licenses to list, and eCommerce refuses rather than answering empty.
        $io->line();
        $io->warn('licenses: ' . $e->getMessage());
    }
} catch (EndpointNotFoundException $e) {
    $io->line();
    $io->error('✗ eCommerce is not answering on this forum: not installed, or its API is switched off.');
    $io->error($e->getMessage());

    exit(1);
} catch (ExceptionInterface $e) {
    $io->line();
    $io->error('✗ ' . $e::class);
    $io->error($e->getMessage());

    exit(1);
}

$io->line();
$io->success('✓ read eCommerce');
