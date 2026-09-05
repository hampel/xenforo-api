<?php

/**
 * Not an exercise. Discovery is glob('harness/*.php'), top level only, so a file down here
 * is required by the exercises that need it rather than listed as one of them.
 *
 * This is the third of the three layers that keep an accident out of this harness:
 *
 *   1. rig itself does not load the package's .env when CLAUDECODE is set. Nothing is
 *      needed from the package for that one, which is what makes it the layer that
 *      protects a harness whose author never thought about any of this.
 *   2. each exercise defaults to the harmless thing - here, a read - and anything that
 *      writes to the forum is opt-in.
 *   3. this: the opt-in itself is refused under an agent.
 *
 * Two and three look redundant and are not. The opt-in in (2) lives in the .env of whoever
 * owns the credentials, and it generally says yes, because that is how they run their own
 * exercises - so (2)'s default never applies to the file that actually exists. An agent
 * inherits that authorisation without having made the decision.
 */

/**
 * Whether the real effect must be refused: an agent is running, and has not been told that
 * this once it may.
 *
 * CLAUDECODE is a fact about who is running the command, which is the one thing a stale
 * .env cannot fake. The variable named here is the deliberate act, and belongs on the
 * command line and never in .env - a persisted one would recreate the very problem.
 *
 * Both reads fail safe. An absent or renamed CLAUDECODE falls back to the ordinary opt-in
 * - which is still the harmless default unless asked - rather than to "assume human,
 * proceed". And the override is exactly '1': an environment variable is always a string,
 * so a loose test would make =0 mean yes.
 */
function harness_agent_refuses(string $variable): bool
{
    return getenv('CLAUDECODE') !== false && getenv($variable) !== '1';
}
