# Orange Pi deployment handoff

This is a handoff to the server-provisioning project. It contains no host,
account, token, app password, or production library data.

## Immutable inputs

Provisioning must receive these values from an approved release manifest:

- `calibre_opds_source_commit`
- `calibre_opds_upstream_commit`
- `calibre_opds_release_tag` matching `v0.0.7-nc34.N`
- `calibre_opds_test_run_url` identifying the immutable CI evidence
- `calibre_opds_archive_url` pointing to the immutable tagged release object
- `calibre_opds_archive_path` pointing to its verified local cache
- `calibre_opds_archive_sha256`
- `calibre_opds_lock_sha256`

The OPi deployer downloads the tagged release object only when its ignored
local `artifacts/` cache is absent, verifies SHA-256 before caching and again
before extraction, and transfers it only when the application phase is
selected. An existing cache with the wrong hash must fail closed. The archive
must contain exactly one top-level `calibre_opds` directory and install
atomically into Nextcloud's configured custom-app directory. Provisioning must
never fetch `master`, another branch, or a mutable `latest` asset.

## R-5xx mapping

| Requirement | Provisioning/verification step |
|---|---|
| R-501 | Assert the manifest tag matches `v0.0.7-nc34.N` and `occ app:getpath calibre_opds` reports the expected app. |
| R-502 | Consume only the archive produced by `scripts/build-release.sh`; reject extra top-level entries, VCS data, CI files, and `tests/`. |
| R-503 | Store the source commit, upstream baseline, lock hash, archive hash, and test-run URL with the deployment record. |
| R-504 | Require an approved `docs/COMPATIBILITY-NC34.md` from the same source commit. |
| R-505 | Pin the immutable source identity and SHA-256 in server configuration; do not follow branches or mutable assets. |
| R-506 | Install below the existing `apps_paths` entry with `writable: true`, run `occ app:enable calibre_opds`, provision no reader credential, and make no PHP/JIT configuration change. |
| R-507 | Run the verification sequence below with a separately created named app password supplied only at runtime. |
| R-508 | Complete the database dump and Btrfs snapshot gate before first enablement; use the rollback sequence below on failure. |

## Pre-enable gate

1. Confirm the exact-image disposable acceptance and clean CI evidence belong
   to the pinned source commit before touching production.
2. Record `occ status`, `occ app:list`, and the current custom-app path.
3. Take a Nextcloud database dump using the server project's existing
   database-native backup task.
4. Take and record a read-only Btrfs snapshot of the Nextcloud application,
   configuration, and data subvolumes covered by the server rollback policy.
5. Hash the configured `Books/Calibre` tree, including file content, size,
   mtime, type, and relative path.
6. Verify the downloaded archive SHA-256, then extract and atomically install.
7. Do not edit, replace, or remove the existing PHP JIT override.

## Verification

Run `occ` as the web-server user and fail on any unexpected output:

```sh
php occ app:enable calibre_opds
php occ app:list --enabled
php occ user:setting READER_UID calibre_opds library
```

Verify that the installed version is `0.0.7`, the enabled list contains
`calibre_opds`, and the setting equals `Books/Calibre`. With a named app
password injected through the deployment runner's secret channel, verify:

- authenticated root feed returns 200 and OPDS Atom media type;
- unauthenticated and deliberately invalid credentials return 401;
- search returns the fixture entry and acquisition links;
- one acquisition downloads with the expected media type and SHA-256;
- the library tree manifest is identical before and after;
- no new app-attributable warning, error, deprecation, or exception exists.

Never write the named app password, Authorization header, or credential-bearing
URL to logs, inventory, facts, command arguments stored by the orchestrator, or
deployment artifacts.

## Rollback

1. Disable the app: `php occ app:disable calibre_opds`.
2. Remove the failed package from the custom-app path or atomically restore the
   previously pinned package if this was an upgrade.
3. Restore the database dump only if enablement or a repair step changed
   database state and rollback validation requires it.
4. Restore the recorded Btrfs snapshot only under the server project's normal
   rollback procedure.
5. Re-hash `Books/Calibre`; any difference is a release blocker and must not be
   normalized away.

The library itself is never a rollback target because the app is read-only.
