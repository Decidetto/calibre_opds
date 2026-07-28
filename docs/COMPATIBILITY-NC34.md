# Nextcloud 34 compatibility report

## Scope and provenance

- Upstream: `https://gitlab.com/oldnomad/calibre_opds.git`
- Baseline: `bb2395c3d7f685a82878e78eb6feef1022d110c7`
- Baseline checked: 2026-07-28
- Upstream `master` at check time: `bb2395c3d7f685a82878e78eb6feef1022d110c7`
- Newer upstream commits included: none
- Newer upstream commits excluded: none
- Target: `nextcloud:34.0.2-apache@sha256:e93ccfc952c95f18175f3d297fb2f60c35070c05ca976050c250a9ddab793e75`
- Maintenance release: app version `0.0.7`, tag pattern `v0.0.7-nc34.N`

The patch retains the upstream architecture: a Nextcloud app using per-user
configuration, the public Files API, read-only PDO SQLite access, and
AppFramework controllers. It adds no service, datastore, cache, proxy,
telemetry, or outbound request.

## Source changes from the upstream baseline

| Path | Change | Requirement |
|---|---|---|
| `appinfo/info.xml` | Raise the app version, add NC34, and align PHP metadata with the tested 8.2–8.5 matrix and the PHP 8.5 target image. | R-101, R-105, R-501 |
| `appinfo/routes.php` | Constrain acquisition format route values. | R-302 |
| `composer.json`, `composer.lock` | Align PHP constraints and make test commands cross-platform and lock-file based. | R-105, R-401 |
| `lib/Controller/OpdsController.php` | Replace legacy security annotations with NC34 attributes; keep explicit authentication before catalogue access; classify missing/invalid library failures; sanitize logging; constrain formats; add safe download headers. | R-104, R-203, R-204, R-207, R-208, R-210, R-303, R-304 |
| `lib/Controller/SettingsController.php` | Use the native security attribute and return a bounded 400 response for unsafe paths. | R-104, R-210, R-302 |
| `lib/Service/SettingsService.php` | Default to `Books/Calibre`, validate stored and submitted paths, resolve only below the authenticated user's Files root, and avoid logging paths. | R-201, R-301, R-302, R-304 |
| `lib/Util/LibraryPath.php` | Centralize strict relative-path normalization for settings and database-derived file paths. | R-302 |
| `lib/Calibre/CalibreDB.php` | Open production databases with read-only SQLite flags, enable `query_only`, use a bounded busy timeout, reject malformed/unsupported schemas, and select the non-deprecated `Pdo\Sqlite` API where available while retaining PHP 8.2–8.3 compatibility. | R-105, R-202, R-210, R-407 |
| `lib/Calibre/CalibreSearch.php` | Preserve upstream regular-expression search semantics while bounding PCRE work and falling back safely for malformed patterns. | R-206, R-211, R-302 |
| `lib/Calibre/Types/CalibreBook.php`, `CalibreBookFormat.php` | Validate database-derived paths and convert missing/unsafe nodes into controlled not-found responses. | R-207, R-208, R-302 |
| `lib/Opds/OpdsResponse.php` | Emit escaped XML text instead of CDATA, including safe handling of `]]>`. | R-209 |
| `tests/unit/*` | Add regression coverage for native attributes, auth rejection, traversal, XML escaping, bounded regex search, missing content, and downloads. | R-402, R-403 |
| `tests/integration/*` | Add disposable exact-image acceptance with a named app password and immutable-library comparison. | R-404–R-408 |
| `.github/workflows/ci.yml` | Run the required clean-clone gates on PHP 8.2, 8.3, 8.4, and 8.5, plus exact-image integration and reproducibility checks. | R-105, R-401, R-404 |
| `scripts/build-release.sh` | Build a deterministic one-root production archive and publish provenance hashes. | R-502, R-503 |
| Documentation | Record release, compatibility, acceptance, and immutable OPi deployment/rollback handoff. | R-106, R-307, R-503–R-508 |

No unrelated refactor is included.

## Executed target evidence

The disposable integration suite passed on the Orange Pi 5 Plus itself using
ARM64, PHP 8.5.8 from the exact target Nextcloud 34.0.2 image, and the same
pinned PostgreSQL 17 image as production. It exercised installation, enable,
a named app password, rejected authentication, navigation, search, covers,
EPUB/PDF acquisition, disable, and re-enable. The fixture library manifest was
unchanged and the run produced no app-attributable warning, error, deprecation,
or uncaught exception. No production Nextcloud data or credential was mounted.
## Retained deprecated APIs

`SettingsService` retains `OCP\IConfig::getUserValue()` and
`OCP\IConfig::setUserValue()`, with the upstream Psalm suppressions. NC34
deprecates these methods in favor of `OCP\IUserConfig`. The fork keeps them
because Nextcloud 29 remains the declared minimum and replacing them would
either raise that minimum or require a version-dependent adapter unrelated to
the NC34 compatibility patch. Their use is limited to one string setting per
authenticated user.

No new private `OC` implementation API is used. References to the app's own
`OCA\Calibre2OPDS` namespace are not Nextcloud-private API dependencies.

## Security and dependency review

The app has no runtime Composer package other than the PHP platform
requirement. Development packages are excluded from the release archive.
`composer audit --locked` is a mandatory CI and release gate and audits the development toolchain as well as the app's empty runtime dependency set. Any
future accepted advisory must be added here with the advisory ID, affected
package, reachability, containment, expiry, and owner approval.

## Private-distribution warning

The app is not App Store signed. `occ integrity:check-app calibre_opds` can
therefore report missing signature data for a privately distributed build.
That expected warning does not waive code-checker findings, unexpected file
changes, package checksum mismatches, or application log errors.
