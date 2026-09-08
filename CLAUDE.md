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
| `src/Client.php` | the entry point; named accessors and `endpoint()` |
| `src/Connection.php` | everything that touches HTTP |
| `src/Authentication/` | the four ways XenForo will authenticate a request, plus guest |
| `src/Endpoint/` | hand-written endpoint groups, and the `Endpoint` base class - every endpoint in the pinned specification |
| `src/Endpoint/Media*`, `Resource*` | XFMG and XFRM - add-ons, wrapped for convenience |
| `src/Generated/Schema/` | entity classes, generated - do not edit |
| `src/Result/` | what an endpoint answers with, where that is not an entity |
| `src/Exception/` | the hierarchy, from `XenForoException` down |
| `src/Upload.php`, `src/Multipart.php` | files going out, and the `multipart/form-data` body that carries them |
| `src/Support/` | coercion, and the field naming shared by both body encodings |
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

## XFMG and XFRM

The Media Gallery and Resource Manager resources are wrapped, and they are add-ons rather
than core: a forum without them answers 404 for every one of those paths, which is
indistinguishable from a missing record. They are in the package because they are bundled
by XenForo and widely installed, not because the package promises to wrap add-ons - the
mechanism below is what does that.

Two shapes there exist nowhere else in the API, and both are noted where they live:
`media-albums/{id}/` paginates two lists at once (`media_pagination`,
`comment_pagination`, which is why `Page::fromResponse()` takes a pagination key), and a
resource version with a `download_url` is hosted off-site and answers a download with a
redirect.

## Extending it

A forum's API is whatever its add-ons say it is, so this package cannot be the complete
list of what any given forum offers. Two ways past it, neither needing a release:

```php
$xf->connection()->get('users/find-criteria', ['email' => $email])->data;   // once
$xf->endpoint(UserFindCriteria::class)->byEmail($email);                    // more than once
```

The second is the one to build on. An `Endpoint` subclass gets the pagination helpers, which
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
  quietly does nothing for updates. The exception is the eight endpoints that take a file,
  which declare `multipart/form-data`.
- **Multipart is POST-only, structurally.** PHP populates `$_FILES` for a POST and for
  nothing else, and `convertCustomMethodPhpInput()` parses one encoding and knows nothing
  about files - so a multipart PUT arrives with neither its files nor its fields and
  answers 200 having done nothing. `Connection::withMultipart()` refuses a non-POST.
- **Three endpoints do not answer in JSON.** `attachments/{id}/data` returns the file on a
  200; the two thumbnail endpoints return a **301** whose `Location` is the whole output.
  `Connection::sendRaw()` is the path for those - it hands the response back undecoded and
  treats a redirect as a success, where `send()` throws on one. Note the thumbnail
  endpoints only work through a client that does not follow redirects; the same URLs are on
  the `Attachment` entity, which is why the methods say to prefer that.
- **A file is judged by its filename, not by its declared type.** `getFile()` builds its
  `\XF\Http\Upload` from the temporary file and the filename; the part's own Content-Type
  is read in one case only, a part named `blob`. So `Upload` defaults to
  `application/octet-stream` and the extension is what has to be right.
- **On PUT, PATCH and DELETE the Content-Type is compared with `===`.** XenForo parses
  those bodies itself in `convertCustomMethodPhpInput()` and a `; charset=utf-8` makes the
  comparison fail, discarding the body with no error. Core XenForo never meets this; an
  add-on endpoint can, which is why `Connection` writes the header itself.
- **The OAuth2 token endpoints take no credential.** `OAuth2Controller` declares
  `allowUnauthenticatedRequest()`, and reads `client_id`/`client_secret` from the request's
  own input - not from the HTTP Basic header `ClientCredentials` sends. That header is read
  elsewhere, by `App::validateUserFromApiHeader()`, and resolves to a guest.
- **A refresh replaces both tokens** and revokes the old access token, so the refresh token
  that comes back is not the one that went in.
- **Introspection answers `active: false` rather than erroring**, and revocation answers
  success whether or not the token existed. Neither raises; both are RFC 7662 by design.
- **A missing API key is not a 401.** `\XF\Api\App::validateRequest()` sets `apiKeyOmitted`
  and carries on as a guest, so unauthenticated endpoints answer normally and everything
  else answers 400 `no_api_key_in_request`.
- **A 2xx whose body is not JSON is somebody else's answer.** A maintenance page, a WAF
  challenge, a CDN interstitial and a truncated response are all a 200 with HTML or
  nothing in it. `Connection::send()` raises `MalformedResponseException` rather than
  decode them as an empty body, because empty would reach every caller as "no such
  record". A 204 is the one success with a legitimately empty body.
- **A field the credential may not see is omitted, not blanked.** XenForo builds results
  with `includeColumn()`, so `email`, `user_state`, `user_group_id` and `is_banned` are
  absent from the JSON rather than null when the key lacks the standing.
  `ApiResponse::has()` is how to tell; `value()` with a default cannot.
- **A missing route and a missing record are different 404s.** `requested_page_not_found`
  is a record (or, on some controllers, one the acting user may not see);
  `endpoint_not_found` is a path or action the forum does not have - in 2.2 and 2.3 alike.
  `apiFind()` returns null for the first and raises `EndpointNotFoundException` for the
  second, because an add-on endpoint on a forum without the add-on is configuration, not
  absence. Measured live on 2.3.12.
- **Read the error code, not the status.** XenForo answers 400 for most caller mistakes -
  a missing input, a validation failure, a page past the end.
- **`GET threads/` is the recently-active list, not the thread list.** Unfiltered and
  under the default sort, `ThreadsController::setupThreadFinder()` adds
  `last_post_date > getReadMarkingCutOff()` - the read-marking window, 30 days by default -
  and answers 200 with `total: 0` on a quiet forum. `forums/{id}/threads` applies no
  cutoff. Found live, on a forum whose two threads were older than the window.
- **A page walk can repeat and skip items.** XenForo sorts most lists by a date with no
  tiebreaker - `applyThreadListSort()` orders by `last_post_date` alone - and MySQL orders
  ties as it likes across `LIMIT` pages. Seen live after seeding 25 threads in three
  seconds: thread 10 on both pages, thread 6 on neither. `apiEach()` cannot see it;
  de-duplicate by id where completeness matters. The `pagination` exercise reports it.
- **A page past the last one is an error, not an empty page.** `assertValidApiPage()`
  throws `invalid_page`, so "fetch until it comes back empty" ends in an exception.
- **Every response carries `XF-Used-Api-Version` and `XF-Latest-Api-Version`.** They are
  the only notice a client gets that the forum has moved on. `Connection` logs it.
- **`GET /index/`** reports the version, the key type and its scopes. It is the cheapest
  way to find out that a URL or a key is wrong. `GET /stats/` is nearly as cheap and comes
  from the forum's cached statistics rather than from a count.
- **`oembed/` embeds the forum's OWN content.** It routes the URL through XenForo's public
  router, so a link to anywhere else answers `requested_content_unavailable`. Whether it
  needs a key at all is the board's `allowExternalEmbed` option.
- **A search forum has no sticky threads**, though the specification says it does - the
  annotation the docs were built from is shared with the real forum endpoint. It is also
  the one list a super-user bypass does not widen, being served from a per-user cache.

## The harness

`vendor/bin/rig` lists the exercises. `index`, `read` and `pagination` are read-only.

`encoding` sends six requests to a real forum: four login attempts that cannot succeed,
and one attachment it uploads and then deletes through the header under test. Nothing
pre-existing is touched, but it writes, so it is opt-in:

```bash
XENFORO_PROBE=1 vendor/bin/rig encoding
```

`upload` genuinely writes: it uploads two files, reads one back and deletes them again,
which is the only way to find out whether a real forum accepts the multipart body this
package builds and returns the same bytes through the download path.

```bash
XENFORO_UPLOAD=1 vendor/bin/rig upload
```

`write` creates content and leaves it - a thread, replies, a reaction, an edit, and with
`XENFORO_WRITE_USER=1` a user who posts the replies, which is the live test of
`actingAs()`. `XENFORO_WRITE_THREADS=25` seeds enough for `pagination` to have a second
page. It never deletes anything, and it is for a development forum only:

```bash
XENFORO_WRITE=1 XENFORO_WRITE_USER=1 XENFORO_WRITE_THREADS=25 vendor/bin/rig write
```

Under an agent all three refuse even then, unless `XENFORO_AGENT_MAY_PROBE=1`,
`XENFORO_AGENT_MAY_UPLOAD=1` or `XENFORO_AGENT_MAY_WRITE=1` is also set - on the command
line, for one run, never in `.env`. See `harness/lib/agent.php`.

**If an exercise fails for want of a credential, that is the guard working.** The rig does
not load `.env` in an agent session. Do not go looking for the key.
