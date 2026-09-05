CHANGELOG
=========

Unreleased
----------

Initial development. Nothing is released, so nothing here is a change from anything.

* `Client`, `Config`, `Connection` and an `Authentication` interface with `ApiKey`,
  `SuperUserKey`, `BearerToken`, `ClientCredentials` and `Guest` implementations
* `Resource` base class and `Client::resource()` as the extension point for add-on endpoints
* hand-written resources: index, auth, me, users, threads, posts, forums, nodes,
  conversations, conversation messages, alerts, search
* 33 entity classes generated from XenForo's OpenAPI specification by
  `tools/generate-schemas.php`, pinned to `resources/openapi.json` taken from
  https://github.com/xenforo-ltd/docs `static/api/openapi.json` on 2026-09-05
* `SpecConformanceTest` checks every endpoint the resources call against that specification
* harness exercises: `index`, `read`, `pagination` and `encoding`
