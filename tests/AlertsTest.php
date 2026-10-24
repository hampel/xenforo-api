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
     * XenForo acts on one flag and checks `viewed` first, so a request carrying both
     * marks the alert viewed and leaves it unread. The default call must send `read`
     * alone, which marks it viewed as well.
     */
    public function test_the_default_mark_sends_read_and_not_viewed(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->xenforo()->alerts()->mark(5);

        $this->assertSame(['read' => '1'], $this->sentBody());
        $this->assertStringEndsWith('/alerts/5/mark', $this->sentUri());
    }

    public function test_marking_only_viewed_sends_viewed(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->xenforo()->alerts()->mark(5, read: false);

        $this->assertSame(['viewed' => '1'], $this->sentBody());
    }

    public function test_an_alert_can_be_marked_unread(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->xenforo()->alerts()->markUnread(5);

        $this->assertSame(['unread' => '1'], $this->sentBody());
    }

    public function test_the_default_mark_all_sends_read_and_not_viewed(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->xenforo()->alerts()->markAll();

        $this->assertSame(['read' => '1'], $this->sentBody());
        $this->assertStringEndsWith('/alerts/mark-all', $this->sentUri());

        $this->client->pushJson(200, ['success' => true]);

        $this->xenforo()->alerts()->markAll(read: false);

        $this->assertSame(['viewed' => '1'], $this->sentBody());
    }
}
