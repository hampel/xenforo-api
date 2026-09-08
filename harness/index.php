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
use Hampel\XenForo\Api\Result\SiteInfo;

require __DIR__ . '/lib/client.php';

$io->title('xenforo-api · index');

$xf = harness_client($io);

$io->values([
    'forum' => $xf->config()->baseUri,
    'credential' => $xf->authentication()->describe(),
]);
$io->line();

try {
    // Through the connection rather than index()->get(), because the acting user is not in
    // the body at all - it is the XF-Request-User response header, which only the
    // connection-level response carries.
    $response = $xf->connection()->get('index/');
    $info = SiteInfo::fromArray($response->data);
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
    // A user key names its user here; a super key has no fixed user and never will, so an
    // empty value on one says nothing about who the request acted as - that is below.
    'key user_id' => $info->keyUserId ?? ($info->keyType === 'super' ? '(none - a super key has no fixed user)' : '(none)'),
    'acting as' => $response->meta->requestUser === null ? 'guest' : 'user ' . $response->meta->requestUser,
    'all scopes' => $info->allowAllScopes ? 'yes' : 'no',
    'scopes' => $info->scopes === [] ? '(none listed)' : implode(', ', $info->scopes),
]);

$io->line();
$io->success('✓ the forum answered');
