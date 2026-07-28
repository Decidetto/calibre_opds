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
| R-105 | FAIL | PHP 8.2-8.5 metadata and CI matrix agree and the PHP 8.5.8 target passes; the clean 8.2-8.4 CI matrix is still pending. |
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
| R-401 | FAIL | Every gate passes on the PHP 8.5 ARM64 target, but the clean GitHub Actions PHP 8.2-8.5 matrix has not run. |
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
| R-501 | FAIL | App version 0.0.7 and `v0.0.7-nc34.N` enforcement exist, but no release tag has been created. |
| R-502 | FAIL | A deterministic one-root production-only build is implemented and CI checks reproducibility; no committed release archive exists yet. |
| R-503 | FAIL | The build emits all required provenance and hashes, but nothing has been published. |
| R-504 | PASS | `docs/COMPATIBILITY-NC34.md` maps production changes to requirements and records the no-refactor boundary. |
| R-505 | FAIL | Immutable source and checksum handoff is documented; the server project has not consumed it. |
| R-506 | FAIL | Custom-app installation, credential, and PHP-JIT constraints are documented; OPi provisioning has not run. |
| R-507 | FAIL | The complete OPi verification sequence is documented; it has not run. |
| R-508 | FAIL | Database-dump, Btrfs snapshot, and rollback gates are documented; they have not run. |

## Release decision

**FAIL — not releasable yet.** Merge/commit the implementation, run CI, create
an approved maintenance tag, build and publish the immutable archive, then run
the isolated ARM64 staging acceptance and update every remaining `FAIL` with
the resulting evidence. No waiver has been requested or granted.
