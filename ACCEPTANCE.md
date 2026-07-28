# Calibre2OPDS Nextcloud 34 acceptance record

Recorded: 2026-07-28

This ledger records the state of every requirement in
`CALIBRE2OPDS-NC34-REQUIREMENTS.md`. `PASS` means the stated evidence was
actually inspected or executed. `FAIL` means a required external or release
gate has not yet run; it is not waived.

## Local evidence

- Upstream `master` was rechecked at
  `bb2395c3d7f685a82878e78eb6feef1022d110c7`.
- `composer validate --strict --no-check-publish`: PASS.
- `composer lint`: PASS.
- `composer cs`: PASS.
- PHPUnit with Xdebug: PASS, 89 tests and 869 assertions.
- `composer audit --locked`: PASS; no advisories in the complete dependency lock.
- Disposable Calibre fixture generation and EPUB/PDF validation: PASS.
- `bash -n` for integration and release scripts: PASS.
- Exact target PHP 8.5.8 on ARM64: Composer validation, syntax, coding
  standard, Psalm, and PHPUnit PASS; 89 tests and 869 assertions, with no
  deprecation or test-runner warning.
- `composer audit --locked`: PASS; no advisories in the complete dependency
  lock.
- Disposable Orange Pi ARM64 acceptance against the exact Nextcloud 34.0.2
  image and the production-pinned PostgreSQL 17 image: PASS.
- Clean GitHub Actions run
  `https://github.com/Decidetto/calibre_opds/actions/runs/30401300808`: PASS
  on PHP 8.2, 8.3, 8.4, and 8.5, with validation, lint, coding standard,
  Psalm, 89 PHPUnit tests, dependency audit, reproducible release build, and
  the exact Nextcloud 34.0.2 live acceptance all green.
- Tag run `https://github.com/Decidetto/calibre_opds/actions/runs/30401705825`:
  PASS; all six jobs are green and the tag-built archive was published at
  `https://github.com/Decidetto/calibre_opds/releases/tag/v0.0.7-nc34.1`.
- Production OPi phases 71, 85, and 93: PASS. Release provenance, archive and
  installed-file checksums, enablement, `Books/Calibre`, unauthenticated
  rejection, rollback dump/snapshots, private-app integrity, preserved PHP JIT
  override, full Nextcloud health, and zero app-attributable warning/error log
  records were verified. The production library was absent and unchanged.

## Project boundary

| ID | Result | Evidence |
|---|---|---|
| R-001 | PASS | Forked from the required GitLab `master` repository. |
| R-002 | PASS | Baseline and 2026-07-28 upstream recheck are recorded in `docs/COMPATIBILITY-NC34.md`; no newer commit existed. |
| R-003 | PASS | AGPL-3.0-or-later licence, headers, and source files are retained. |
| R-004 | PASS | Exact digest acceptance passed on Orange Pi ARM64 with PHP 8.5.8. |
| R-005 | PASS | Repository review found no production credential, database, library, or host configuration. Runtime test secrets are generated and not persisted. |
| R-006 | PASS | The fork remains a single Nextcloud app and adds no runtime service, datastore, cache, proxy, or origin. |

## Compatibility

| ID | Result | Evidence |
|---|---|---|
| R-101 | PASS | `appinfo/info.xml` declares Nextcloud 29 through 34. |
| R-102 | PASS | Fresh exact-image acceptance enabled, disabled, and re-enabled app version 0.0.7 successfully. |
| R-103 | PASS | Production code uses public `OCP` APIs; no new private `OC` or foreign `OCA` implementation reference was added. |
| R-104 | PASS | Changed controllers use native PHP security attributes; legacy security annotations are absent. |
| R-105 | PASS | The declared PHP 8.2-8.5 range matches the clean four-version CI matrix, and the PHP 8.5.8 target image also passes. |
| R-106 | PASS | Retained deprecated `IConfig` calls and their compatibility rationale are documented. |

## Required behaviour

| ID | Result | Evidence |
|---|---|---|
| R-201 | PASS | Per-user Files-root resolution and the `Books/Calibre` default are implemented and unit tested. |
| R-202 | PASS | SQLite read-only/query-only behaviour and an unchanged library manifest are unit tested. |
| R-203 | PASS | The exact-image ARM64 run authenticated OPDS with a named app password and rejected missing and invalid credentials. |
| R-204 | PASS | Rejected authentication is tested before library access and the harness checks response-body non-disclosure. |
| R-205 | PASS | Existing navigation for authors, publishers, languages, series, tags, and books remains covered by the upstream tests. |
| R-206 | PASS | Search, malformed-pattern fallback, entries, and acquisition links are unit tested. |
| R-207 | PASS | Confined EPUB/PDF acquisition, media types, contents, and unsafe format/path rejection are tested. |
| R-208 | PASS | Existing and missing cover behaviour returns controlled responses in tests. |
| R-209 | PASS | Spaces, Unicode, quotes, ampersands, non-Latin text, and `]]>` are emitted as valid XML in tests and fixtures. |
| R-210 | PASS | Missing, malformed, and unsupported libraries produce bounded responses and sanitized logs in tests. |
| R-211 | PASS | The full upstream unit suite remains passing; regex search semantics were retained with bounded evaluation. |

## Security and privacy

| ID | Result | Evidence |
|---|---|---|
| R-301 | PASS | Library resolution begins at the authenticated user's public Files API root; cross-user/absolute paths are rejected. |
| R-302 | PASS | Central path validation, route constraints, database-path validation, and traversal regression tests fail closed. |
| R-303 | PASS | OPDS-only exemptions and matching explicit authentication-before-access checks are verified by reflection and controller tests. |
| R-304 | PASS | Log calls are sanitized and tests assert that paths and exception details are absent. |
| R-305 | PASS | Production code contains no outbound request, telemetry, analytics, or remote provider. |
| R-306 | PASS | `composer audit --locked` reports no advisory in the complete dependency lock; no advisory is accepted. |
| R-307 | PASS | Exact-image private-app integrity handling passed with only the documented unsigned-app result; lint, coding standard, Psalm, and PHPUnit pass. |

## Quality gates

| ID | Result | Evidence |
|---|---|---|
| R-401 | PASS | Clean GitHub Actions jobs pass validation, lint, coding standard, Psalm, PHPUnit, and dependency audit on PHP 8.2, 8.3, 8.4, and 8.5. |
| R-402 | PASS | All upstream tests pass and focused regression tests cover every production change. |
| R-403 | PASS | Unit and integration suites collectively cover successful/rejected auth, confinement, XML, covers, missing library, search, and downloads. |
| R-404 | PASS | The live disposable run used the exact Nextcloud digest, pinned PostgreSQL, EPUB/PDF, cover, series, Unicode, and missing-field fixtures on ARM64. |
| R-405 | PASS | Install lifecycle, settings, root feed, browse, search, cover, downloads, authentication rejection, disable, and re-enable all passed. |
| R-406 | PASS | The live before/after manifest proved identical path, type, size, mtime, and SHA state. |
| R-407 | PASS | The exercised routes produced no app-attributable warning, error, deprecation, or uncaught exception. |
| R-408 | PASS | Final isolated acceptance passed on the Orange Pi ARM64 host without production data. |

## Release and deployment

| ID | Result | Evidence |
|---|---|---|
| R-501 | PASS | Annotated tag `v0.0.7-nc34.1` identifies app version 0.0.7 at source commit `0f7e58f4442014fd871383772c26e665d686b89c`. |
| R-502 | PASS | The tag run reproduced the production-only one-root archive byte for byte and published it with SHA-256 `813404f92b7b91a426258cedffb354c2ffbe80a4088c2e2dd6173a9f675757f5`. |
| R-503 | PASS | The GitHub release publishes the archive, checksum, source commit, upstream baseline, lock hash, tag CI evidence, and compatibility report. |
| R-504 | PASS | `docs/COMPATIBILITY-NC34.md` maps production changes to requirements and records the no-refactor boundary. |
| R-505 | PASS | The OPi config pins the release tag, full source/upstream commits, tag-run URL, archive SHA-256, and lock SHA-256; phase 71 consumed only the matching local artifact. |
| R-506 | PASS | Production phase 71 installed into `custom_apps`, enabled the app, set `Books/Calibre`, provisioned no reader credential, and phase 93 verified the existing JIT override unchanged. |
| R-507 | PASS | Production phase 85 verified version, provenance, package manifest, enabled state, library setting, and rejected unauthenticated access; the disposable OPi ARM64 run verified named-app-password authentication and EPUB/PDF acquisition. |
| R-508 | PASS | Production phase 71 created and validated the PostgreSQL dump and read-only appdata/data snapshots before enablement, retained rollback provenance, and proved the library fingerprint unchanged. |

## Release decision

**PASS - released and deployed.** Every mandatory requirement has concrete
evidence and no waiver. The real `Books/Calibre` publication and first
production-client setup are operational follow-up work, not release blockers:
a disposable OPi ARM64 run already proved authenticated acquisition while the
production deployment proved its package, authentication boundary, rollback,
and unchanged-library properties.
