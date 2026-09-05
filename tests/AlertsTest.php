<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Authentication\SuperUserKey;

final class AlertsTest extends TestCase
{
    public function test_it_sends_an_alert(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $sent = $this->xenforo(new SuperUserKey('super'))->alerts()->send(
            42,
            'Your report has finished. {link}',
            ['link_url' => 'https://example.com/report', 'link_title' => 'View it']
        );

        $this->assertTrue($sent);
        $this->assertSame([
            'to_user_id' => '42',
            'alert' => 'Your report has finished. {link}',
            'link_url' => 'https://example.com/report',
            'link_title' => 'View it',
        ], $this->sentBody());
    }

    /**
     * Read and viewed are separate states in XenForo, and both are sent explicitly rather
     * than left to a default that differs between the two mark endpoints.
     */
    public function test_marking_sends_both_states(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->xenforo()->alerts()->mark(5, read: true, viewed: false);

        $this->assertSame(['read' => '1', 'viewed' => '0'], $this->sentBody());
    }
}
