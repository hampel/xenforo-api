# hampel/xenforo-api

A PHP client for the XenForo REST API, over any PSR-18 HTTP client. No framework, no
hard-coded HTTP library, and no assumption that XenForo's own endpoints are the whole API -
because on a real forum they are not.

## Commands

```bash
composer check          # lint, analyse, test - what CI runs
composer test           # phpunit
composer analyse        # phpstan, level 8, PHP 8.3-8.5 in one pass
composer format         # pint
composer generate       # regenerate src/Generated/Schema from resources/openapi.json
```

## Layout

| path | what it is |
|---|---|
| `src/Client.php` | the entry point; named accessors and `resource()` |
| `src/Connection.php` | everything that touches HTTP |
| `src/Authentication/` | the four ways XenForo will authenticate a request, plus guest |
| `src/Resource/` | hand-written endpoint groups, and the `Resource` base class |
| `src/Generated/Schema/` | entity classes, generated - do not edit |
| `src/Result/` | `ApiResponse`, `ResponseMeta`, `Page`, `SiteInfo` |
| `resources/openapi.json` | XenForo's own specification, pinned |
| `tools/generate-schemas.php` | the generator |
| `harness/` | rig exercises - real calls to a real forum |

## What is generated and what is not

**Entities are generated. Endpoints are hand-written.** The specification describes 33
entities carrying some 640 fields; typing those by hand is work with no judgement in it and
every field is a chance to mistype a name no test would catch. The endpoints are the
opposite - what makes the client pleasant is deciding that a thread's posts are
`threads()->posts($id)`, and no generator has an opinion about that.

`resources/openapi.json` comes from https://github.com/xenforo-ltd/docs
(`static/api/openapi.json`). That is XenForo's documentation repository, not a versioned
artefact of the product, so the copy here is pinned deliberately: regenerating means
updating that file first and recording in the changelog which commit it came from.

## Extending it

A forum's API is whatever its add-ons say it is, so this package cannot be the complete
list of what any given forum offers. Two ways past it, neither needing a release:

```php
$xf->connection()->get('users/find-criteria', ['email' => $email])->data;   // once
$xf->resource(UserFindCriteria::class)->byEmail($email);                    // more than once
```

The second is the one to build on. A `Resource` subclass gets the pagination helpers, which
work for an endpoint this package has never heard of, because every list endpoint in XenForo
builds its pagination block with the same `AbstractController::getPaginationData()`.

`tests/Fixture/UserFindCriteria.php` is a worked example, modelled on a real add-on.

## Facts about the XenForo API worth not rediscovering

Each of these is asserted somewhere in `tests/` or `harness/`, so if one stops being true
something should say so.

- **The API is GET, POST and DELETE.** There are no PUT or PATCH endpoints and no non-POST
  endpoint takes a body: DELETE takes query parameters. Every write is a POST, including
  what would elsewhere be an update.
- **Bodies are form-encoded, never JSON.** XenForo will read a JSON body, but only on POST
  (`\XF\Http\Request::getPhpInputJson()`), so a JSON-first client works for creates and
  quietly does nothing for updates.
- **On PUT, PATCH and DELETE the Content-Type is compared with `===`.** XenForo parses
  those bodies itself in `convertCustomMethodPhpInput()` and a `; charset=utf-8` makes the
  comparison fail, discarding the body with no error. Core XenForo never meets this; an
  add-on endpoint can, which is why `Connection` writes the header itself.
- **A missing API key is not a 401.** `\XF\Api\App::validateRequest()` sets `apiKeyOmitted`
  and carries on as a guest, so unauthenticated endpoints answer normally and everything
  else answers 400 `no_api_key_in_request`.
- **Read the error code, not the status.** XenForo answers 400 for most caller mistakes -
  a missing input, a validation failure, a page past the end.
- **A page past the last one is an error, not an empty page.** `assertValidApiPage()`
  throws `invalid_page`, so "fetch until it comes back empty" ends in an exception.
- **Every response carries `XF-Used-Api-Version` and `XF-Latest-Api-Version`.** They are
  the only notice a client gets that the forum has moved on. `Connection` logs it.
- **`GET /index/`** reports the version, the key type and its scopes. It is the cheapest
  way to find out that a URL or a key is wrong.

## The harness

`vendor/bin/rig` lists the exercises. `index`, `read` and `pagination` are read-only.

`encoding` sends four requests to a real forum, none of which write anything - but a
rejected login is still a login attempt, so it is opt-in:

```bash
XENFORO_PROBE=1 vendor/bin/rig encoding
```

Under an agent it refuses even then, unless `XENFORO_AGENT_MAY_PROBE=1` is also set - on
the command line, for one run, never in `.env`. See `harness/lib/agent.php`.

**If an exercise fails for want of a credential, that is the guard working.** The rig does
not load `.env` in an agent session. Do not go looking for the key.
