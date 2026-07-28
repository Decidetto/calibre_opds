<!--
SPDX-FileCopyrightText: 2023 Alec Kojaev <alec@kojaev.name>
SPDX-License-Identifier:  CC0-1.0
-->
# Calibre2OPDS app

[![CI](https://github.com/Decidetto/calibre_opds/actions/workflows/ci.yml/badge.svg)](https://github.com/Decidetto/calibre_opds/actions/workflows/ci.yml)


The Calibre2OPDS app provides access to user's [Calibre](https://calibre-ebook.com/) library
stored in Nextcloud via [OPDS](https://specs.opds.io/opds-1.2).

[OpenSearch](https://github.com/dewitt/opensearch) is supported for searching in the library.

This maintained fork is [published on GitHub](https://github.com/Decidetto/calibre_opds);
its [upstream project is on GitLab](https://gitlab.com/oldnomad/calibre_opds/).

## Usage

This app is intended to be used in situation where you are storing your whole
Calibre library directory in your Nextcloud instance.

The app exposes Calibre library contents as OPDS feeds. If your Nextcloud is at URL
`https://example.com/index.php`, then the root OPDS feed is available at URL
`https://example.com/index.php/apps/calibre_opds/`. Note that accessing your OPDS feed
requires authentication in Nextcloud.

Correspondence between Calibre metadata and OPDS fields is described in [a separate document](OPDS.md).

### Settings

This app has no administrator settings.

Personal settings for this app are in settings section "Sharing". The only parameter that
can be modified is the path to the Calibre library folder (by default `Books/Calibre`).
The path is relative to the authenticated user's Files root.

OPDS clients should use the user's Nextcloud login name and a dedicated named
app password over HTTPS. The catalogue does not permit anonymous access.

## Installation

This NC34 maintenance fork is privately distributed and is not App Store
signed. Install a pinned release archive into Nextcloud's configured
custom-app directory and verify its published SHA-256 before extraction.

For a development build:

First, clone this repository:

```sh
git clone https://gitlab.com/oldnomad/calibre_opds.git
```

Then run Composer and create a tarball:

```sh
composer install --no-dev
scripts/build-release.sh
```

The reproducible tarball and checksum are created in `dist/`. Copy the tarball
to the supported custom-app directory configured in `apps_paths`.

Enable it with `occ app:enable calibre_opds`. See
`docs/OPI-DEPLOYMENT.md` for the immutable Orange Pi handoff.
