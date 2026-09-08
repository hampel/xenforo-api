<?php

/**
 * Exercise: prove against a real forum that a decorated Content-Type loses a DELETE body. Writes one attachment, removes it.
 *
 * This is the claim the whole package rests on and the one nothing local can settle.
 * Connection::FORM_CONTENT_TYPE is a factual assertion about somebody else's PHP:
 * \XF\Http\Request::convertCustomMethodPhpInput() compares $_SERVER['CONTENT_TYPE'] with
 * `===` against 'application/x-www-form-urlencoded', so a "; charset=utf-8" that most HTTP
 * clients would add makes the comparison fail and the body vanish. The suite asserts the
 * header we send; it cannot assert what XenForo then does with it, because the stub is
 * built from the same belief the code is.
 *
 * THE POST LEG probes without writing anything: POST auth/ with a login that cannot exist.
 * A body that arrived is validated and rejected; a body that was lost is reported as a
 * REQUIRED INPUT missing. Both content types are sent, and both should arrive - PHP parses
 * a POST body itself and ignores the parameter - which is the control.
 *
 * THE DELETE LEG NEEDS A RECORD OF ITS OWN, and the first version of this exercise did not
 * understand that. It sent DELETE auth/, which has no DELETE action; both legs answered 404
 * endpoint_not_found before any input was parsed; and the exercise, seeing no
 * required_input_missing on either, concluded that XenForo no longer compared the header
 * exactly. It had tested nothing and reported a finding. A 404 on either leg now means
 * "nothing was tested" and says so.
 *
 * What it does instead: XenForo's own DELETEs take their arguments as QUERY parameters, so
 * a body on one is only ever read through convertCustomMethodPhpInput() - and every core
 * DELETE looks its record up before reading any input, so a nonexistent record cannot
 * probe it. So the exercise uploads one attachment against a key and sends
 * DELETE attachments/{id}/ with that key in the BODY, twice. Decorated first: the key is
 * lost, assertViewableAttachment() sees an unassociated attachment with no key, answers
 * 403, and the attachment survives. Then bare: the key arrives, the delete succeeds, 200.
 * That order is load-bearing - the other way round the second leg has nothing to delete.
 *
 * Needs XENFORO_URL and a SUPER-USER XENFORO_API_KEY (auth/ is super-user only), acting as
 * a user who may manage attachments - XENFORO_API_USER. Nothing pre-existing is touched.
 * Set XENFORO_PROBE=1 to run it. Under an agent, XENFORO_AGENT_MAY_PROBE=1 as well, on the
 * command line and never in .env.
 *
 * @var Hampel\Rig\Io $io
 */

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\HttpFactory;
use Hampel\XenForo\Api\Connection;
use Hampel\XenForo\Api\Exception\ExceptionInterface;
use Hampel\XenForo\Api\Upload;

require __DIR__ . '/lib/agent.php';
require __DIR__ . '/lib/client.php';
require __DIR__ . '/lib/content.php';

$io->title('xenforo-api · encoding');

/**
 * The mode goes above the work, not below it, so nobody reads a refused run as a result.
 *
 * @return array{bool, string}
 */
$mode = static function (): array {
    if (getenv('XENFORO_PROBE') !== '1') {
        return [false, 'refused - set XENFORO_PROBE=1 to send the six probe requests'];
    }

    if (harness_agent_refuses('XENFORO_AGENT_MAY_PROBE')) {
        return [false, 'refused - XENFORO_PROBE ignored in an agent session'];
    }

    return [true, 'probing - four login attempts that cannot succeed, then one attachment up and down'];
};

[$proceed, $description] = $mode();

$io->value('mode', $description);

// Settled before the credential is looked for, so a refused run reads the same whether or
// not one is configured - which is what an agent meets, the rig having withheld the
// environment file entirely.
if (!$proceed) {
    $io->line();
    $io->warn('Nothing was sent, so this run answers none of the questions in the docblock -');
    $io->warn('in particular it does NOT show that the Content-Type rule still holds.');

    exit(0);
}

$xf = harness_client($io);
$config = $xf->config();
$key = (string) getenv('XENFORO_API_KEY');
$actingAs = getenv('XENFORO_API_USER');

$io->value('forum', $config->baseUri);
$io->line();

$guzzle = new Guzzle(['http_errors' => false]);
$factory = new HttpFactory();

$decorated = Connection::FORM_CONTENT_TYPE . '; charset=utf-8';

/**
 * A raw request with the header under test written by hand, because the package would
 * write it correctly and the point is to watch what an incorrect one does.
 *
 * @return array{status: int, codes: list<string>, message: string}
 */
$probe = static function (string $method, string $path, string $contentType, string $body) use ($guzzle, $factory, $config, $key, $actingAs): array {
    $request = $factory->createRequest($method, $config->resolve($path))
        ->withHeader('XF-Api-Key', $key)
        ->withHeader('Accept', 'application/json')
        ->withHeader('Content-Type', $contentType)
        ->withBody($factory->createStream($body));

    if (is_string($actingAs) && ctype_digit($actingAs)) {
        $request = $request->withHeader('XF-Api-User', $actingAs);
    }

    $response = $guzzle->sendRequest($request);
    $decoded = json_decode((string) $response->getBody(), true);

    $codes = [];
    $message = '';

    foreach ((is_array($decoded) ? $decoded['errors'] ?? [] : []) as $error) {
        if (is_array($error)) {
            $codes[] = isset($error['code']) && is_scalar($error['code']) ? (string) $error['code'] : '?';
            $message = isset($error['message']) && is_scalar($error['message']) ? (string) $error['message'] : $message;
        }
    }

    return ['status' => $response->getStatusCode(), 'codes' => $codes, 'message' => $message];
};

/** @param array{status: int, codes: list<string>, message: string} $result */
$line = static fn (array $result): string => sprintf(
    '%d  [%s]  %s',
    $result['status'],
    implode(', ', $result['codes']),
    $result['message']
);

// XenForo's "you did not send a required field" code. Its presence means the body was not
// there to be read - which for a write endpoint would have been a 200 that did nothing.
$lost = static fn (array $result): bool => in_array('required_input_missing', $result['codes'], true);

// ---- POST: the control ----------------------------------------------------------------

$login = 'zzz-no-such-user-' . bin2hex(random_bytes(4));
$loginBody = http_build_query(['login' => $login, 'password' => 'not-a-password', 'limit_ip' => '0']);

$post = [
    'POST bare' => $probe('POST', 'auth/', Connection::FORM_CONTENT_TYPE, $loginBody),
    'POST decorated' => $probe('POST', 'auth/', $decorated, $loginBody),
];

$io->info('POST auth/ with a login that cannot exist - the control:');
$io->line();
$io->values(array_map($line, $post));
$io->line();

if ($lost($post['POST bare'])) {
    $io->error('✗ Even a bare POST lost its body. Something more fundamental is wrong than the');
    $io->error('  Content-Type rule - check the URL reaches the API and not the forum front end.');

    exit(1);
}

$io->success('✓ POST bare        - body arrived, as it must for every write in this package');
$io->line($lost($post['POST decorated'])
    ? '  ! POST decorated   - body LOST. PHP is stricter here than expected; worth writing up.'
    : '  ✓ POST decorated   - body arrived. PHP parses a POST body itself and ignores the'
    . PHP_EOL . '                       charset parameter, so the decoration is harmless on POST.');
$io->line();

// ---- DELETE: the claim --------------------------------------------------------------

// Before the block with a cleanup in it, because it exits rather than throws.
$nodeId = harness_forum_node_id($xf, $io);

$io->info('DELETE attachments/{id}/ with the key in the BODY - the claim:');
$io->line();

$attachmentId = null;
$attachmentKey = null;
$delete = [];
$failure = null;
$leaked = false;

try {
    $attachmentKey = $xf->attachments()->newKey('post', ['node_id' => $nodeId])['key'];

    $uploaded = $xf->attachments()->upload(
        $attachmentKey,
        Upload::fromString(harness_one_pixel_png(), sprintf('xf-api-encoding-%s.png', gmdate('Ymd-His')))
    );

    $attachmentId = $uploaded->attachment_id;

    if ($attachmentId === null) {
        throw new \RuntimeException('the upload came back without an attachment_id');
    }

    $io->value('attachment', $attachmentId);
    $io->line();

    $deleteBody = http_build_query(['key' => $attachmentKey]);
    $path = 'attachments/' . $attachmentId . '/';

    // Decorated FIRST. It is the one expected to fail, and the attachment has to survive it
    // for the bare leg to have anything to delete.
    $delete = [
        'DELETE decorated' => $probe('DELETE', $path, $decorated, $deleteBody),
        'DELETE bare' => $probe('DELETE', $path, Connection::FORM_CONTENT_TYPE, $deleteBody),
    ];

    $io->values(array_map($line, $delete));
    $io->line();
} catch (ExceptionInterface $e) {
    $failure = $e;

    $io->error(sprintf('✗ %s', $e::class));
    $io->error('  ' . $e->getMessage());
} finally {
    // The bare leg is supposed to have deleted it. Anything else, and it is still there.
    $stillThere = $attachmentId !== null && ($delete['DELETE bare']['status'] ?? 0) !== 200;

    if ($stillThere) {
        try {
            $xf->attachments()->delete($attachmentId, $attachmentKey);

            $io->line(sprintf('  cleaned up attachment %d through the package', $attachmentId));
        } catch (ExceptionInterface $cleanup) {
            $leaked = true;

            $io->error(sprintf('  ✗ CLEANUP FAILED for attachment %d - %s', $attachmentId, $cleanup::class));
            $io->error('    Remove it by hand. It is unassociated, so the forum will also expire it.');
        }
    }
}

if ($failure !== null) {
    exit(1);
}

$decoratedStatus = $delete['DELETE decorated']['status'] ?? 0;
$bareStatus = $delete['DELETE bare']['status'] ?? 0;

$io->info('Reading the pair:');
$io->line();

if ($decoratedStatus === 404 || $bareStatus === 404) {
    // The mistake the first version of this exercise made, now named rather than misread.
    $io->warn('! A 404 on a DELETE leg means the request never reached input parsing - the route');
    $io->warn('  or the record was missing - so NOTHING WAS TESTED. This is not a result.');
} elseif ($decoratedStatus === 403 && $bareStatus === 200) {
    $io->success('✓ The rule holds. With a decorated Content-Type the key in the body was never read');
    $io->success('  and the forum refused; with the bare one it was read and the delete went through.');
    $io->success('  That is exactly why Connection writes the header itself.');
} elseif ($decoratedStatus === 200) {
    $io->warn('! The decorated DELETE succeeded, so its body WAS read. XenForo no longer compares');
    $io->warn('  the header exactly, or something in front of the forum is normalising it. Nothing');
    $io->warn('  breaks - the bare header is still correct - but FORM_CONTENT_TYPE\'s docblock now');
    $io->warn('  overstates the hazard and should be re-checked against the current XenForo source.');
} else {
    $io->warn('! Neither outcome this exercise knows about. Read the two lines above directly.');
}

if ($leaked) {
    exit(1);
}
