#!/usr/bin/env bash
# SPDX-FileCopyrightText: 2026 Calibre2OPDS contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

set -euo pipefail

script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
repo_dir="$(cd -- "${script_dir}/../.." && pwd)"
compose_file="${script_dir}/compose.yaml"
port="${NC_PORT:-18080}"
base_url="http://127.0.0.1:${port}"
library="/var/www/html/data/integration-reader/files/Books/Calibre"
work_dir="$(mktemp -d)"

cleanup() {
	docker compose -f "${compose_file}" down --volumes --remove-orphans >/dev/null 2>&1 || true
	rm -rf -- "${work_dir}"
}
trap cleanup EXIT

for command in docker curl openssl; do
	command -v "${command}" >/dev/null || {
		echo "Required command not found: ${command}" >&2
		exit 2
	}
done
test -f "${repo_dir}/vendor/autoload.php" || {
	echo "Run composer install --no-dev before the integration test." >&2
	exit 2
}
test ! -d "${repo_dir}/vendor/nextcloud/ocp" || {
	echo "Development OCP stubs would shadow the target image; run composer install --no-dev." >&2
	exit 2
}

export NC_PORT="${port}"
export NC_ADMIN_PASSWORD
NC_ADMIN_PASSWORD="$(openssl rand -hex 24)"
export NC_DB_PASSWORD
NC_DB_PASSWORD="$(openssl rand -hex 24)"
user_password="$(openssl rand -hex 24)"

docker compose -f "${compose_file}" up --detach

for _ in $(seq 1 90); do
	if docker compose -f "${compose_file}" exec -T --user www-data nextcloud php occ status --output=json \
		>"${work_dir}/status.json" 2>/dev/null \
		&& grep -q '"installed":true' "${work_dir}/status.json"; then
		break
	fi
	sleep 2
done
grep -q '"installed":true' "${work_dir}/status.json" || {
	echo "Nextcloud did not become ready" >&2
	docker compose -f "${compose_file}" logs >&2
	exit 1
}

architecture="$(docker image inspect \
	nextcloud:34.0.2-apache@sha256:e93ccfc952c95f18175f3d297fb2f60c35070c05ca976050c250a9ddab793e75 \
	--format '{{.Architecture}}')"
echo "Nextcloud acceptance architecture: ${architecture}"
if [[ "${REQUIRE_ARM64:-0}" == "1" && "${architecture}" != "arm64" ]]; then
	echo "ARM64 acceptance was required but Docker reported ${architecture}" >&2
	exit 1
fi

occ() {
	docker compose -f "${compose_file}" exec -T --user www-data nextcloud php occ "$@"
}

occ app:enable calibre_opds
occ app:list --enabled | grep -q 'calibre_opds: 0.0.7'

set +e
integrity_output="$(occ integrity:check-app calibre_opds 2>&1)"
integrity_status=$?
set -e
if [[ "${integrity_status}" -ne 0 ]] \
	&& ! grep -Fq 'Signature data not found' <<<"${integrity_output}"; then
	echo "${integrity_output}" >&2
	echo "Unexpected app integrity failure" >&2
	exit 1
fi
if grep -Eq 'INVALID_HASH|EXTRA_FILE|MISSING_FILE' <<<"${integrity_output}"; then
	echo "${integrity_output}" >&2
	echo "App integrity reported an unexpected package difference" >&2
	exit 1
fi


docker compose -f "${compose_file}" exec -T --user www-data -e OC_PASS="${user_password}" \
	nextcloud php occ user:add --password-from-env --display-name='OPDS Integration Reader' integration-reader
occ files:scan --path='integration-reader/files'
docker compose -f "${compose_file}" exec -T --user www-data nextcloud \
	php /var/www/html/custom_apps/calibre_opds/tests/integration/create-library.php "${library}"
occ files:scan --path='integration-reader/files/Books/Calibre'
occ user:setting integration-reader calibre_opds library 'Books/Calibre'
[[ "$(occ user:setting integration-reader calibre_opds library)" == 'Books/Calibre' ]]

token_output="$(docker compose -f "${compose_file}" exec -T --user www-data -e NC_PASS="${user_password}" \
	nextcloud php occ user:auth-tokens:add --name='Calibre OPDS integration' \
	--password-from-env integration-reader)"
app_password="$(printf '%s\n' "${token_output}" | tr -d '\r' | sed -n '/^app password:$/ { n; p; }')"
[[ -n "${app_password}" ]] || {
	echo "Named app password was not returned by occ" >&2
	exit 1
}

docker compose -f "${compose_file}" exec -T --user www-data nextcloud \
	php /var/www/html/custom_apps/calibre_opds/tests/integration/library-manifest.php "${library}" \
	>"${work_dir}/before.json"
log_lines_before="$(docker compose -f "${compose_file}" exec -T nextcloud \
	sh -c 'test -f /var/www/html/data/nextcloud.log && wc -l < /var/www/html/data/nextcloud.log || echo 0' | tr -d '\r')"

catalogue_url="${base_url}/index.php/apps/calibre_opds/"

assert_rejected() {
	local label="$1"
	local credentials="$2"
	local output="${work_dir}/${label}.body"
	local status
	if [[ -n "${credentials}" ]]; then
		status="$(curl --silent --show-error --output "${output}" --write-out '%{http_code}' \
			--user "${credentials}" "${catalogue_url}")"
	else
		status="$(curl --silent --show-error --output "${output}" --write-out '%{http_code}' \
			"${catalogue_url}")"
	fi
	[[ "${status}" == "401" ]] || {
		echo "${label}: expected 401, received ${status}" >&2
		exit 1
	}
	if grep -Eq 'Unicode|Lovelace|Publisher|Series|metadata' "${output}"; then
		echo "${label}: authentication response leaked catalogue data" >&2
		exit 1
	fi
}

assert_rejected unauthenticated ''
assert_rejected invalid "integration-reader:not-the-password"

auth=(--user "integration-reader:${app_password}" --fail --silent --show-error)
curl "${auth[@]}" "${catalogue_url}" --output "${work_dir}/root.xml"
grep -q 'Calibre OPDS Library' "${work_dir}/root.xml"

for route in author-prefixes/1 publishers languages series tags books; do
	curl "${auth[@]}" "${catalogue_url}${route}" --output "${work_dir}/${route//\//-}.xml"
done
curl "${auth[@]}" "${catalogue_url}books/search/Unicode" --output "${work_dir}/search.xml"

grep -q '/data/1/EPUB' "${work_dir}/search.xml"
grep -q '/data/1/PDF' "${work_dir}/search.xml"

docker compose -f "${compose_file}" exec -T nextcloud php -r '
	$xml = simplexml_load_string(stream_get_contents(STDIN));
	if ($xml === false) {
		exit(1);
	}
	$xml->registerXPathNamespace("atom", "http://www.w3.org/2005/Atom");
	foreach ($xml->xpath("//atom:entry/atom:title") ?: [] as $title) {
		if ((string)$title === "Unicode & \"Quotes\" — 日本語") {
			exit(0);
		}
	}
	fwrite(STDERR, "Expected Unicode fixture title was not found in the search feed\n");
	exit(1);
' <"${work_dir}/search.xml"

curl "${auth[@]}" --dump-header "${work_dir}/epub.headers" \
	"${catalogue_url}data/1/EPUB" --output "${work_dir}/book.epub"
curl "${auth[@]}" --dump-header "${work_dir}/pdf.headers" \
	"${catalogue_url}data/1/PDF" --output "${work_dir}/book.pdf"
curl "${auth[@]}" --dump-header "${work_dir}/cover.headers" \
	"${catalogue_url}cover/1" --output "${work_dir}/cover.jpg"
grep -qi '^content-type: application/epub+zip' "${work_dir}/epub.headers"
grep -qi '^content-type: application/pdf' "${work_dir}/pdf.headers"
grep -qi '^content-type: image/jpeg' "${work_dir}/cover.headers"

expected_epub="$(docker compose -f "${compose_file}" exec -T nextcloud sha256sum \
	"${library}/Ada Lovelace/Unicode & \"Quotes\" — 日本語 (1)/Unicode & \"Quotes\" — 日本語.epub" \
	| awk '{print $1}' | tr -d '\r')"
expected_pdf="$(docker compose -f "${compose_file}" exec -T nextcloud sha256sum \
	"${library}/Ada Lovelace/Unicode & \"Quotes\" — 日本語 (1)/Unicode & \"Quotes\" — 日本語.pdf" \
	| awk '{print $1}' | tr -d '\r')"
[[ "$(sha256sum "${work_dir}/book.epub" | awk '{print $1}')" == "${expected_epub}" ]]
[[ "$(sha256sum "${work_dir}/book.pdf" | awk '{print $1}')" == "${expected_pdf}" ]]

missing_cover_status="$(curl --silent --show-error --output "${work_dir}/missing-cover.body" \
	--write-out '%{http_code}' --user "integration-reader:${app_password}" "${catalogue_url}cover/2")"
[[ "${missing_cover_status}" == '404' ]]

occ app:disable calibre_opds
occ app:enable calibre_opds
curl "${auth[@]}" "${catalogue_url}" --output "${work_dir}/root-after-reenable.xml"

docker compose -f "${compose_file}" exec -T --user www-data nextcloud \
	php /var/www/html/custom_apps/calibre_opds/tests/integration/library-manifest.php "${library}" \
	>"${work_dir}/after.json"
diff --unified "${work_dir}/before.json" "${work_dir}/after.json"

docker compose -f "${compose_file}" exec -T nextcloud php -r '
	$path = "/var/www/html/data/nextcloud.log";
	$start = (int)$argv[1];
	$lines = is_file($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];
	foreach (array_slice($lines, $start) as $line) {
		$row = json_decode($line, true);
		if (!is_array($row)) {
			continue;
		}
		$level = (int)($row["level"] ?? 0);
		$app = (string)($row["app"] ?? "");
		$message = (string)($row["message"] ?? "");
		if ($level >= 2 && ($app === "calibre_opds" || str_contains($message, "Calibre OPDS"))) {
			fwrite(STDERR, $line . PHP_EOL);
			exit(1);
		}
	}
' "${log_lines_before}"

if docker compose -f "${compose_file}" logs nextcloud \
	| grep -Ei 'PHP (warning|notice|deprecated|fatal)|uncaught exception' \
	| grep -Ei 'calibre|opds'; then
	echo "PHP emitted an app-attributable diagnostic" >&2
	exit 1
fi

echo "Nextcloud 34.0.2 integration acceptance passed on ${architecture}."
