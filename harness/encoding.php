<?php

/**
 * Exercise: prove against a real forum that a decorated Content-Type loses the request body.
 *
 * This is the claim the whole package rests on and the one nothing local can settle.
 * Connection::FORM_CONTENT_TYPE is a factual assertion about somebody else's PHP:
 * \XF\Http\Request::convertCustomMethodPhpInput() compares $_SERVER['CONTENT_TYPE'] with
 * `===` against 'application/x-www-form-urlencoded', so a "; charset=utf-8" that most HTTP
 * clients would add makes the comparison fail and the body vanish. The suite asserts the
 * header we send; it cannot assert what XenForo then does with it, because the stub is
 * built from the same belief the code is.
 *
 * HOW IT PROBES WITHOUT WRITING ANYTHING
 *
 * POST auth/ with a login that cannot exist. The two outcomes are distinguishable and
 * neither creates, changes or deletes a thing:
 *
 *   body arrived  -> XenForo validates the login and rejects it
 *   body lost     -> XenForo never sees `login` at all and reports a REQUIRED INPUT missing
 *
 * The error code is the signal, and the second is the dangerous one - on a real endpoint it
 * would be a 200 that did nothing. Four requests go out: both content types on POST, where
 * PHP itself parses the body and the decoration should be harmless, and both on DELETE,
 * where XenForo parses it by hand and the decoration should be fatal. Printing all four
 * together is what makes the difference legible rather than asserted.
 *
 * NOT READ-ONLY IN ONE RESPECT: a rejected login is a login attempt, and a forum logs those
 * and rate-limits by IP. limit_ip is 0 here so the probe cannot lock anyone out, and the
 * username is random so there is no account to lock. Still, this points at a real forum -
 * hence the guard below.
 *
 * Needs XENFORO_URL and a SUPER-USER XENFORO_API_KEY (auth/ is super-user only).
 * Set XENFORO_PROBE=1 to run it. Under an agent, XENFORO_AGENT_MAY_PROBE=1 as well, on the
 * command line and never in .env.
 *
 * @var Hampel\Rig\Io $io
 */

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\HttpFactory;
use Hampel\XenForo\Api\Config;
use Hampel\XenForo\Api\Connection;

require __DIR__ . '/lib/agent.php';
require __DIR__ . '/lib/client.php';

$io->title('xenforo-api · encoding');

/**
 * The mode goes above the work, not below it, so nobody reads a refused run as a result.
 *
 * @return array{bool, string}
 */
$mode = static function (): array {
    if (getenv('XENFORO_PROBE') !== '1') {
        return [false, 'refused - set XENFORO_PROBE=1 to send the four probe requests'];
    }

    if (harness_agent_refuses('XENFORO_AGENT_MAY_PROBE')) {
        return [false, 'refused - XENFORO_PROBE ignored in an agent session'];
    }

    return [true, 'probing - four requests will reach the forum, none of which write anything'];
};

[$proceed, $description] = $mode();

$io->value('mode', $description);

$config = new Config(harness_forum_url($io));
$key = getenv('XENFORO_API_KEY');

if (!is_string($key) || $key === '') {
    $io->error('XENFORO_API_KEY is not set. Copy .env.example to .env beside the package.');

    exit(1);
}

$io->value('forum', $config->baseUri);
$io->line();

if (!$proceed) {
    $io->warn('Nothing was sent, so this run answers none of the questions in the docblock -');
    $io->warn('in particular it does NOT show that the Content-Type rule still holds.');

    exit(0);
}

$guzzle = new Guzzle(['http_errors' => false]);
$factory = new HttpFactory();

$decorated = Connection::FORM_CONTENT_TYPE . '; charset=utf-8';
$login = 'zzz-no-such-user-' . bin2hex(random_bytes(4));
$body = http_build_query(['login' => $login, 'password' => 'not-a-password', 'limit_ip' => '0']);

/**
 * @return array{status: int, codes: list<string>, message: string}
 */
$probe = static function (string $method, string $contentType) use ($guzzle, $factory, $config, $key, $body): array {
    $request = $factory->createRequest($method, $config->resolve('auth/'))
        ->withHeader('XF-Api-Key', $key)
        ->withHeader('Accept', 'application/json')
        ->withHeader('Content-Type', $contentType)
        ->withBody($factory->createStream($body));

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

$results = [
    'POST bare' => $probe('POST', Connection::FORM_CONTENT_TYPE),
    'POST decorated' => $probe('POST', $decorated),
    'DELETE bare' => $probe('DELETE', Connection::FORM_CONTENT_TYPE),
    'DELETE decorated' => $probe('DELETE', $decorated),
];

$lines = [];

foreach ($results as $label => $result) {
    $lines[$label] = sprintf(
        '%d  [%s]  %s',
        $result['status'],
        $result['codes'] === [] ? '' : implode(', ', $result['codes']),
        $result['message']
    );
}

$io->values($lines);
$io->line();

// XenForo's "you did not send a required field" code. Its presence means the body was not
// there to be read - which for a write endpoint would have been a 200 that did nothing.
$lost = static fn (array $result): bool => in_array('required_input_missing', $result['codes'], true);

$io->info('Reading the four:');
$io->line();

if ($lost($results['POST bare'])) {
    $io->error('✗ Even a bare POST lost its body. Something more fundamental is wrong than the');
    $io->error('  Content-Type rule - check the URL reaches the API and not the forum front end.');

    exit(1);
}

$io->success('✓ POST bare        - body arrived, as it must for every write in this package');

$io->line($lost($results['POST decorated'])
    ? '  ! POST decorated   - body LOST. PHP is stricter here than expected; worth writing up.'
    : '  ✓ POST decorated   - body arrived. Expected: PHP parses a POST body itself and ignores'
    . PHP_EOL . '                       the charset parameter, so the decoration is harmless on POST.');

$io->line();
$io->info('The DELETE pair is the one that matters - XenForo parses those bodies by hand:');
$io->line();

if ($lost($results['DELETE decorated']) && !$lost($results['DELETE bare'])) {
    $io->success('✓ The rule holds. A decorated Content-Type loses the body on DELETE and a bare one');
    $io->success('  does not, which is exactly why Connection writes the header itself.');
} elseif (!$lost($results['DELETE decorated']) && !$lost($results['DELETE bare'])) {
    $io->warn('! Both DELETEs kept their body. XenForo no longer compares the header exactly,');
    $io->warn('  or something in front of the forum is normalising it. Nothing breaks - the bare');
    $io->warn('  header is still correct - but FORM_CONTENT_TYPE\'s docblock now overstates the');
    $io->warn('  hazard and should be re-checked against the current XenForo source.');
} else {
    $io->warn('! Neither outcome this exercise knows about. Read the four lines above directly.');
}
