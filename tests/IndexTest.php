<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

final class IndexTest extends TestCase
{
    public function test_it_reports_the_forum_and_the_key(): void
    {
        $this->client->pushJson(200, [
            'version_id' => 2031270,
            'site_title' => 'Example Forum',
            'key' => ['type' => 'super', 'user_id' => null, 'allow_all_scopes' => true, 'scopes' => []],
        ]);

        $info = $this->xenforo()->index()->get();

        $this->assertSame('https://forum.example.com/api/index/', $this->sentUri());
        $this->assertSame('Example Forum', $info->siteTitle);
        $this->assertSame('2.3.12', $info->version());
        $this->assertTrue($info->isSuperUserKey());
    }

    public function test_a_ping_succeeds_when_the_forum_answers(): void
    {
        $this->client->pushJson(200, ['version_id' => 2031270]);

        $this->assertTrue($this->xenforo()->index()->ping());
    }

    /**
     * ping() is for a health check, so it answers rather than throwing - including for the
     * two failures most likely to bring it here: a rejected key, and a base URI that does
     * not reach an API at all.
     */
    public function test_a_ping_fails_quietly_and_says_why_in_the_log(): void
    {
        $logger = new RecordingLogger();

        $this->client->pushError(401, [['code' => 'api_key_not_found', 'message' => 'API key not found']]);

        $this->assertFalse($this->xenforo(logger: $logger)->index()->ping());
        $this->assertTrue($logger->hasMessageContaining('ping failed'));
    }

    public function test_a_ping_survives_a_transport_failure(): void
    {
        $this->client->push(new TransportFailure('Name or service not known'));

        $this->assertFalse($this->xenforo()->index()->ping());
    }

    /**
     * A base URI pointing at the forum's front end rather than its API gets HTML back.
     */
    public function test_a_ping_survives_an_html_response(): void
    {
        $this->client->pushRaw(404, '<!DOCTYPE html><title>Oops! We ran into some problems.</title>');

        $this->assertFalse($this->xenforo()->index()->ping());
    }
}
