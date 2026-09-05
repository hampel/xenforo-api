<?php

/**
 * Exercise: ask a real forum what it is, and what the key may do. Read-only.
 *
 * The first thing to run against a forum you have just been given a URL and a key for.
 * Everything else in this harness assumes those two are right, and this is the exercise
 * that says so - including the two mistakes that produce the most confusing failures
 * elsewhere: a URL that reaches the forum's front end rather than its API (HTML back, not
 * JSON) and a key with fewer scopes than the caller assumed.
 *
 * Needs XENFORO_URL and XENFORO_API_KEY.
 *
 * @var Hampel\Rig\Io $io
 */

use Hampel\XenForo\Api\Exception\ExceptionInterface;

require __DIR__ . '/lib/client.php';

$io->title('xenforo-api · index');

$xf = harness_client($io);

$io->values([
    'forum' => $xf->config()->baseUri,
    'credential' => $xf->authentication()->describe(),
]);
$io->line();

try {
    $info = $xf->index()->get();
} catch (ExceptionInterface $e) {
    $io->error('✗ ' . $e::class);
    $io->error($e->getMessage());

    exit(1);
}

$io->values([
    'site title' => $info->siteTitle,
    'base url' => $info->baseUrl,
    'api url' => $info->apiUrl,
    'version' => $info->version() . ' (' . $info->versionId . ')',
    'key type' => $info->keyType,
    'key user_id' => $info->keyUserId ?? '(none - acting as guest)',
    'all scopes' => $info->allowAllScopes ? 'yes' : 'no',
    'scopes' => $info->scopes === [] ? '(none listed)' : implode(', ', $info->scopes),
]);

$io->line();
$io->success('✓ the forum answered');
