<?php

/**
 * Exercise: upload a file to a real forum, read it back, and see what it judged it by. Writes.
 *
 * Multipart is the one thing in this package a stubbed test cannot settle. The suite takes
 * the body apart again and proves it is well formed by the boundary its own header
 * declares - but it is this package parsing this package, and a body that PHP's own
 * multipart parser will not accept looks identical from in there. Only a real forum
 * answers that.
 *
 * THE QUESTIONS
 *
 *   1. Does PHP accept the body at all? A body it cannot parse leaves $_FILES and $_POST
 *      empty, so the answer is not an error about the file - it is XenForo reporting that
 *      a REQUIRED INPUT is missing, which is the silent-success shape this package exists
 *      to avoid.
 *   2. Does `context[node_id]` survive the multipart encoding? XenForo checks the acting
 *      user's permission against the context, so a context that arrived empty is refused
 *      as a permission failure rather than reported as a lost field. A key coming back at
 *      all is the answer.
 *   3. Do the bytes come back? attachments/{id}/data answers with the file rather than
 *      with JSON, so it goes through a different path in Connection - and a round trip is
 *      the only check that says the upload and the download agree. The comparison is on a
 *      hash of the bytes, not on their length, because a body mangled in transit is
 *      overwhelmingly likely to keep its length and change its content.
 *   4. Is the FILENAME really what the forum judges, rather than the Content-Type the part
 *      declares? Upload::DEFAULT_CONTENT_TYPE rests on that claim. Two uploads of the same
 *      bytes settle it: one named .png declaring application/octet-stream, one named .zzz
 *      declaring image/png. If the filename decides, the first is accepted and the second
 *      refused - which is the opposite of what their declared types would suggest.
 *
 * WHAT IT LEAVES BEHIND, AND WHY THAT IS LITTLE
 *
 * An attachment uploaded against a key is temporary until content is created with that
 * key. Nothing here creates content, so the forum's own daily cleanup would remove these
 * anyway - and the exercise deletes them itself in a finally, naming any it could not.
 *
 * Needs XENFORO_URL, an XENFORO_API_KEY that may manage attachments, and - since an
 * attachment key belongs to a user - XENFORO_API_USER if the key is a super-user one.
 * XENFORO_NODE_ID picks the forum whose permissions are checked; without it, the first
 * Forum node found.
 *
 * Set XENFORO_UPLOAD=1 to run it. Under an agent, XENFORO_AGENT_MAY_UPLOAD=1 as well, on
 * the command line and never in .env.
 *
 * @var Hampel\Rig\Io $io
 */

use Hampel\XenForo\Api\Exception\ApiException;
use Hampel\XenForo\Api\Exception\ExceptionInterface;
use Hampel\XenForo\Api\Upload;

require __DIR__ . '/lib/agent.php';
require __DIR__ . '/lib/client.php';
require __DIR__ . '/lib/content.php';

$io->title('xenforo-api · upload');

/**
 * The mode goes above the work, not below it, so nobody reads a refused run as a result.
 *
 * @return array{bool, string}
 */
$mode = static function (): array {
    if (getenv('XENFORO_UPLOAD') !== '1') {
        return [false, 'refused - set XENFORO_UPLOAD=1 to upload two files to the forum'];
    }

    if (harness_agent_refuses('XENFORO_AGENT_MAY_UPLOAD')) {
        return [false, 'refused - XENFORO_UPLOAD ignored in an agent session'];
    }

    return [true, 'uploading - two files will be written to the forum, and deleted again'];
};

[$proceed, $description] = $mode();

$io->value('mode', $description);

// The refusal is settled before the credential is looked for, so a refused run reads the
// same whether or not one is configured - which is what an agent meets, the rig having
// withheld the environment file entirely.
if (!$proceed) {
    $io->line();
    $io->warn('Nothing was sent, so this run answers none of the four questions above -');
    $io->warn('in particular it does NOT show that a real forum accepts the body this builds.');

    exit(0);
}

$xf = harness_client($io);

$io->value('forum', $xf->config()->baseUri);
$io->line();

$png = harness_one_pixel_png();

$stamp = gmdate('Ymd-His');

// Before the block with a cleanup in it, because it exits rather than throws.
$nodeId = harness_forum_node_id($xf, $io);

$io->value('node_id', $nodeId);
$io->line();

/** @var list<int> $uploaded */
$uploaded = [];
$failure = null;
$leaked = false;
$mismatch = false;
$key = null;

try {
    // QUESTION 2. An empty context is refused, so a key coming back means context[node_id]
    // arrived intact through the multipart encoding.
    $io->info('1. attachments/new-key, context sent as a nested multipart field');

    $created = $xf->attachments()->newKey('post', ['node_id' => $nodeId]);
    $key = $created['key'];

    $io->success(sprintf('   ✓ key %s - so context[node_id] survived the encoding', $key));
    $io->line();

    // QUESTION 1 and the first half of 3.
    $io->info('2. attachments/, a real PNG named .png declaring application/octet-stream');

    $accepted = $xf->attachments()->upload(
        $key,
        Upload::fromString($png, sprintf('xf-api-harness-%s.png', $stamp))
    );

    if ($accepted->attachment_id !== null) {
        $uploaded[] = $accepted->attachment_id;
    }

    $io->values([
        'attachment_id' => $accepted->attachment_id,
        'filename' => $accepted->filename,
        'file_size' => $accepted->file_size,
    ]);
    $io->success('   ✓ the body parsed, the file arrived, and $_FILES was populated');
    $io->line();

    // Read it back through a second request, so this is the forum's account of what it
    // stored rather than the upload's own echo of what was sent.
    $listed = $xf->attachments()->list($key);

    $io->success(sprintf('   ✓ %d attachment(s) readable against the key', count($listed)));
    $io->line();

    // QUESTION 3. The download path, and the only end-to-end check there is.
    $io->info('3. attachments/{id}/data, reading the same file back');

    if ($accepted->attachment_id === null) {
        $io->error('   ✗ the upload came back without an attachment_id, so there is nothing to read');

        $mismatch = true;
    } else {
        $download = $xf->attachments()->download($accepted->attachment_id, $key);
        $returned = $download->contents();

        $io->values([
            'content type' => $download->contentType,
            'filename' => $download->filename,
            'bytes' => strlen($returned),
        ]);

        if (hash('sha256', $returned) === hash('sha256', $png)) {
            $io->success('   ✓ byte-for-byte identical to what went up');
        } else {
            $io->error('   ✗ THE BYTES CHANGED. Compare the length above against the upload, and');
            $io->error('     suspect the multipart framing or the download path before the forum.');

            $mismatch = true;
        }
    }

    $io->line();

    // THE SECOND HALF OF QUESTION 4.
    $io->info('4. the same bytes named .zzz declaring image/png');

    try {
        $refused = $xf->attachments()->upload(
            $key,
            Upload::fromString($png, sprintf('xf-api-harness-%s.zzz', $stamp), 'image/png')
        );

        if ($refused->attachment_id !== null) {
            $uploaded[] = $refused->attachment_id;
        }

        $io->warn('   ! ACCEPTED. Either this forum allows the .zzz extension, or the declared');
        $io->warn('     Content-Type is now being consulted. Upload::DEFAULT_CONTENT_TYPE says it');
        $io->warn('     is not, and that docblock should be re-checked against XF\'s getFile().');
    } catch (ApiException $e) {
        $io->success(sprintf(
            '   ✓ refused: %d [%s] - the FILENAME decided, not the declared type',
            $e->statusCode,
            implode(', ', $e->codes())
        ));
    }
} catch (ExceptionInterface $e) {
    $failure = $e;

    $io->line();
    $io->error(sprintf('✗ %s', $e::class));
    $io->error('  ' . $e->getMessage());

    if ($e instanceof ApiException && $e->hasCode('required_input_missing')) {
        $io->line();
        $io->error('  required_input_missing is the answer to question 1, and it is the bad one:');
        $io->error('  the request arrived but its body did not parse, so XenForo saw no input at');
        $io->error('  all. On a write endpoint that shape is a 200 that did nothing.');
    }
} finally {
    if ($uploaded !== []) {
        $io->line();
        $io->info('cleaning up');
    }

    foreach ($uploaded as $attachmentId) {
        try {
            $xf->attachments()->delete($attachmentId, $key);

            $io->success(sprintf('   ✓ deleted attachment %d', $attachmentId));
        } catch (ExceptionInterface $cleanup) {
            $leaked = true;

            $io->error(sprintf('   ✗ CLEANUP FAILED for attachment %d - %s', $attachmentId, $cleanup::class));
            $io->error('     Remove it by hand. It is unassociated, so the forum will also expire it.');
        }
    }
}

if ($failure !== null || $leaked || $mismatch) {
    exit(1);
}
