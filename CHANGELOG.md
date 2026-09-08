CHANGELOG
=========

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
