<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Result\ApiResponse;
use Hampel\XenForo\Api\Result\ResponseMeta;
use PHPUnit\Framework\TestCase as BaseTestCase;

final class ApiResponseTest extends BaseTestCase
{
    /**
     * XenForo omits a field the credential may not see rather than sending it as null, so
     * absent and null are different answers here: the first is about the caller's
     * standing, the second about the data. value() collapses them; has() does not.
     */
    public function test_has_tells_absent_from_null_where_value_cannot(): void
    {
        $response = new ApiResponse(['email' => null], 200, new ResponseMeta());

        $this->assertTrue($response->has('email'));
        $this->assertFalse($response->has('is_banned'));

        $this->assertSame('DEFAULT', $response->value('email', 'DEFAULT'), 'value() reads a null as absent...');
        $this->assertSame('DEFAULT', $response->value('is_banned', 'DEFAULT'), '...and cannot distinguish them.');
    }

    public function test_has_is_about_the_top_level_only(): void
    {
        $response = new ApiResponse(['user' => ['email' => 'x']], 200, new ResponseMeta());

        $this->assertTrue($response->has('user'));
        $this->assertFalse($response->has('email'));
    }
}
