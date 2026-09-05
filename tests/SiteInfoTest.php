<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Result\SiteInfo;
use PHPUnit\Framework\TestCase as BaseTestCase;

final class SiteInfoTest extends BaseTestCase
{
    /**
     * @return array<mixed>
     */
    private function response(): array
    {
        return [
            'version_id' => 2031270,
            'site_title' => 'Example Forum',
            'base_url' => 'https://forum.example.com',
            'api_url' => 'https://forum.example.com/api',
            'key' => [
                'type' => 'user',
                'user_id' => 4264,
                'allow_all_scopes' => false,
                'scopes' => ['user:read', 'thread:read'],
            ],
        ];
    }

    public function test_it_reads_the_index_response(): void
    {
        $info = SiteInfo::fromArray($this->response());

        $this->assertSame('Example Forum', $info->siteTitle);
        $this->assertSame('user', $info->keyType);
        $this->assertSame(4264, $info->keyUserId);
        $this->assertFalse($info->isSuperUserKey());
    }

    /**
     * XenForo's version_id is abbccde, so 2031270 is 2.3.12 - the only way to tell whether
     * an endpoint added in a later release will be there.
     */
    public function test_it_decodes_the_version_id(): void
    {
        $this->assertSame('2.3.12', SiteInfo::fromArray($this->response())->version());
    }

    public function test_scopes_decide_what_the_key_may_do(): void
    {
        $info = SiteInfo::fromArray($this->response());

        $this->assertTrue($info->hasScope('user:read'));
        $this->assertFalse($info->hasScope('user:write'));
    }

    /**
     * A super-user key reports allow_all_scopes and an empty scope list, so reading the
     * list alone would say it can do nothing.
     */
    public function test_a_super_user_key_has_every_scope(): void
    {
        $response = $this->response();
        $response['key'] = ['type' => 'super', 'user_id' => null, 'allow_all_scopes' => true, 'scopes' => []];

        $info = SiteInfo::fromArray($response);

        $this->assertTrue($info->isSuperUserKey());
        $this->assertTrue($info->hasScope('anything:at:all'));
        $this->assertNull($info->keyUserId);
    }
}
