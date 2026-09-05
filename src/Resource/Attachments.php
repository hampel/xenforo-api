<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Resource;

use Hampel\XenForo\Api\Generated\Schema\Attachment;
use Hampel\XenForo\Api\Upload;

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
 * NOT HERE: the three endpoints that do not answer in JSON - `attachments/{id}/data`
 * returns the raw file, and the two thumbnail endpoints answer with a 301 to an image.
 * Connection decodes every response as JSON and treats a redirect as a failure, so those
 * need a raw-response path this package does not have yet. Build the request with
 * `connection()->request('GET', ...)` and send it through `connection()->client()`.
 */
final class Attachments extends Resource
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
