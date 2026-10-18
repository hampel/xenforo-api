CHANGELOG
=========

Unreleased
----------

* DragonByte eCommerce: `$xf->ecommerce()` reaches its products, categories, downloads,
  licenses and reviews - all 28 of its endpoints. A third-party add-on, so its classes live
  under `Endpoint\DBTech\eCommerce` and `Generated\Schema\DBTech\eCommerce` rather than beside
  XFMG and XFRM
* Its entities are generated from `resources/dbtech-ecommerce.openapi.json`, transcribed by
  hand from eCommerce 5.4.2's own API annotations, and `SpecConformanceTest` holds the
  eCommerce endpoints to it in both directions
* eCommerce refuses any request that does not identify the board and platform it is for, so
  `Identity` carries the four `X-DragonByte-*` headers and `identifiedAs()` - on
  `$xf->ecommerce()` or any one endpoint - sends them on every request
* `composer generate` builds from more than one specification, and an entity in one may refer
  to an entity in another
* The `dbtech-ecommerce` harness exercise, read-only

1.2.2 (2026-10-08)
------------------

* `SiteInfo` reads the scopes a key holds. XenForo sends `key.scopes` as a map of scope
  to `true`, not the list its documentation describes, so `$scopes` was always empty and
  `hasScope()` false for every key that is not allowed all scopes. Both shapes are read
* The error-response log line carries the error codes, not the response body

1.2.1 (2026-09-13)
------------------

* A 4xx is logged at `info`, not `error`: XenForo answered, and the typed exception
  carries the answer. `error` is for a forum that did not - a transport failure, a 5xx,
  a 2xx that is not JSON
* The query string is left out of every logged URI, since on a lookup endpoint it is
  the lookup

1.2.0 (2026-09-13)
------------------

* `Client::withKey($baseUri, $key, $client)` - the short form for an API key, so the
  ordinary case no longer names a `Config` and an `ApiKey` to accept both defaults
* README states which XenForo versions answer which endpoints - 154 of the 162 on 2.3,
  144 on 2.2, 124 on 2.1 - measured on a real forum of each
* The `write` exercise no longer stops at `threads/{id}/mark-read` on a forum older
  than 2.2

1.1.0 (2026-09-09)
------------------

* The PSR-17 factories are optional on `Client`: when not given, Guzzle's, Nyholm's or
  Diactoros' is found automatically
* Every generated entity, `SiteInfo`, `SiteStats`, `Embed` and `ApiResponse` implement
  `\JsonSerializable`, serialising to the payload as it arrived

1.0.0 (2026-09-09)
------------------

* Initial release
* `src/Generated/Schema` generated from `resources/openapi.json`, taken from
  https://github.com/xenforo-ltd/docs (`static/api/openapi.json` at commit `1f43b195`,
  2026-03-18)
