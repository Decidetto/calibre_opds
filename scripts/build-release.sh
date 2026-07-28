#!/usr/bin/env bash
# SPDX-FileCopyrightText: 2026 Calibre2OPDS contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

set -euo pipefail

repo_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${repo_dir}"

for command in composer git tar gzip sha256sum; do
	command -v "${command}" >/dev/null || {
		echo "Required command not found: ${command}" >&2
		exit 2
	}
done

if [[ -n "$(git status --porcelain --untracked-files=normal)" ]]; then
	echo "Release builds require a clean, committed worktree." >&2
	exit 1
fi

version="$(sed -n 's:.*<version>\([^<]*\)</version>.*:\1:p' appinfo/info.xml)"
[[ "${version}" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || {
	echo "Invalid app version: ${version}" >&2
	exit 1
}

source_commit="$(git rev-parse HEAD)"
source_epoch="${SOURCE_DATE_EPOCH:-$(git show -s --format=%ct HEAD)}"
current_tag="$(git tag --points-at HEAD | head -n 1)"
if [[ -n "${current_tag}" && ! "${current_tag}" =~ ^v${version}-nc34\.[0-9]+$ ]]; then
	echo "Release tag must match v${version}-nc34.N; found ${current_tag}" >&2
	exit 1
fi

work_dir="$(mktemp -d)"
cleanup() {
	rm -rf -- "${work_dir}"
}
trap cleanup EXIT

stage_root="${work_dir}/stage"
app_root="${stage_root}/calibre_opds"
mkdir -p "${app_root}"
git archive HEAD | tar -xf - -C "${app_root}"

composer install \
	--working-dir="${app_root}" \
	--no-dev \
	--no-interaction \
	--no-progress \
	--prefer-dist \
	--classmap-authoritative \
	--no-scripts

rm -rf -- \
	"${app_root}/.github" \
	"${app_root}/.gitlab-ci.yml" \
	"${app_root}/.php-cs-fixer.dist.php" \
	"${app_root}/.l10nignore" \
	"${app_root}/CALIBRE2OPDS-NC34-REQUIREMENTS.md" \
	"${app_root}/ACCEPTANCE.md" \
	"${app_root}/Makefile" \
	"${app_root}/composer.json" \
	"${app_root}/composer.lock" \
	"${app_root}/phpunit.xml" \
	"${app_root}/psalm.xml" \
	"${app_root}/scripts" \
	"${app_root}/tests"

find "${stage_root}" -exec touch -h -d "@${source_epoch}" {} +

dist_dir="${repo_dir}/dist"
mkdir -p "${dist_dir}"
archive="${dist_dir}/calibre_opds-${version}-nc34.tar.gz"
manifest="${dist_dir}/RELEASE-MANIFEST.md"
tar \
	--sort=name \
	--mtime="@${source_epoch}" \
	--owner=0 \
	--group=0 \
	--numeric-owner \
	--format=posix \
	--pax-option=delete=atime,delete=ctime \
	-C "${stage_root}" \
	-cf - calibre_opds \
	| gzip -n >"${archive}"

archive_hash="$(sha256sum "${archive}" | awk '{print $1}')"
lock_hash="$(sha256sum composer.lock | awk '{print $1}')"
printf '%s  %s\n' "${archive_hash}" "$(basename "${archive}")" >"${archive}.sha256"

cat >"${manifest}" <<EOF
# Calibre2OPDS NC34 release manifest

- App version: \`${version}\`
- Release tag: \`${current_tag:-not tagged}\`
- Source commit: \`${source_commit}\`
- Upstream baseline: \`bb2395c3d7f685a82878e78eb6feef1022d110c7\`
- Composer lock SHA-256: \`${lock_hash}\`
- Archive: \`$(basename "${archive}")\`
- Archive SHA-256: \`${archive_hash}\`
- Source date epoch: \`${source_epoch}\`
- Test evidence: CI workflow for source commit \`${source_commit}\`
- Compatibility report: \`docs/COMPATIBILITY-NC34.md\`
EOF

echo "${archive}"
echo "SHA-256: ${archive_hash}"
