<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Exception\RuntimeException;
use Hampel\XenForo\Api\Generated\Schema\XFRM_ResourceVersion;
use Hampel\XenForo\Api\Result\Download;

/**
 * Resource versions - the `Resource versions` tag, from XenForo Resource Manager.
 *
 * A version is one release of a resource. There is no list endpoint here and no update
 * endpoint either - versions are listed against their resource, Resources::versions(),
 * and a released version is not edited, it is superseded.
 *
 * An add-on's endpoints, absent from a forum without XFRM - see Resources.
 */
final class ResourceVersions extends Endpoint
{
    public function get(int $versionId): XFRM_ResourceVersion
    {
        return XFRM_ResourceVersion::fromArray(
            $this->apiGet('resource-versions/' . $versionId . '/')->array('version')
        );
    }

    public function find(int $versionId): ?XFRM_ResourceVersion
    {
        return $this->apiFind(
            'resource-versions/' . $versionId . '/',
            [],
            'version',
            XFRM_ResourceVersion::fromArray(...)
        );
    }

    /**
     * Release a new version of a resource.
     *
     * What the payload needs depends on the resource's type, the same way create() on
     * Resources does: a local resource wants a `version_attachment_key`, an external
     * one an `external_download_url`, a commercial external one that plus `currency` and
     * `external_purchase_url`.
     *
     * @param  array<string, mixed>  $payload  version_string, version_type,
     *         version_attachment_key, external_download_url, currency,
     *         external_purchase_url
     */
    public function create(int $resourceId, array $payload): XFRM_ResourceVersion
    {
        return XFRM_ResourceVersion::fromArray(
            $this->apiPost('resource-versions/', ['resource_id' => $resourceId] + $payload)->array('version')
        );
    }

    public function delete(int $versionId, bool $hard = false, ?string $reason = null): bool
    {
        return $this->apiDelete('resource-versions/' . $versionId . '/', array_filter([
            'hard_delete' => $hard ? '1' : null,
            'reason' => $reason,
        ], static fn ($value): bool => $value !== null))->isSuccess();
    }

    /**
     * Download one of a version's files.
     *
     * A version can hold more than one file, so `$fileId` names which - the ids are in
     * `$version->files`. Downloading also increments the resource's download counter, which
     * is the forum's own behaviour and not something this can ask it not to do.
     *
     * NOT EVERY VERSION HAS A FILE HERE. A version whose `download_url` is set lives
     * somewhere else entirely, and XFRM answers with a redirect to it rather than with
     * bytes - so check that field before calling this. Where it is set, the URL is the
     * answer and there is nothing for this method to return.
     */
    public function download(int $versionId, int $fileId): Download
    {
        $response = $this->apiGetRaw('resource-versions/' . $versionId . '/download', ['file' => $fileId]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(sprintf(
                'Version %d is hosted elsewhere: the forum answered %d rather than the file. Read '
                    . '`download_url` off the version - that URL is where the file actually is.',
                $versionId,
                $response->getStatusCode()
            ));
        }

        return Download::fromResponse($response);
    }
}
