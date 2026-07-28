# Calibre2OPDS Nextcloud 34 fork requirements

Status: approved project specification, implementation pending

This document defines the fork-and-upgrade project that will live in a
separate local repository. Copy this file into that repository unchanged and
record later requirement changes in both locations.

The words MUST, MUST NOT, SHOULD, SHOULD NOT, and MAY express requirement
strength.

## 1. Project boundary

**R-001** The project MUST fork
`https://gitlab.com/oldnomad/calibre_opds` from upstream `master`.

**R-002** The initial fork MUST record upstream commit
`bb2395c3d7f685a82878e78eb6feef1022d110c7` as its baseline. The implementer
MUST recheck upstream before coding and document any newer commits included or
excluded.

**R-003** The project MUST preserve the upstream AGPL-3.0-or-later licence,
copyright notices, and source availability obligations.

**R-004** The project MUST target the production image
`nextcloud:34.0.2-apache@sha256:e93ccfc952c95f18175f3d297fb2f60c35070c05ca976050c250a9ddab793e75`
on ARM64. An amd64 test instance MAY provide earlier feedback, but it cannot
replace final ARM64 acceptance.

**R-005** The fork repository MUST contain no OPi credentials, Nextcloud app
passwords, database contents, production library files, or private host
configuration.

**R-006** The fork MUST remain a Nextcloud application. It MUST NOT add a
container, daemon, database, cache, reverse proxy, or separate public origin.

## 2. Compatibility

**R-101** `appinfo/info.xml` MUST declare Nextcloud 34 support. The project
SHOULD retain the upstream minimum version of 29 unless a documented API
constraint requires a higher minimum.

**R-102** The app MUST install, enable, disable, and re-enable on a fresh
Nextcloud 34.0.2 instance through supported `occ` commands.

**R-103** The app MUST use public `OCP` APIs. New references to private `OC`
or `OCA` implementation classes are forbidden.

**R-104** Controller security declarations MUST use the PHP attributes
supported by Nextcloud 34. Legacy docblock security annotations MUST be removed
from changed controllers.

**R-105** The app MUST support the PHP version in the target image. Composer's
declared PHP range MUST match the versions tested in CI.

**R-106** The project MUST document each deprecated API retained from upstream.
The Nextcloud 34 release MAY retain a deprecated API when replacement would
expand the patch or break older supported versions.

## 3. Required behaviour

**R-201** Each authenticated Nextcloud user MUST select a Calibre library path
inside that user's Files namespace. The production path is `Books/Calibre`.

**R-202** The app MUST open Calibre's `metadata.db` in read-only mode. It MUST
NOT create SQLite journals, WAL files, lock files, thumbnails, metadata files,
or other content inside the library.

**R-203** The OPDS endpoint MUST require Nextcloud authentication. It MUST
accept a valid named Nextcloud app password through the authentication method
used by common OPDS 1.2 clients.

**R-204** An unauthenticated request and a request with invalid credentials
MUST return an authentication failure without revealing book titles, authors,
paths, covers, counts, or search results.

**R-205** The app MUST provide OPDS 1.2 navigation for authors, publishers,
languages, series, tags, and books where the source library contains those
fields.

**R-206** Search MUST return matching catalogue entries and valid acquisition
links.

**R-207** Acquisition links MUST download the requested format with the correct
media type and file contents. The app MUST prevent access to files outside the
configured library.

**R-208** Cover links MUST return the expected cover or a controlled not-found
response. Missing covers MUST NOT produce an unhandled exception.

**R-209** Library paths and book metadata containing spaces, Unicode, quotes,
ampersands, and non-Latin characters MUST produce valid XML and working links.

**R-210** A missing library, missing `metadata.db`, malformed database, or
unsupported schema MUST produce a bounded user-facing error and a useful
server log entry without exposing filesystem paths or credentials.

**R-211** Existing upstream OPDS behaviour MUST remain intact unless this
specification changes it.

## 4. Security and privacy

**R-301** The app MUST enforce user-file permissions through Nextcloud's Files
API. A user MUST NOT select or read another user's library.

**R-302** Path traversal through settings, route parameters, encoded path
segments, database values, or acquisition links MUST fail closed.

**R-303** Public-page and CSRF exemptions MUST cover only the endpoints that
need OPDS client authentication. Controller code MUST perform the matching
authentication and authorization checks before reading catalogue data.

**R-304** Logs MUST NOT contain passwords, authorization headers, session
tokens, full acquisition URLs with credentials, or book contents.

**R-305** The fork MUST add no outbound network request, telemetry, analytics,
or remote metadata provider.

**R-306** `composer audit` MUST report no unreviewed vulnerability affecting
runtime dependencies at release time. The compatibility report MUST document
any accepted advisory and its containment.

**R-307** The final package MUST pass Nextcloud's applicable app code and
integrity checks. The release notes MUST describe any expected warning caused
by private distribution.

## 5. Quality gates

**R-401** CI MUST run Composer validation, PHP syntax checks, the upstream
coding-standard check, Psalm, PHPUnit, and `composer audit` from a clean clone.

**R-402** The project MUST keep the upstream tests passing. It MUST add tests
for each code change made for Nextcloud 34.

**R-403** The test suite MUST cover successful app-password authentication,
rejected authentication, path confinement, XML escaping, missing covers,
missing library state, search, and format downloads.

**R-404** A live integration test MUST run against the exact Nextcloud 34.0.2
application image. It MUST use a disposable user and a disposable Calibre
library containing at least EPUB and PDF formats, a cover, series metadata,
Unicode metadata, and one missing optional field.

**R-405** The live test MUST prove install, enable, settings save, root-feed
fetch, browse, search, cover fetch, format download, authentication rejection,
disable, and re-enable.

**R-406** The live test MUST compare the source library before and after all
requests. No file, byte, timestamp, or directory entry may change.

**R-407** Nextcloud logs MUST contain no new warning or error attributable to
the app during the acceptance run. PHP must report no notice, warning,
deprecation, or uncaught exception on exercised routes.

**R-408** The final acceptance run MUST execute on the Orange Pi's ARM64
Nextcloud staging instance or an isolated clone of its Compose configuration.
Production data MUST NOT serve as test input.

## 6. Release and deployment handoff

**R-501** The fork MUST use a valid app version higher than upstream 0.0.6 and
a release tag that identifies the NC34 maintenance line.

**R-502** The project MUST produce a reproducible release archive with one
top-level `calibre_opds` directory, production dependencies only, and no VCS,
CI, test-fixture, or editor files.

**R-503** The release MUST publish the archive SHA-256, source commit, upstream
baseline, dependency lock hash, test results, and compatibility report.

**R-504** The compatibility report MUST list every source change from upstream
and explain why the change is required. Unrelated refactors are forbidden.

**R-505** The server project MUST pin the release by immutable source identity
and archive SHA-256. It MUST NOT download an unpinned branch or mutable release
asset during provisioning.

**R-506** OPi provisioning MUST install the app into Nextcloud's supported
custom-app location, enable it with `occ`, avoid provisioning reader
credentials, and preserve the existing PHP JIT override.

**R-507** OPi verification MUST check the installed version, package checksum,
enabled state, configured library path, authenticated OPDS response, rejected
unauthenticated response, and an acquisition download.

**R-508** Deployment MUST take a Nextcloud database dump and Btrfs rollback
snapshot before first enablement. Rollback MUST consist of disabling the app,
restoring the prior pinned package if needed, and leaving the Calibre library
unchanged.

## 7. Non-goals

The NC34 fork MUST NOT add:

- library uploads, imports, conversion, or metadata editing;
- a browser-based reader;
- reading-position, annotation, highlight, bookmark, or note sync;
- Calibre-Web compatibility or Kobo store emulation;
- OPDS 2.0 unless upstream adopts it in the selected baseline;
- catalogue pagination or a schema redesign without a measured production
  need; or
- the Windows-to-Nextcloud publication tool.

## 8. Deliverables

The project is incomplete until it supplies:

1. the maintained fork with upstream remote and documented baseline;
2. the minimal NC34 compatibility patch;
3. automated unit and security-regression tests;
4. the live Nextcloud 34.0.2 integration test and fixtures;
5. a reproducible release archive and SHA-256;
6. release notes and a compatibility report; and
7. an OPi deployment handoff that maps each `R-5xx` requirement to a future
   provisioning or verification step.

## 9. Acceptance record

The implementer MUST create `ACCEPTANCE.md` in the fork and record PASS or FAIL
for every requirement ID. Each PASS needs a command, test name, artifact, or
review reference. A waiver needs the requirement ID, reason, risk, containment,
and owner approval. No requirement may disappear from the matrix.

The project reaches release readiness only when all MUST requirements pass or
carry an approved waiver, the ARM64 test passes, and the OPi deployment handoff
contains no unresolved secret, storage, authentication, or rollback decision.
