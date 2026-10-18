<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint\DBTech\eCommerce;

use Hampel\XenForo\Api\Generated\Schema\DBTech\eCommerce\Download;
use Hampel\XenForo\Api\Exception\RuntimeException;
use Hampel\XenForo\Api\Result\Download as DownloadedFile;

/**
 * Downloads - one release of a product - from DragonByte eCommerce, a third-party add-on;
 * see eCommerceEndpoint.
 *
 * A download holds one version per product version and type: a release of a plugin for two
 * platforms, in a full and a demo build, is one download with four versions. Listing is
 * against the product, Products::downloads().
 */
final class Downloads extends eCommerceEndpoint
{
    /**
     * Release a new download.
     *
     * `download_type` - `dbtech_ecommerce_attachment`, `dbtech_ecommerce_external` or
     * `dbtech_ecommerce_autogen` - decides what `handler_data[...]` has to carry, and both
     * it and `version_string` are required alongside.
     *
     * @param  array<string, mixed>  $payload  download_type, version_string, handler_data[...],
     *         change_log, release_notes, version_fields[...], change_log_attachment_key,
     *         release_notes_attachment_key, has_new_features, has_changed_features,
     *         has_bug_fixes, is_unstable, release_date
     */
    public function create(int $productId, array $payload): Download
    {
        return Download::fromArray(
            $this->apiPost('dbtech-ecommerce/downloads/', ['product_id' => $productId] + $payload)->array('download')
        );
    }

    /**
     * A download with its Product and its Versions.
     */
    public function get(int $downloadId): Download
    {
        return Download::fromArray($this->apiGet('dbtech-ecommerce/downloads/' . $downloadId . '/')->array('download'));
    }

    public function find(int $downloadId): ?Download
    {
        return $this->apiFind(
            'dbtech-ecommerce/downloads/' . $downloadId . '/',
            [],
            'download',
            Download::fromArray(...)
        );
    }

    /**
     * @param  array<string, mixed>  $payload  version_string, change_log, release_notes,
     *         version_fields[...], handler_data[...], change_log_attachment_key,
     *         release_notes_attachment_key, has_new_features, has_changed_features,
     *         has_bug_fixes, is_unstable, author_alert, author_alert_reason
     */
    public function update(int $downloadId, array $payload): Download
    {
        return Download::fromArray(
            $this->apiPost('dbtech-ecommerce/downloads/' . $downloadId . '/', $payload)->array('download')
        );
    }

    /**
     * A soft delete needs `dbtech_ecommerce_download:write`; a hard one
     * `dbtech_ecommerce_download:delete_hard` as well.
     *
     * @param  array<string, scalar|null>  $options  author_alert, author_alert_reason
     */
    public function delete(int $downloadId, bool $hard = false, ?string $reason = null, array $options = []): bool
    {
        return $this->apiDelete(
            'dbtech-ecommerce/downloads/' . $downloadId . '/',
            self::deleteQuery($hard, $reason, $options)
        )->isSuccess();
    }

    /**
     * Download the file of one of a download's versions.
     *
     * A full version needs one of the acting user's licenses to permit it; a refusal is a
     * NotPermittedException carrying `you_have_not_purchased_any_licenses_yet` or
     * `license_does_not_permit_download` (a 402 before eCommerce 5.4.2, so a ClientException
     * there). On a board that matches licenses to the board they are used on, the license
     * has to be for the identity's board - see Identity. A download is logged and watches the
     * product for the acting user, which is eCommerce's own behaviour.
     *
     * NOT EVERY VERSION HAS A FILE HERE. An external download answers with a redirect to
     * wherever it is hosted, and the Versions on get() already carry that `download_url`.
     *
     * @param  int|null  $fileId  which attachment, where an attachment version holds several
     */
    public function download(
        int $downloadId,
        string $productVersion,
        string $type = 'full',
        ?int $fileId = null,
    ): DownloadedFile {
        $response = $this->apiGetRaw('dbtech-ecommerce/downloads/' . $downloadId . '/download', array_filter([
            'product_version' => $productVersion,
            'product_version_type' => $type,
            'file' => $fileId,
        ], static fn ($value): bool => $value !== null));

        if ($response->getStatusCode() !== 200) {
            $location = $response->getHeaderLine('Location');

            throw new RuntimeException(sprintf(
                'Download %d is hosted elsewhere: the forum answered %d rather than the file%s.',
                $downloadId,
                $response->getStatusCode(),
                $location === '' ? '' : ', pointing at ' . $location
            ));
        }

        return DownloadedFile::fromResponse($response);
    }
}
