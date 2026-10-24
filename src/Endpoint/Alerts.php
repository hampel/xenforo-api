<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Generated\Schema\UserAlert;
use Hampel\XenForo\Api\Result\Page;

/**
 * Alerts - the `Alerts` tag in the XenForo API documentation.
 *
 * Reading covers the acting user's own alerts. Sending one is a super-user operation, and
 * the most useful endpoint in this package for an integration that wants to tell a forum
 * member something without sending email.
 */
final class Alerts extends Endpoint
{
    /**
     * @param  array<string, scalar|array<mixed>|null>  $filters  cutoff, unviewed, unread
     * @return Page<UserAlert>
     */
    public function list(int $page = 1, array $filters = []): Page
    {
        return $this->apiPaginate('alerts/', 'alerts', UserAlert::fromArray(...), $page, $filters);
    }

    /**
     * @param  array<string, scalar|array<mixed>|null>  $filters
     * @return \Generator<int, UserAlert>
     */
    public function each(array $filters = []): \Generator
    {
        yield from $this->apiEach('alerts/', 'alerts', UserAlert::fromArray(...), $filters);
    }

    public function get(int $alertId): UserAlert
    {
        return UserAlert::fromArray($this->apiGet('alerts/' . $alertId . '/')->array('alert'));
    }

    /**
     * Send an alert to a user. Super-user keys only.
     *
     * The `{link}` placeholder in the alert text is where the link is inserted. Without it
     * XenForo appends the link to the end, which is usually fine and occasionally reads
     * badly - putting the placeholder in is how you control that.
     *
     * @param  array<string, mixed>  $options  from_user_id (0 for an anonymous alert),
     *         link_url, link_title
     */
    public function send(int $toUserId, string $alert, array $options = []): bool
    {
        return $this->apiPost('alerts/', [
            'to_user_id' => $toUserId,
            'alert' => $alert,
        ] + $options)->isSuccess();
    }

    /**
     * Mark one alert read, or only viewed. Viewed means it has appeared in the alert
     * popup; read means it has been acted on, and marking read marks it viewed too.
     *
     * XenForo acts on ONE flag per request and checks `viewed` before `read`, so a
     * request carrying both true only marks the alert viewed - the call succeeds and the
     * alert stays unread. Only the strongest flag asked for is sent: `read` when $read,
     * otherwise `viewed`. With both false XenForo answers that input is required.
     *
     * Needs only the `alert:read` scope: XenForo exempts both mark endpoints from the
     * `alert:write` a POST would otherwise require.
     */
    public function mark(int $alertId, bool $read = true, bool $viewed = true): bool
    {
        return $this->apiPost('alerts/' . $alertId . '/mark', self::markFlag($read, $viewed))->isSuccess();
    }

    /**
     * Mark one alert unread again - the third state the mark endpoint takes.
     */
    public function markUnread(int $alertId): bool
    {
        return $this->apiPost('alerts/' . $alertId . '/mark', ['unread' => true])->isSuccess();
    }

    /**
     * Mark every alert read, or only viewed. The same one-flag rule as mark(), and the
     * same `alert:read` scope; there is no unread form of this one.
     */
    public function markAll(bool $read = true, bool $viewed = true): bool
    {
        return $this->apiPost('alerts/mark-all', self::markFlag($read, $viewed))->isSuccess();
    }

    /**
     * @return array<string, bool>
     */
    private static function markFlag(bool $read, bool $viewed): array
    {
        return match (true) {
            $read => ['read' => true],
            $viewed => ['viewed' => true],
            default => ['read' => false, 'viewed' => false],
        };
    }
}
