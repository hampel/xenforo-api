<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

final class StatsTest extends TestCase
{
    /**
     * The specification describes this response as `totals[threads]`, `online[total]` and
     * so on, which is the shape of the annotation the docs were compiled from rather than
     * the shape of the JSON - the JSON nests. SiteStats is hand-written for that reason,
     * and this is what says the mapping is right.
     */
    public function test_the_three_nested_blocks_come_out_flat(): void
    {
        $this->client->pushJson(200, [
            'totals' => ['threads' => 1200, 'messages' => 34567, 'users' => 890],
            'latest_user' => ['user_id' => 890, 'username' => 'sim', 'register_date' => 1_756_000_000],
            'online' => ['total' => 42, 'members' => 12, 'guests' => 30],
        ]);

        $stats = $this->xenforo()->stats()->get();

        $this->assertSame(1200, $stats->threads);
        $this->assertSame(34567, $stats->messages);
        $this->assertSame(890, $stats->users);
        $this->assertSame('sim', $stats->latestUsername);
        $this->assertSame(1_756_000_000, $stats->latestUserRegisterDate);
        $this->assertSame(42, $stats->onlineTotal);
        $this->assertSame(30, $stats->onlineGuests);
        $this->assertTrue($stats->hasLatestUser());
        $this->assertSame('https://forum.example.com/api/stats/', $this->sentUri());
    }

    /**
     * A forum with no registered users still sends the block, filled with zeroes and an
     * empty string rather than left out - so the id is what has to be tested.
     */
    public function test_an_empty_forum_reports_no_latest_user_rather_than_omitting_it(): void
    {
        $this->client->pushJson(200, [
            'totals' => ['threads' => 0, 'messages' => 0, 'users' => 0],
            'latest_user' => ['user_id' => 0, 'username' => '', 'register_date' => 0],
            'online' => ['total' => 0, 'members' => 0, 'guests' => 0],
        ]);

        $stats = $this->xenforo()->stats()->get();

        $this->assertFalse($stats->hasLatestUser());
        $this->assertSame(0.0, $stats->onlineGuestsShare());
    }

    /**
     * An add-on can add to any API result, so nothing is dropped for not being in the
     * specification - the same promise the generated entities make with their own $raw.
     */
    public function test_fields_no_specification_mentions_survive_in_raw(): void
    {
        $this->client->pushJson(200, [
            'totals' => ['threads' => 1],
            'some_addon_block' => ['whatever' => true],
        ]);

        $stats = $this->xenforo()->stats()->get();

        $this->assertSame(['whatever' => true], $stats->raw['some_addon_block']);
    }
}
