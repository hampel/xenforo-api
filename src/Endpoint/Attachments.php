<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Exception\NotFoundException;
use Hampel\XenForo\Api\Exception\RuntimeException;
use Hampel\XenForo\Api\Generated\Schema\Attachment;
use Hampel\XenForo\Api\Result\Download;
use Hampel\XenForo\Api\Upload;
use Psr\Http\Message\ResponseInterface;

/**
 * Attachments - the `Attachments` tag in the XenForo API documentation.
 *
 * THE ORDER THIS HAPPENS IN
 *
 * An attachment cannot be posted with the content it belongs to. It is uploaded first,
 * against a key, and the key is then handed to whatever creates the content:
 *
 *     $key = $xf->attachments()->newKey('post', ['thread_id' => 42])['key'];
 *     $xf->attachments()->upload($key, Upload::fromPath('/tmp/screenshot.png'));
 *     $xf->posts()->create(42, 'See attached', $key);
 *
 * The `context` a key is created with is not decoration: it is what the forum checks the
 * acting user's permission against, and it must describe the content the attachment will
 * end up on. A `post` key wants the thread_id it will be replying to, or the post_id it
 * will be editing. Get it wrong and the key is refused at creation rather than at use.
 *
 * A key's attachments are temporary until the content is created. XenForo's daily cleanup
 * removes unassociated attachments, so an upload that is never used costs nothing
 * permanent - which is what makes this safe to exercise against a real forum.
 *
 * THE THREE THAT DO NOT ANSWER IN JSON go through Connection::sendRaw() instead, which
 * hands back the response whole rather than decoding it - download() below, and the two
 * thumbnail methods, which have a caveat of their own worth reading before you use them.
 */
final class Attachments extends Endpoint
{
    /**
     * Every attachment uploaded against a key and not yet associated with content.
     *
     * Not paginated: XenForo returns the lot, because a key holds one post's worth.
     *
     * @return list<Attachment>
     */
    public function list(string $key): array
    {
        return array_values(array_map(
            Attachment::fromArray(...),
            array_filter($this->apiGet('attachments/', ['key' => $key])->array('attachments'), 'is_array')
        ));
    }

    /**
     * @param  string|null  $key  the attachment key, needed while the attachment is still
     *                            unassociated - without it the forum has no way to know the
     *                            caller is the one who uploaded it, and answers 403. Not in
     *                            the published specification; read by the controller.
     */
    public function get(int $attachmentId, ?string $key = null): Attachment
    {
        return Attachment::fromArray(
            $this->apiGet('attachments/' . $attachmentId . '/', $key === null ? [] : ['key' => $key])
                ->array('attachment')
        );
    }

    public function find(int $attachmentId, ?string $key = null): ?Attachment
    {
        return $this->apiFind(
            'attachments/' . $attachmentId . '/',
            $key === null ? [] : ['key' => $key],
            'attachment',
            Attachment::fromArray(...)
        );
    }

    public function delete(int $attachmentId, ?string $key = null): bool
    {
        return $this->apiDelete(
            'attachments/' . $attachmentId . '/',
            $key === null ? [] : ['key' => $key]
        )->isSuccess();
    }

    /**
     * Create a key to upload against, optionally with the first file.
     *
     * @param  string  $type  the content type - `post` and `conversation_message` are the
     *                        built-in ones, and an add-on can register more
     * @param  array<string, scalar>  $context  what the attachment will belong to, e.g.
     *                                          ['thread_id' => 42] for a reply
     * @return array{key: string, attachment: Attachment|null}
     */
    public function newKey(string $type, array $context = [], ?Upload $attachment = null): array
    {
        $response = $this->apiUpload(
            'attachments/new-key',
            ['type' => $type, 'context' => $context],
            $attachment === null ? [] : ['attachment' => $attachment]
        );

        $uploaded = $response->array('attachment');

        return [
            'key' => (string) $response->value('key', ''),
            'attachment' => $uploaded === [] ? null : Attachment::fromArray($uploaded),
        ];
    }

    /**
     * The file itself.
     *
     * The stream is not read here, so a large attachment can go to disk without becoming a
     * PHP string on the way:
     *
     *     $xf->attachments()->download($id)->saveTo('/tmp/' . $id . '.bin');
     *
     * The type and the name come from the response rather than from the entity, and they
     * answer a slightly different question - Download says which and why.
     */
    public function download(int $attachmentId, ?string $key = null): Download
    {
        $response = $this->apiGetRaw(
            'attachments/' . $attachmentId . '/data',
            $key === null ? [] : ['key' => $key]
        );

        if ($response->getStatusCode() !== 200) {
            // 304 to a conditional request, or a redirect somebody's client did not follow.
            // Either way the body is not the file, and returning an empty Download would
            // write an empty file without complaining.
            throw new RuntimeException(sprintf(
                'The forum answered %d rather than the attachment. A caching or redirecting HTTP client '
                    . 'is in play: this endpoint returns the file itself on a 200.',
                $response->getStatusCode()
            ));
        }

        return Download::fromResponse($response);
    }

    /**
     * The URL of the attachment's thumbnail, or null if it has none.
     *
     * READ THIS BEFORE USING IT. The endpoint answers with a 301 whose Location header is
     * the whole of the output, so it only works through an HTTP client that does not follow
     * redirects. Guzzle's PSR-18 sendRequest() never does - it hard-codes allow_redirects
     * to false - so through a plain Guzzle client this works. A transport built some other
     * way may have followed it (Laravel's HTTP client does, unless withoutRedirecting()),
     * and then there is no way to recover the URL from a PSR-7 response; this throws rather
     * than guess.
     *
     * `$xf->attachments()->get($id)->thumbnail_url` is the same answer out of a request you
     * have very likely already made, and it does not depend on how the client is
     * configured. Prefer it. This exists because the endpoint does.
     */
    public function thumbnailUrl(int $attachmentId, ?string $key = null): ?string
    {
        // The path is spelled out here, and again below, rather than passed to the shared
        // helper - SpecConformanceTest reads these paths statically out of the source, and
        // a path assembled somewhere else is a path it cannot check.
        try {
            $response = $this->apiGetRaw(
                'attachments/' . $attachmentId . '/thumbnail',
                $key === null ? [] : ['key' => $key]
            );
        } catch (NotFoundException) {
            return null;
        }

        return self::location($response);
    }

    /**
     * As thumbnailUrl(), for the 2x image. Same caveat, and the same field on the entity -
     * `retina_thumbnail_url`.
     */
    public function retinaThumbnailUrl(int $attachmentId, ?string $key = null): ?string
    {
        try {
            $response = $this->apiGetRaw(
                'attachments/' . $attachmentId . '/retina-thumbnail',
                $key === null ? [] : ['key' => $key]
            );
        } catch (NotFoundException) {
            // XenForo answers notFound when the attachment has no thumbnail, which is an
            // ordinary answer rather than a failure - as is an attachment that is not there.
            return null;
        }

        return self::location($response);
    }

    private static function location(ResponseInterface $response): string
    {
        $location = $response->getHeaderLine('Location');

        if ($location !== '') {
            return $location;
        }

        throw new RuntimeException(
            'The forum redirected to the thumbnail and the HTTP client followed it, so the URL is gone: a '
                . 'PSR-7 response does not record where it ended up. Read thumbnail_url off the attachment '
                . 'entity instead, or configure the client not to follow redirects.'
        );
    }

    /**
     * Upload a file against an existing key.
     *
     * The key must have been created by the same user this credential is acting as -
     * XenForo answers `attachment_key_user_wrong` otherwise, which is worth catching by
     * code rather than reading as a permission problem.
     */
    public function upload(string $key, Upload $attachment): Attachment
    {
        return Attachment::fromArray(
            $this->apiUpload('attachments/', ['key' => $key], ['attachment' => $attachment])
                ->array('attachment')
        );
    }
}
