<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Authentication\SuperUserKey;
use Hampel\XenForo\Api\Upload;

/**
 * The two avatar endpoints, which are the same call on two paths - one for the user the
 * credential is acting as, one for a user named by id.
 */
final class AvatarTest extends TestCase
{
    public function test_the_acting_user_replaces_their_own(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->assertTrue(
            $this->xenforo()->me()->uploadAvatar(Upload::fromString('JPEG', 'me.jpg', 'image/jpeg'))
        );

        $this->assertSame('https://forum.example.com/api/me/avatar', $this->sentUri());
        $this->assertSame('POST', $this->client->lastRequest()->getMethod());

        $part = $this->sentParts()->parts['avatar'];

        $this->assertSame('me.jpg', $part['filename']);
        $this->assertSame('image/jpeg', $part['type']);
        $this->assertSame('JPEG', $part['value']);
    }

    public function test_a_super_user_key_replaces_somebody_else(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $client = $this->xenforo(new SuperUserKey('super-key'))->actingAs(3);

        $this->assertTrue($client->users()->uploadAvatar(7, Upload::fromString('JPEG', 'them.jpg')));

        $request = $this->client->lastRequest();

        $this->assertSame('https://forum.example.com/api/users/7/avatar', (string) $request->getUri());
        $this->assertSame('3', $request->getHeaderLine('XF-Api-User'));
    }

    /**
     * XenForo judges an upload by the filename's extension and by the file's own contents -
     * \XF\Http\Request::getFile() never looks at the part's declared type. So the type this
     * package sends is the honest unspecific one unless told otherwise, and it is the
     * filename that has to be right.
     */
    public function test_the_declared_type_is_incidental_and_the_filename_is_not(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->xenforo()->me()->uploadAvatar(Upload::fromString('JPEG', 'me.jpg'));

        $this->assertSame('me.jpg', $this->sentParts()->parts['avatar']['filename']);
        $this->assertSame('application/octet-stream', $this->sentParts()->parts['avatar']['type']);
    }
}
