<?php

/**
 * Not an exercise - see lib/agent.php. Builds the client every exercise needs, from the
 * environment, and fails with something legible when it cannot.
 */

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\HttpFactory;
use Hampel\Rig\Io;
use Hampel\XenForo\Api\Authentication\ApiKey;
use Hampel\XenForo\Api\Authentication\SuperUserKey;
use Hampel\XenForo\Api\Client;
use Hampel\XenForo\Api\Config;

function harness_forum_url(Io $io): string
{
    $url = getenv('XENFORO_URL');

    if (!is_string($url) || $url === '') {
        $io->error('XENFORO_URL is not set. Copy .env.example to .env beside the package.');

        exit(1);
    }

    return $url;
}

function harness_client(Io $io): Client
{
    $url = harness_forum_url($io);
    $key = getenv('XENFORO_API_KEY');

    if (!is_string($key) || $key === '') {
        $io->error('XENFORO_API_KEY is not set. Copy .env.example to .env beside the package.');
        $io->error('If you are an agent and the rig said it withheld the environment file, that is the guard');
        $io->error('working - ask rather than working around it.');

        exit(1);
    }

    // A super-user key can act as a user, and every read below is more interesting when it
    // does. XENFORO_API_USER is how you say which; without it a super key acts as a guest.
    $actingAs = getenv('XENFORO_API_USER');

    $authentication = is_string($actingAs) && ctype_digit($actingAs)
        ? new SuperUserKey($key, (int) $actingAs)
        : new ApiKey($key);

    $factory = new HttpFactory();

    return new Client(new Config($url), $authentication, new Guzzle(), $factory, $factory);
}
