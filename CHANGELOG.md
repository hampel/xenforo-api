CHANGELOG
=========

Unreleased
----------

* The PSR-17 factories are optional on `Client`: when not given, Guzzle's, Nyholm's or
  Diactoros' is found automatically
* Every generated entity, `SiteInfo`, `SiteStats`, `Embed` and `ApiResponse` implement
  `\JsonSerializable`, serialising to the payload as it arrived
* `EndpointNotFoundException`, for the 404 that means the route is missing rather than the
  record; `apiFind()` and `apiFindResponse()` raise it rather than returning null
* `MalformedResponseException`, for a successful status whose body is not JSON
* `TooManyRequestsException`, for a 429
* `ApiResponse::has()`, and `Endpoint::apiFindResponse()`
* `Client::withCredential()`
* Harness: a `write` exercise; `encoding` now probes a DELETE body against an attachment
  it owns

1.0.0 (2026-09-09)
------------------

* Initial release
* `src/Generated/Schema` generated from `resources/openapi.json`, taken from
  https://github.com/xenforo-ltd/docs (`static/api/openapi.json` at commit `1f43b195`,
  2026-03-18)
