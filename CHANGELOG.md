CHANGELOG
=========

Unreleased
----------

Initial development. Nothing is released, so nothing here is a change from anything.

* `Client`, `Config`, `Connection` and an `Authentication` interface with `ApiKey`,
  `SuperUserKey`, `BearerToken`, `ClientCredentials` and `Guest` implementations
* `Resource` base class and `Client::resource()` as the extension point for add-on endpoints
* hand-written resources: index, auth, me, users, threads, posts, forums, nodes,
  profile posts, profile post comments, conversations, conversation messages, alerts,
  search, attachments
* 33 entity classes generated from XenForo's OpenAPI specification by
  `tools/generate-schemas.php`, pinned to `resources/openapi.json` taken from
  https://github.com/xenforo-ltd/docs `static/api/openapi.json` on 2026-09-05
* `SpecConformanceTest` checks every endpoint the resources call against that specification -
  153 of the API's 162 endpoints are wrapped
* file uploads: `Upload`, `Multipart` and `Connection::postMultipart()`, covering the five
  core endpoints that take a file - `attachments/`, `attachments/new-key`, `me/avatar`,
  `users/{id}/avatar` and `threads/{id}/feature` - plus `threads/{id}/unfeature`
* `Support\Payload`, so a multipart body and a form-encoded one cannot name a field differently
* file downloads: `Connection::sendRaw()`, `Result\Download` and the three attachment
  endpoints that do not answer in JSON - `attachments/{id}/data`, `.../thumbnail` and
  `.../retina-thumbnail`
* XenForo Media Gallery and Resource Manager: `media`, `mediaAlbums`, `mediaCategories`,
  `mediaComments`, `resourceItems`, `resourceCategories`, `resourceReviews`,
  `resourceUpdates` and `resourceVersions` - all 62 endpoints of the nine XFMG/XFRM tags
* `Page::fromResponse()` takes a pagination key, for `media-albums/{id}/` - the one endpoint
  in the API that paginates two lists at once
* harness exercises: `index`, `read`, `pagination`, `encoding` and `upload`
