# hampel/xenforo-api

A PHP client for the [XenForo REST API](https://docs.xenforo.com/api) — core endpoints,
add-on extensions and OAuth2 — over any PSR-18 HTTP client.

## Installation

```bash
composer require hampel/xenforo-api
```

PHP 8.3 or later. The package requires four PSR interfaces and nothing else, so you bring
your own HTTP client:

```bash
composer require guzzlehttp/guzzle    # or any PSR-18 implementation
```

## Getting started

```php
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\HttpFactory;
use Hampel\XenForo\Api\Authentication\ApiKey;
use Hampel\XenForo\Api\Client;
use Hampel\XenForo\Api\Config;

$factory = new HttpFactory();      // PSR-17, both roles

$xf = new Client(
    new Config('https://forum.example.com'),
    new ApiKey($key),
    new Guzzle(),
    $factory,
    $factory
);

$thread = $xf->threads()->get(1234);

echo $thread->title;
```

`Config` takes the board URL or the API URL — either works, and the `/api` suffix is added
if it is missing. Create the key in the forum's admin panel under **Setup → API keys**.

The first call worth making against a forum you have just been handed is `index()`, which
tells you whether the URL reaches an API at all, what version is behind it, and what the key
may do:

```php
$info = $xf->index()->get();

$info->version();          // '2.3.12'
$info->keyType;            // 'guest', 'user' or 'super'
$info->hasScope('user:write');
```

## Authenticating

XenForo accepts four credentials on the same endpoints, and which one you hold changes what
you may ask for. Each is a class rather than a flag, because "act as this user" and "bypass
permissions" belong to a super-user key and to nothing else.

```php
new ApiKey($key);                              // a guest or user key
new SuperUserKey($key, actingAs: 42);          // acts as user 42
new SuperUserKey($key, bypassPermissions: true);
new BearerToken($accessToken);                 // OAuth2
new ClientCredentials($clientId, $clientSecret);
new Guest();                                   // no credential
```

A super-user key with no `actingAs` acts as a **guest**, which is XenForo's own default and
rarely what the key was created for.

To act as a series of users, ask for a client per user rather than mutating one — a request
made on behalf of one user then cannot leak into the next:

```php
foreach ($userIds as $userId) {
    $xf->actingAs($userId)->me()->update(['timezone' => 'Australia/Sydney']);
}
```

## Pagination

Every list endpoint returns the same pagination block, so every list method here returns the
same `Page`:

```php
$page = $xf->users()->list(2);

$page->currentPage;    // 2
$page->lastPage;       // 9
$page->total;          // 173
$page->hasMore();      // true

foreach ($page as $user) { … }
```

To walk the lot, `each()` returns a generator and fetches a page at a time, only as far as
it is consumed:

```php
foreach ($xf->users()->each() as $user) { … }
```

Use it rather than writing the loop by hand. **Asking XenForo for a page past the last one
is an error, not an empty result** — `invalid_page`, HTTP 400 — so the obvious "fetch until
it comes back empty" ends every complete traversal in an exception.

## Uploading files

Attachments, avatars and featured-content images are sent as `multipart/form-data`, and an
`Upload` describes the file without reading it — the bytes are streamed through your PSR-17
factory when the body is built, so an attachment larger than memory costs nothing extra.

```php
use Hampel\XenForo\Api\Upload;

$xf->me()->uploadAvatar(Upload::fromPath('/tmp/avatar.png'));
```

An attachment cannot be posted with the content it belongs to. It goes up first, against a
key, and the key is handed to whatever creates the content:

```php
$key = $xf->attachments()->newKey('post', ['thread_id' => 42])['key'];

$xf->attachments()->upload($key, Upload::fromPath('/tmp/screenshot.png'));

$xf->posts()->create(42, 'See attached', $key);
```

The `context` a key is created with is what the forum checks permission against, so it has
to describe the content the attachment will end up on — `thread_id` for a reply, `post_id`
for an edit, `node_id` for a new thread. A wrong context is refused when the key is created
rather than when it is used.

`Upload::fromString()` and `Upload::fromStream()` cover content this process already holds.
The content type an upload declares is **not** what XenForo judges it by: `getFile()` builds
its `\XF\Http\Upload` from the temporary file and the filename, so it is the extension
that decides whether the forum accepts the file at all. Get the filename right; the type
defaults to `application/octet-stream` and that is honest.

Two things follow from multipart that are worth knowing:

- **It is POST-only, structurally.** PHP populates `$_FILES` for a POST and nothing else,
  and XenForo's fallback for the other methods parses one encoding and has no concept of a
  file — so a multipart PUT arrives carrying neither its files nor its fields and answers
  200 having done nothing. `Connection::withMultipart()` refuses a non-POST rather than
  send one.
- **`POST threads/{id}/feature` takes a multipart body whether or not you give it an
  image**, because the encoding is a property of the endpoint rather than of the call.

All eight of the API's multipart endpoints are wrapped: attachments, both avatars, and the
`feature` endpoints on threads, media and resources.

## Downloading files

`attachments/{id}/data` answers with the file rather than with JSON, so it goes through
`Connection::sendRaw()` — the response comes back whole, with its body stream unread:

```php
$download = $xf->attachments()->download($attachmentId);

$download->saveTo('/tmp/' . $attachmentId . '.bin');   // a chunk at a time
$download->contents();                                 // or the lot, as a string
$download->filename;                                   // what the forum called it
$download->contentType;                                // what it will serve it as
```

`download()` never reads the body itself, so a 40MB attachment goes to disk without ever
being a PHP string. The name and type come from the response rather than from the
`Attachment` entity and answer a slightly different question: XenForo decides at download
time whether a file is safe to display inline, so an image comes back as its real type and
anything else as `application/octet-stream`, whatever it really is.

An attachment that has not been associated with content yet needs the key it was uploaded
against — `download($id, $key)` — for the same reason reading its record does.

The two thumbnail endpoints answer with a **301** whose `Location` header is the entire
output, so `thumbnailUrl()` and `retinaThumbnailUrl()` only work through an HTTP client that
does not follow redirects, and a PSR-18 client is free to follow them — most do by default.
Where the client has followed one there is no way to recover the URL from a PSR-7 response,
and the methods say so rather than guess. **`$xf->attachments()->get($id)->thumbnail_url` is
the same answer** out of a request you have probably already made, and it does not depend on
how the client is configured; prefer it.

## The bundled add-ons: Media Gallery and Resource Manager

XFMG and XFRM are add-ons, not core, and a forum may have neither. Their endpoints are
wrapped here as a convenience — this is the same extension mechanism described below, and
these classes are what a third party's own `Resource` would look like.

| accessor | tag | what it covers |
|---|---|---|
| `media()` | Media | media items, their comments, upload and download, reactions, featuring |
| `mediaAlbums()` | Media albums | albums, their media and comments, reactions |
| `mediaCategories()` | Media categories | the category tree, and a category's contents |
| `mediaComments()` | Media comments | comments on either a media item or an album |
| `resourceItems()` | Resources | resources, their versions, updates and reviews |
| `resourceCategories()` | Resource categories | the category tree, and a category's resources |
| `resourceReviews()` | Resource reviews | reviews, and the author's reply to one |
| `resourceUpdates()` | Resource updates | the posts announcing a change to a resource |
| `resourceVersions()` | Resource versions | releases, and downloading a release's files |

```php
$xf->media()->create(['album_id' => 4, 'title' => 'Sunset'], Upload::fromPath('/tmp/sunset.jpg'));

$xf->resourceItems()->versions($resourceId);
$xf->resourceVersions()->download($versionId, $fileId)->saveTo('/tmp/release.zip');
```

**A forum without the add-on answers 404 for every one of these paths**, which is the same
answer a missing record gets — so `find()` returning `null` cannot be read as "no such
media item" unless you already know the gallery is installed.
`$xf->index()->get()->hasScope('media:read')` tells the two apart in one request.

`resourceItems()` is the one accessor not named after its tag. `Resource` is already the
base class of every resource here and `resource()` is already the extension point, so
`resources()` would make one word mean two unrelated things; XenForo's own entity is
`XFRM\Entity\ResourceItem`, and this borrows that.

Two things in these add-ons are shaped unlike anything in core. `media-albums/{id}/` is the
only endpoint in the API that paginates **two** lists at once, so its pagination blocks are
named `media_pagination` and `comment_pagination` and `withContent()` pages them
independently. And a resource version whose `download_url` is set is hosted somewhere else
entirely — XFRM answers with a redirect rather than bytes, so check that field before
calling `download()`.

## Errors

```php
use Hampel\XenForo\Api\Exception\{ApiException, ClientException, NotFoundException, RequestException};

try {
    $xf->threads()->get(1234);
} catch (NotFoundException $e) {
    // no such thread - or the acting user may not see it, which is the same 404
} catch (ClientException $e) {
    // any other 4xx. Read the code, not the status:
    if ($e->hasCode('invalid_page')) { … }
} catch (RequestException $e) {
    // never reached the forum: DNS, TLS, a connect timeout. Safe to retry.
}
```

The hierarchy is `RuntimeException` → `XenForoException` → `ApiException` →
`ClientException` (4xx) or `ServerException` (5xx), with `NotAuthenticatedException` (401),
`NotPermittedException` (403) and `NotFoundException` (404) under `ClientException`. Every
one of them implements `ExceptionInterface`, so a consumer can catch the package's failures
with one clause and let everything else through.

**Read the code rather than the status.** XenForo answers 400 for most things a caller got
wrong — a missing required input, a validation failure, a page past the end — so the status
distinguishes very little and `$e->hasCode(…)` distinguishes exactly.

## Extending it, for add-on endpoints

A XenForo forum's API is whatever its add-ons say it is. An add-on that extends
`\XF\Api\Controller\UsersController` with an `actionGetFindCriteria()` adds
`GET users/find-criteria` to that forum and to no other, and no client can anticipate it. So
the extension point is a class, not a registration.

For a one-off, call it directly:

```php
$xf->connection()->get('users/find-criteria', ['email' => $email])->data;
```

For anything used more than once, write a `Resource`:

```php
use Hampel\XenForo\Api\Generated\Schema\User;
use Hampel\XenForo\Api\Resource\Resource;

final class UserFindCriteria extends Resource
{
    public function byEmail(string $email): ?User
    {
        return $this->apiFind('users/find-criteria', ['email' => $email], 'user', User::fromArray(...));
    }
}

$xf->resource(UserFindCriteria::class)->byEmail('someone@example.com');
```

There is nothing to register, no container and no string keys — the class *is* the
registration, so a third party can ship one in its own package and a consumer's static
analysis follows the return type all the way through. `Client::resource()` memoises by class
name, and the built-in accessors like `users()` are the same mechanism with a shorter name.

Subclassing buys you `apiPaginate()`, `apiEach()` and `apiFind()`, which work unchanged for
an endpoint this package has never heard of, because every paginated endpoint in XenForo —
core, first-party add-on, third-party add-on — builds its pagination block with the same
`AbstractController::getPaginationData()`.

## Entities

The 33 entity classes under `Hampel\XenForo\Api\Generated\Schema` are generated from
XenForo's own OpenAPI specification. Field names are XenForo's, unchanged, so they can be
read against the API documentation:

```php
$thread->thread_id;
$thread->Forum->title;      // nested entities are resolved
$thread->raw['whatever'];   // fields no specification mentions
```

**Every field is nullable, and that is not defensiveness.** A XenForo API result is not a
fixed record: it varies by verbosity, by what the acting user may see, and by which add-ons
the forum has installed. An add-on can add fields to any entity by extending its
`toApiResult()`, so each entity also keeps the array it was built from in `->raw` and
nothing is ever lost for not being in a specification.

## Things about this API worth knowing before you meet them

- **The API is GET, POST and DELETE.** There are no PUT or PATCH endpoints, and no non-POST
  endpoint takes a body — DELETE takes query parameters. Every write is a POST, including
  what would elsewhere be an update.
- **Bodies are form-encoded, never JSON** — or multipart, for the eight endpoints that take
  a file. XenForo will read a JSON body, but only on POST, so a JSON-first client works for
  creates and silently does nothing for updates. This package sends form-encoded otherwise
  and writes the `Content-Type` itself, exactly, with no `charset` parameter — on a PUT,
  PATCH or DELETE, XenForo compares that header with `===` and a decorated one makes the
  body vanish with no error at all.
- **A file is judged by its filename, not by the type it declares.** XenForo reads the
  part's own `Content-Type` in exactly one case — a part named `blob`, where it invents an
  extension for a file the browser did not name.
- **A missing API key is not a 401.** XenForo treats it as a guest and carries on, so
  unauthenticated endpoints answer normally.
- **Every response carries `XF-Used-Api-Version` and `XF-Latest-Api-Version`**, reachable on
  `$response->meta`. They are the only notice a client ever gets that the forum now offers
  an API version newer than the one it is speaking. `Connection` logs it when it happens.
- **Pin an API version if you need one**: `new Config($url, version: 1)` produces
  `/api/v1/…` URLs.

## Logging

Every constructor takes an optional PSR-3 logger. Requests are logged at `debug`, failures
at `error`, and a forum that has outgrown the pinned API version at `info`. Credentials are
never logged — `Authentication::describe()` names the credential type and nothing more.

## Testing

```bash
composer check
```

The suite needs no network: PSR-18 is a one-method interface, so the seam the package
exposes to its consumers is the seam the tests drive it through.

`SpecConformanceTest` is the one worth knowing about. It reads the paths out of the resource
classes and checks each against XenForo's specification, because the resources are
hand-written and a mistyped path is a 404 at runtime that every stubbed test passes either
way.

## Exercising it against a real forum

`harness/` holds [`hampel/rig`](https://github.com/hampel/rig) exercises — real calls to a
real forum, which is the only thing that can tell you whether an assumption about somebody
else's API is still true. `vendor/bin/rig` lists them. Copy `.env.example` to `.env` first.

## License

MIT. See [LICENSE.md](LICENSE.md).
