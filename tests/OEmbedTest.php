<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Exception\ClientException;

final class OEmbedTest extends TestCase
{
    public function test_it_asks_for_a_url_and_reads_the_oembed_document(): void
    {
        $this->client->pushJson(200, [
            'version' => '1.0',
            'type' => 'rich',
            'provider_name' => 'Example forum',
            'provider_url' => 'https://forum.example.com',
            'author_name' => 'sim',
            'html' => '<iframe src="…"></iframe>',
            'cache_age' => 3600,
        ]);

        $embed = $this->xenforo()->oembed()->get('https://forum.example.com/threads/hello.12/');

        $this->assertSame('1.0', $embed->version);
        $this->assertSame('rich', $embed->type);
        $this->assertSame('Example forum', $embed->providerName);
        $this->assertSame(3600, $embed->cacheAge);
        $this->assertSame(
            'https://forum.example.com/api/oembed/?url=https%3A%2F%2Fforum.example.com%2Fthreads%2Fhello.12%2F',
            $this->sentUri()
        );
    }

    /**
     * The forum embeds its OWN content: it routes the URL through its public router and
     * asks that controller for something embeddable. A link to somewhere else is not a
     * failure of the request, it is content this forum has nothing to say about.
     */
    public function test_a_url_the_forum_cannot_resolve_is_an_ordinary_absence(): void
    {
        $this->client->pushError(400, [['code' => 'requested_content_unavailable']]);

        $this->assertNull($this->xenforo()->oembed()->find('https://youtube.com/watch?v=x'));
    }

    /**
     * find() is about one error code and nothing else. A forum with external embedding
     * turned off answers 400 no_api_key_in_request to an unauthenticated call, and reading
     * that as "no embeddable content" would hide a configuration problem.
     */
    public function test_any_other_failure_still_raises(): void
    {
        $this->client->pushError(400, [['code' => 'no_api_key_in_request']]);

        $this->expectException(ClientException::class);

        $this->xenforo()->oembed()->find('https://forum.example.com/threads/hello.12/');
    }
}
