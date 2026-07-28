# Calibre2OPDS 0.0.7 — Nextcloud 34 maintenance line

Release tags use `v0.0.7-nc34.N`.

## Changes

- Declare compatibility with Nextcloud 34 while retaining Nextcloud 29 as the
  minimum.
- Support PHP 8.5 without deprecated PDO SQLite APIs while retaining PHP
  8.2–8.4 compatibility.
- Use native AppFramework controller security attributes.
- Default new users to `Books/Calibre`.
- Enforce user-relative path confinement for settings and database-derived
  cover/acquisition paths.
- Reinforce read-only SQLite access and return bounded responses for missing,
  malformed, and unsupported libraries.
- Bound regular-expression search evaluation, handle malformed patterns safely,
  and harden XML output for Unicode and special characters.
- Add named-app-password live acceptance, immutable-library comparison, and a
  reproducible release builder.

## Upgrade notes

Existing per-user library settings are preserved. Paths must now be canonical
relative paths in the user's Files namespace; absolute paths, backslashes,
empty segments, `.` and `..` are rejected.

This is a privately distributed, unsigned Nextcloud app. The expected
integrity-check warning is “no signature data found.” Any other integrity,
code-checker, log, checksum, or acceptance failure is not expected and blocks
deployment.
