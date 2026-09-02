#!/usr/bin/env bash

GRAND_MEDIA_SLUG=grand-media
FREEMIUS_PRODUCT_ID=20980
WORDPRESS_ORG_SVN_REPOSITORY_ROOT=https://plugins.svn.wordpress.org
WORDPRESS_ORG_SVN_WORKING_COPY_URL=$WORDPRESS_ORG_SVN_REPOSITORY_ROOT/$GRAND_MEDIA_SLUG

die() { printf 'ERROR: %s\n' "$*" >&2; return 1; }

require_command() {
	command -v "$1" >/dev/null 2>&1 || die "Required command not found: $1"
}

plugin_header_version() {
	sed -n 's/^ \* Version:[[:space:]]*//p' "$1/grand-media.php" | head -1
}

runtime_version() {
	sed -n "s/^[[:space:]]*public \\\$version[[:space:]]*=[[:space:]]*'\([^']*\)';.*/\1/p" "$1/grand-media.php" | head -1
}

stable_tag() {
	sed -n 's/^Stable tag:[[:space:]]*//p' "$1/readme.txt" | head -1
}

assert_version_consistency() {
	local repo=${RELEASE_REPO:?}
	local version=$1
	local header runtime tag
	header=$(plugin_header_version "$repo")
	runtime=$(runtime_version "$repo")
	tag=$(stable_tag "$repo")

	test -n "$header" || { die 'Plugin header version is missing'; return 1; }
	test -n "$runtime" || { die 'Runtime version is missing'; return 1; }
	test -n "$tag" || { die 'Stable tag is missing'; return 1; }
	test "$header" = "$version" || { die "Plugin header version $header does not match $version"; return 1; }
	test "$runtime" = "$version" || { die "Gmedia::\$version $runtime does not match $version"; return 1; }
	test "$tag" = "$version" || { die "Stable tag $tag does not match $version"; return 1; }
}

assert_clean_master() {
	local repo=${RELEASE_REPO:?}
	local branch head upstream upstream_sha status
	require_command git || return 1
	branch=$(git -C "$repo" branch --show-current) || { die 'Unable to determine current branch'; return 1; }
	test "$branch" = master || { die "Current branch $branch is not master"; return 1; }
	status=$(git -C "$repo" status --porcelain) || { die 'Unable to determine working-tree status'; return 1; }
	test -z "$status" || { die 'Working tree is not clean'; return 1; }
	head=$(git -C "$repo" rev-parse HEAD) || { die 'Unable to resolve HEAD'; return 1; }
	upstream=$(git -C "$repo" rev-parse --abbrev-ref --symbolic-full-name '@{upstream}') || { die 'Master branch has no configured upstream'; return 1; }
	case $upstream in
		*/master) ;;
		*) die "Configured upstream $upstream is not a master branch"; return 1 ;;
	esac
	upstream_sha=$(git -C "$repo" rev-parse '@{upstream}') || { die 'Unable to resolve configured upstream'; return 1; }
	test "$head" = "$upstream_sha" || { die 'HEAD does not match configured upstream'; return 1; }
}

resolve_wp_cli() {
	if test -n "${GRAND_MEDIA_WP_CLI:-}"; then
		require_command "$GRAND_MEDIA_WP_CLI" || return 1
		printf '%s\n' "$GRAND_MEDIA_WP_CLI"
	elif command -v wp-dev >/dev/null 2>&1; then
		printf 'wp-dev\n'
	elif command -v wp >/dev/null 2>&1; then
		printf 'wp\n'
	else
		die 'Unable to resolve WordPress CLI (wp-dev or wp)'
	fi
}

create_work_dir() {
	local work_dir=${1:-}
	local repo=$2
	local parent name canonical_parent canonical_work
	if test -z "$work_dir"; then
		parent=${TMPDIR:-/tmp}
		test -d "$parent" && ! test -L "$parent" || { die 'Temporary directory parent is unsafe'; return 1; }
		canonical_parent=$(cd "$parent" && pwd -P) || return 1
		case $canonical_parent in
			"$repo"|"$repo"/*) die 'Work directory must be outside the source repository'; return 1 ;;
		esac
		work_dir=$(TMPDIR="$canonical_parent" mktemp -d) || return 1
		test -d "$work_dir" && ! test -L "$work_dir" || { die 'mktemp returned an unsafe work directory'; return 1; }
		canonical_work=$(cd "$work_dir" && pwd -P) || return 1
		case $canonical_work in
			"$repo"|"$repo"/*) die 'Work directory must be outside the source repository'; return 1 ;;
			"$canonical_parent"/*) ;;
			*) die 'mktemp returned a work directory outside the approved temporary parent'; return 1 ;;
		esac
		printf '%s\n' "$canonical_work"
		return
	fi
	if test -L "$work_dir"; then
		die 'Work directory must be outside the source repository and must not be a symlink'
		return 1
	fi
	parent=$(dirname "$work_dir") || return 1
	name=$(basename "$work_dir") || return 1
	case $name in ''|.|..) die 'Work directory path is unsafe'; return 1 ;; esac
	test -d "$parent" && ! test -L "$parent" || { die 'Work directory parent must exist and must not be a symlink'; return 1; }
	canonical_parent=$(cd "$parent" && pwd -P) || return 1
	canonical_work="$canonical_parent/$name"
	case $canonical_work in
		"$repo"|"$repo"/*) die 'Work directory must be outside the source repository'; return 1 ;;
	esac
	mkdir "$canonical_work" 2>/dev/null || {
		test -d "$canonical_work" && ! test -L "$canonical_work" || return 1
	}
	(cd "$canonical_work" && pwd -P)
}

manifest_init() (
	local manifest=$1
	local version=$2
	local git_sha=$3
	local temporary="$manifest.tmp.$$"
	trap 'rm -f "$temporary"' EXIT
	trap 'exit 1' HUP INT TERM
	jq -n \
		--arg version "$version" \
		--arg git_sha "$git_sha" \
		--arg timestamp "$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
		'{ version: $version, git_sha: $git_sha, timestamp: $timestamp, stages: {} }' > "$temporary" || return 1
	mv "$temporary" "$manifest" || return 1
	trap - EXIT HUP INT TERM
)

manifest_set() (
	local manifest=$1
	local temporary="$manifest.tmp.$$"
	shift
	trap 'rm -f "$temporary"' EXIT
	trap 'exit 1' HUP INT TERM
	jq "$@" "$manifest" > "$temporary" || return 1
	mv "$temporary" "$manifest" || return 1
	trap - EXIT HUP INT TERM
)

verify_recorded_source_artifact() {
	local manifest=$1
	local source_zip expected_size expected_sha actual_size actual_sha
	source_zip=$(jq -r '.source_zip // empty' "$manifest") || return 1
	expected_size=$(jq -r '.source_zip_bytes // empty' "$manifest") || return 1
	expected_sha=$(jq -r '.source_zip_sha256 // empty' "$manifest") || return 1
	test -n "$source_zip" || { die 'Release manifest source ZIP path is missing'; return 1; }
	test -f "$source_zip" || { die 'Recorded source ZIP does not exist'; return 1; }
	case $expected_size in ''|*[!0-9]*) die 'Release manifest source ZIP size is invalid'; return 1 ;; esac
	case $expected_sha in ''|*[!0-9a-f]*) die 'Release manifest source ZIP SHA-256 is invalid'; return 1 ;; esac
	test "${#expected_sha}" -eq 64 || { die 'Release manifest source ZIP SHA-256 is invalid'; return 1; }
	actual_size=$(wc -c < "$source_zip" | tr -d '[:space:]') || return 1
	actual_sha=$(shasum -a 256 "$source_zip" | awk '{ print $1 }') || return 1
	test "$actual_size" = "$expected_size" || { die 'Recorded source ZIP size does not match manifest'; return 1; }
	test "$actual_sha" = "$expected_sha" || { die 'Recorded source ZIP SHA-256 does not match manifest'; return 1; }
	printf '%s\n' "$source_zip"
}

assert_safe_release_version() {
	local version=$1
	if ! printf '%s\n' "$version" | grep -Eq '^[0-9]+([.][0-9]+){2}([.-][A-Za-z0-9]+)*$'; then
		die 'Release manifest version is unsafe'
		return 1
	fi
}

assert_safe_git_sha() {
	local git_sha=$1
	case $git_sha in
		*[!0-9a-f]*|'') die 'Release manifest Git SHA is unsafe'; return 1 ;;
	esac
	test "${#git_sha}" -eq 40 || { die 'Release manifest Git SHA is unsafe'; return 1; }
}

assert_freemius_request_ready() {
	local token=${FREEMIUS_API_TOKEN:-}
	case $token in *$'\r'*|*$'\n'*) die 'FREEMIUS_API_TOKEN must not contain CR or LF'; return 1 ;; esac
	test -n "$token" || { die 'FREEMIUS_API_TOKEN is required'; return 1; }
	require_command curl
}

freemius_request() {
	local token=${FREEMIUS_API_TOKEN:-}
	assert_freemius_request_ready || return 1
	token=$(printf '%s' "$token" | sed 's/\\/\\\\/g; s/"/\\"/g') || return 1
	{
		printf 'header = "Authorization: Bearer %s"\n' "$token"
		printf 'silent\nshow-error\nfail-with-body\n'
	} | curl --disable --config - "$@"
}

assert_freemius_upload_not_attempted() {
	local manifest=$1
	if jq -e '(.freemius_upload_attempt? != null) or (.freemius? != null) or (.stages.upload_pending? == "passed")' "$manifest" >/dev/null; then
		die 'Freemius upload was already attempted; do not retry automatically'
		return 1
	fi
}

mark_freemius_upload_attempt() {
	local manifest=$1
	local source_sha=$2
	local timestamp
	assert_freemius_upload_not_attempted "$manifest" || return 1
	timestamp=$(date -u +%Y-%m-%dT%H:%M:%SZ) || return 1
	manifest_set "$manifest" \
		--arg source_sha "$source_sha" \
		--arg timestamp "$timestamp" \
		'del(
		  .freemius,
		  .free_zip,
		  .verification,
		  .svn,
		  .free_extracted_root,
		  .transformation_summary,
		  .stages.upload_pending,
		  .stages.download_free,
		  .stages.verify,
		  .stages.svn_prepare,
		  .stages.svn_publish,
		  .stages.freemius_release
		) |
		.freemius_upload_attempt = { source_zip_sha256: $source_sha, timestamp: $timestamp }'
}

pending_upload_may_exist() {
	die "$1; A pending Freemius deployment may exist; do not retry automatically"
}

upload_pending() (
	local requested_zip=$1
	local manifest=$2
	local url response version product_id deployment_id response_version release_mode source_zip source_sha
	test -f "$manifest" || { die 'Release manifest does not exist'; return 1; }
	assert_freemius_upload_not_attempted "$manifest" || return 1
	test -f "$requested_zip" || { die 'Source ZIP does not exist'; return 1; }
	version=$(jq -r '.version // empty' "$manifest") || return 1
	test -n "$version" || { die 'Release manifest version is missing'; return 1; }
	source_zip=$(verify_recorded_source_artifact "$manifest") || return 1
	test "$requested_zip" = "$source_zip" || { die 'Requested source ZIP does not match manifest'; return 1; }
	source_sha=$(jq -r '.source_zip_sha256 // empty' "$manifest") || return 1
	assert_freemius_request_ready || return 1
	url="https://api.freemius.com/v1/products/$FREEMIUS_PRODUCT_ID/tags.json"
	response=$(mktemp "${TMPDIR:-/tmp}/grand-media-freemius.XXXXXX") || return 1
	trap 'rm -f "$response"' EXIT
	trap 'exit 1' HUP INT TERM
	chmod 600 "$response" || return 1
	mark_freemius_upload_attempt "$manifest" "$source_sha" || return 1
	if ! freemius_request --request POST --form "file=@$source_zip" "$url" > "$response"; then
		pending_upload_may_exist 'Freemius upload failed'
		return 1
	fi
	if ! jq -e . "$response" >/dev/null; then
		pending_upload_may_exist 'Freemius upload response is not valid JSON'
		return 1
	fi
	if ! jq -e '(.plugin_id | type == "string") and .plugin_id == "20980"' "$response" >/dev/null; then
		pending_upload_may_exist 'Freemius response product must be string 20980'
		return 1
	fi
	product_id=$(jq -r '.plugin_id // empty' "$response") || { pending_upload_may_exist 'Unable to read Freemius upload product'; return 1; }
	deployment_id=$(jq -r '.id // empty' "$response") || { pending_upload_may_exist 'Unable to read Freemius upload deployment ID'; return 1; }
	response_version=$(jq -r '.version // empty' "$response") || { pending_upload_may_exist 'Unable to read Freemius upload version'; return 1; }
	release_mode=$(jq -r '.release_mode // empty' "$response") || { pending_upload_may_exist 'Unable to read Freemius upload mode'; return 1; }
	rm -f "$response" || { pending_upload_may_exist 'Unable to remove Freemius upload response'; return 1; }
	test "$product_id" = "$FREEMIUS_PRODUCT_ID" || { pending_upload_may_exist 'Freemius response product does not match Grand Media'; return 1; }
	case $deployment_id in ''|*[!0-9]*) pending_upload_may_exist 'Freemius response deployment ID is invalid'; return 1 ;; esac
	test "$response_version" = "$version" || { pending_upload_may_exist 'Freemius response version does not match manifest'; return 1; }
	test "$release_mode" = pending || { pending_upload_may_exist 'Freemius response release mode is not pending'; return 1; }
	if ! manifest_set "$manifest" \
		--argjson product_id "$product_id" \
		--arg deployment_id "$deployment_id" \
		--arg release_mode "$release_mode" \
		--arg upload_release_mode "$release_mode" \
		--arg source_sha "$source_sha" \
		--arg endpoint_class 'POST /v1/products/20980/tags.json' \
		'.freemius = {
		  product_id: $product_id,
		  deployment_id: $deployment_id,
		  release_mode: $release_mode,
		  upload_release_mode: $upload_release_mode,
		  source_zip_sha256: $source_sha,
		  upload_endpoint_class: $endpoint_class
		} | .stages.upload_pending = "passed"'; then
		pending_upload_may_exist 'Unable to record pending Freemius deployment'
		return 1
	fi
	trap - EXIT HUP INT TERM
)

assert_upload_provenance() {
	local manifest=$1
	local source_sha attempt_sha upload_sha product_id deployment_id release_mode upload_release_mode upload_endpoint
	source_sha=$(jq -r '.source_zip_sha256 // empty' "$manifest") || return 1
	attempt_sha=$(jq -r '.freemius_upload_attempt.source_zip_sha256 // empty' "$manifest") || return 1
	upload_sha=$(jq -r '.freemius.source_zip_sha256 // empty' "$manifest") || return 1
	product_id=$(jq -r '.freemius.product_id // empty' "$manifest") || return 1
	deployment_id=$(jq -r '.freemius.deployment_id // empty' "$manifest") || return 1
	release_mode=$(jq -r '.freemius.release_mode // empty' "$manifest") || return 1
	upload_release_mode=$(jq -r '.freemius.upload_release_mode // empty' "$manifest") || return 1
	upload_endpoint=$(jq -r '.freemius.upload_endpoint_class // empty' "$manifest") || return 1
	case $source_sha in ''|*[!0-9a-f]*) die 'Release source provenance SHA-256 is invalid'; return 1 ;; esac
	test "${#source_sha}" -eq 64 || { die 'Release source provenance SHA-256 is invalid'; return 1; }
	test "$attempt_sha" = "$source_sha" && test "$upload_sha" = "$source_sha" || {
		die 'Freemius upload provenance does not match source ZIP'
		return 1
	}
	test "$product_id" = "$FREEMIUS_PRODUCT_ID" || { die 'Freemius upload provenance product does not match Grand Media'; return 1; }
	case $deployment_id in ''|*[!0-9]*) die 'Freemius upload provenance deployment ID is invalid'; return 1 ;; esac
	test "$upload_release_mode" = pending || { die 'Freemius upload provenance upload-time release mode is not pending'; return 1; }
	case $release_mode in
		pending) ;;
		released)
			if ! jq -e '.stages.freemius_release == "passed" and
				(.freemius.released_at | type) == "string" and
				.freemius.release_list_endpoint_class == "GET /v1/products/20980/tags.json?fields=id,plugin_id,version,release_mode&count=50" and
				.freemius.release_update_endpoint_class == "PUT /v1/products/20980/tags/{deployment_id}.json"' "$manifest" >/dev/null; then
				die 'Freemius release provenance is incomplete'
				return 1
			fi
			;;
		*) die 'Freemius upload provenance release mode is invalid'; return 1 ;;
	esac
	test "$upload_endpoint" = 'POST /v1/products/20980/tags.json' || { die 'Freemius upload provenance endpoint does not match Grand Media'; return 1; }
}

download_free() (
	local deployment_id=$1
	local zip=$2
	local manifest=$3
	local recorded_id recorded_product source_sha partial size sha magic
	if jq -e '(.free_zip? != null) or (.stages.download_free? == "passed")' "$manifest" >/dev/null; then
		die 'Freemius free download was already recorded; do not replace its evidence'
		return 1
	fi
	recorded_id=$(jq -r '.freemius.deployment_id // empty' "$manifest") || return 1
	recorded_product=$(jq -r '.freemius.product_id // empty' "$manifest") || return 1
	case $deployment_id in ''|*[!0-9]*) die 'Freemius deployment ID is invalid'; return 1 ;; esac
	test "$recorded_id" = "$deployment_id" || { die 'Freemius deployment ID does not match manifest'; return 1; }
	test "$recorded_product" = "$FREEMIUS_PRODUCT_ID" || { die 'Freemius product does not match Grand Media'; return 1; }
	verify_recorded_source_artifact "$manifest" >/dev/null || return 1
	assert_upload_provenance "$manifest" || return 1
	source_sha=$(jq -r '.source_zip_sha256 // empty' "$manifest") || return 1
	if test -e "$zip" || test -L "$zip"; then
		die 'Free ZIP destination already exists'
		return 1
	fi
	partial="$zip.partial"
	rm -f "$partial" || return 1
	trap 'rm -f "$partial" "$zip"' EXIT
	trap 'exit 1' HUP INT TERM
	if ! freemius_request --request GET --location --output "$partial" "https://api.freemius.com/v1/products/$FREEMIUS_PRODUCT_ID/tags/$deployment_id.zip?is_premium=false"; then
		die 'Freemius free download failed'
		return 1
	fi
	magic=$(dd if="$partial" bs=1 count=2 2>/dev/null)
	if test "$magic" != PK; then
		die 'Downloaded free ZIP does not have ZIP magic'
		return 1
	fi
	size=$(wc -c < "$partial") || return 1
	size=$(printf '%s' "$size" | tr -d '[:space:]') || return 1
	case $size in ''|*[!0-9]*) die 'Downloaded free ZIP size is invalid'; return 1 ;; esac
	test "$size" -gt 0 || { die 'Downloaded free ZIP size is invalid'; return 1; }
	sha=$(shasum -a 256 "$partial") || return 1
	sha=${sha%%[[:space:]]*}
	case $sha in *[!0-9a-f]*|'') die 'Downloaded free ZIP SHA-256 is invalid'; return 1 ;; esac
	test "${#sha}" -eq 64 || { die 'Downloaded free ZIP SHA-256 is invalid'; return 1; }
	mv "$partial" "$zip" || return 1
	manifest_set "$manifest" \
		--arg zip "$zip" \
		--arg sha "$sha" \
		--arg deployment_id "$deployment_id" \
		--arg source_sha "$source_sha" \
		--arg endpoint_class 'GET /v1/products/20980/tags/{deployment_id}.zip?is_premium=false' \
		--argjson size "$size" \
		'.free_zip = {
		  path: $zip,
		  bytes: $size,
		  sha256: $sha,
		  deployment_id: $deployment_id,
		  source_zip_sha256: $source_sha,
		  download_endpoint_class: $endpoint_class
		} | .stages.download_free = "passed"' || return 1
	trap - EXIT HUP INT TERM
)

assert_download_provenance() {
	local manifest=$1
	local source_sha deployment_id free_source_sha free_deployment_id download_endpoint
	assert_upload_provenance "$manifest" || return 1
	source_sha=$(jq -r '.source_zip_sha256 // empty' "$manifest") || return 1
	deployment_id=$(jq -r '.freemius.deployment_id // empty' "$manifest") || return 1
	free_source_sha=$(jq -r '.free_zip.source_zip_sha256 // empty' "$manifest") || return 1
	free_deployment_id=$(jq -r '.free_zip.deployment_id // empty' "$manifest") || return 1
	download_endpoint=$(jq -r '.free_zip.download_endpoint_class // empty' "$manifest") || return 1
	test "$free_source_sha" = "$source_sha" && test "$free_deployment_id" = "$deployment_id" || {
		die 'Freemius download provenance does not match upload evidence'
		return 1
	}
	test "$download_endpoint" = 'GET /v1/products/20980/tags/{deployment_id}.zip?is_premium=false' || {
		die 'Freemius download provenance endpoint does not match Grand Media'
		return 1
	}
}

run_source_gate() {
	local repo=$1 test_file
	(
		cd "$repo" || exit 1
		for test_file in tests/compat/*.php; do
			php "$test_file" || exit 1
		done
		find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l &&
		git diff --check
	)
}

assert_build_git_provenance() {
	local repo=$1
	local manifest=$2
	local git_sha=$3
	local upstream=$4
	local manifest_sha current_head current_upstream current_upstream_sha
	assert_safe_git_sha "$git_sha" || return 1
	manifest_sha=$(jq -r '.git_sha // empty' "$manifest") || return 1
	test "$manifest_sha" = "$git_sha" || { die 'Build manifest Git SHA does not match source export'; return 1; }
	current_head=$(git -C "$repo" rev-parse HEAD) || return 1
	current_upstream=$(git -C "$repo" rev-parse --abbrev-ref --symbolic-full-name '@{upstream}') || return 1
	current_upstream_sha=$(git -C "$repo" rev-parse '@{upstream}') || return 1
	test "$current_head" = "$git_sha" || { die 'Source HEAD changed before tracked export'; return 1; }
	test "$current_upstream" = "$upstream" || { die 'Configured upstream changed before tracked export'; return 1; }
	test "$current_upstream_sha" = "$git_sha" || { die 'Configured upstream changed before tracked export'; return 1; }
	git -C "$repo" cat-file -e "$git_sha^{commit}" || { die 'Manifest Git SHA is not an available commit'; return 1; }
}

create_tracked_source_export() {
	local work_dir=$1
	local export_container export_root canonical_work canonical_container canonical_root actual_parent basename marker
	test -d "$work_dir" && ! test -L "$work_dir" || { die 'Build work directory is unsafe'; return 1; }
	canonical_work=$(cd "$work_dir" && pwd -P) || return 1
	export_container=$(mktemp -d "$canonical_work/grand-media-source-export.XXXXXX") || return 1
	test -d "$export_container" && ! test -L "$export_container" || { die 'Tracked source export container is unsafe'; return 1; }
	canonical_container=$(cd "$export_container" && pwd -P) || { die 'Unable to canonicalize tracked source export container'; return 1; }
	test "$canonical_container" = "$export_container" || { die 'Tracked source export container is not canonical'; return 1; }
	actual_parent=$(cd "$(dirname "$canonical_container")" && pwd -P) || return 1
	basename=$(basename "$canonical_container") || return 1
	case $basename in grand-media-source-export.*) ;; *) die 'Tracked source export path is unsafe'; return 1 ;; esac
	test "$actual_parent" = "$canonical_work" || { die 'Tracked source export escaped the work directory'; return 1; }
	marker="$canonical_container/.grand-media-source-export"
	if ! ( set -C; printf 'path=%s\nparent=%s\n' "$canonical_container" "$canonical_work" > "$marker" ) 2>/dev/null; then
		die 'Unable to exclusively create tracked source export marker'
		return 1
	fi
	test -f "$marker" && ! test -L "$marker" || { die 'Tracked source export marker is unsafe'; return 1; }
	printf 'path=%s\nparent=%s\n' "$canonical_container" "$canonical_work" | cmp -s - "$marker" || {
		die 'Tracked source export marker does not match its container'
		return 1
	}
	export_root="$canonical_container/$GRAND_MEDIA_SLUG"
	if ! mkdir "$export_root"; then
		cleanup_tracked_source_export "$canonical_work" "$canonical_container" || true
		return 1
	fi
	test -d "$export_root" && ! test -L "$export_root" || { cleanup_tracked_source_export "$canonical_work" "$canonical_container" || true; return 1; }
	canonical_root=$(cd "$export_root" && pwd -P) || { cleanup_tracked_source_export "$canonical_work" "$canonical_container" || true; return 1; }
	test "$canonical_root" = "$export_root" || { cleanup_tracked_source_export "$canonical_work" "$canonical_container" || true; return 1; }
	test "$(cd "$(dirname "$canonical_root")" && pwd -P)" = "$canonical_container" || { cleanup_tracked_source_export "$canonical_work" "$canonical_container" || true; return 1; }
	printf '%s\n' "$canonical_container"
}

cleanup_tracked_source_export() {
	local work_dir=$1
	local export_container=$2
	local canonical_work canonical_container actual_parent basename marker
	test -d "$work_dir" && ! test -L "$work_dir" || return 1
	test -d "$export_container" && ! test -L "$export_container" || return 1
	canonical_work=$(cd "$work_dir" && pwd -P) || return 1
	canonical_container=$(cd "$export_container" && pwd -P) || return 1
	test "$canonical_container" = "$export_container" || return 1
	actual_parent=$(cd "$(dirname "$canonical_container")" && pwd -P) || return 1
	basename=$(basename "$canonical_container") || return 1
	case $basename in grand-media-source-export.*) ;; *) return 1 ;; esac
	test "$actual_parent" = "$canonical_work" || return 1
	marker="$canonical_container/.grand-media-source-export"
	test -f "$marker" && ! test -L "$marker" || return 1
	printf 'path=%s\nparent=%s\n' "$canonical_container" "$canonical_work" | cmp -s - "$marker" || return 1
	rm -rf "$canonical_container"
}

build_source_zip() (
	local repo=$1
	local version=$2
	local work_dir=$3
	local manifest=$4
	local git_sha=$5
	local upstream=$6
	local wp_cli archive entries top_levels ignored export_container export_root
	wp_cli=$(resolve_wp_cli) || return 1
	test -f "$repo/.distignore" || { die 'Source .distignore does not exist'; return 1; }
	assert_build_git_provenance "$repo" "$manifest" "$git_sha" "$upstream" || return 1
	export_container=$(create_tracked_source_export "$work_dir") || return 1
	export_root="$export_container/$GRAND_MEDIA_SLUG"
	trap 'cleanup_tracked_source_export "$work_dir" "$export_container"' EXIT
	trap 'exit 1' HUP INT TERM
	git -C "$repo" archive --format=tar "$git_sha" | tar -xf - -C "$export_root" || return 1
	test -f "$export_root/.distignore" || { die 'Tracked source export does not contain .distignore'; return 1; }
	assert_build_git_provenance "$repo" "$manifest" "$git_sha" "$upstream" || return 1
	archive="$work_dir/$GRAND_MEDIA_SLUG-$version-source.zip"
	"$wp_cli" dist-archive "$export_root" "$archive" --create-target-dir >&2 || return 1
	entries=$(unzip -Z1 "$archive") || return 1
	top_levels=$(printf '%s\n' "$entries" | awk -F/ 'NF { print $1 }' | sort -u)
	test "$top_levels" = "$GRAND_MEDIA_SLUG" || {
		die 'Source archive must contain exactly one grand-media/ top-level directory'
		return 1
	}
	printf '%s\n' "$entries" | grep -x "$GRAND_MEDIA_SLUG/" >/dev/null || {
		die 'Source archive is missing grand-media/ top-level directory'
		return 1
	}
	while IFS= read -r ignored; do
		case $ignored in ''|'#'*) continue ;; esac
		if printf '%s\n' "$entries" | grep -F -x "$GRAND_MEDIA_SLUG/$ignored" >/dev/null || \
			printf '%s\n' "$entries" | grep -F -q "$GRAND_MEDIA_SLUG/$ignored/"; then
			die 'Source archive contains excluded development files'
			return 1
		fi
	done < "$export_root/.distignore"
	assert_build_git_provenance "$repo" "$manifest" "$git_sha" "$upstream" || return 1
	cleanup_tracked_source_export "$work_dir" "$export_container" || return 1
	trap - EXIT HUP INT TERM
	printf '%s\n' "$archive"
)

build_command() {
	local repo=$1
	local version=$2
	local requested_work_dir=$3
	local resume_manifest=$4
	local work_dir manifest expected_manifest git_sha archive size sha upstream
	repo=$(cd "$repo" && pwd -P) || return 1
	preflight_command "$repo" "$version" || return 1
	git_sha=$(git -C "$repo" rev-parse HEAD) || return 1
	upstream=$(git -C "$repo" rev-parse --abbrev-ref --symbolic-full-name '@{upstream}') || return 1
	if test -n "$resume_manifest" && test -z "$requested_work_dir"; then
		die '--resume requires matching --work-dir'
		return 1
	fi
	work_dir=$(create_work_dir "$requested_work_dir" "$repo") || return 1
	manifest="$work_dir/release-manifest.json"
	expected_manifest=$(cd "$work_dir" && pwd -P)/release-manifest.json || return 1

	if test -n "$resume_manifest"; then
		test -f "$resume_manifest" || { die 'Resume manifest does not exist'; return 1; }
		test ! -L "$resume_manifest" || { die 'Resume manifest must be <work-dir>/release-manifest.json'; return 1; }
		resume_manifest=$(cd "$(dirname "$resume_manifest")" && pwd -P)/$(basename "$resume_manifest") || return 1
		test "$resume_manifest" = "$expected_manifest" || { die 'Resume manifest must be <work-dir>/release-manifest.json'; return 1; }
		if ! jq -e --arg version "$version" --arg git_sha "$git_sha" \
			'.version == $version and .git_sha == $git_sha' "$resume_manifest" >/dev/null; then
			die 'Resume manifest does not match current source'
			return 1
		fi
		if jq -e '(.stages.build? != null) or (.source_zip? != null) or (.freemius_upload_attempt? != null)' "$resume_manifest" >/dev/null; then
			die 'Resume manifest already contains completed build evidence'
			return 1
		fi
		manifest=$resume_manifest
	fi

	if test -n "$(find "$work_dir" -mindepth 1 -maxdepth 1 -print -quit)"; then
		test -n "$resume_manifest" || { die 'Work directory is not empty; use --resume'; return 1; }
	else
		test -z "$resume_manifest" || { die 'Resume manifest disappeared before build'; return 1; }
		manifest_init "$manifest" "$version" "$git_sha" || return 1
	fi

	run_source_gate "$repo" || return 1
	archive=$(build_source_zip "$repo" "$version" "$work_dir" "$manifest" "$git_sha" "$upstream") || return 1
	size=$(wc -c < "$archive" | tr -d '[:space:]') || return 1
	sha=$(shasum -a 256 "$archive" | awk '{ print $1 }') || return 1
	manifest_set "$manifest" \
		--arg source_zip "$archive" \
		--arg source_zip_sha256 "$sha" \
		--arg upstream "$upstream" \
		--argjson source_zip_bytes "$size" \
		'.source_zip = $source_zip |
		 .source_zip_bytes = $source_zip_bytes |
		 .source_zip_sha256 = $source_zip_sha256 |
		 .source_git = { branch: "master", clean: true, upstream: $upstream, matches_upstream: true } |
		 .stages.build = "passed"' || return 1
	printf 'manifest=%s\nsource_zip=%s\n' "$manifest" "$archive"
}

metadata_command() {
	local repo=$1
	printf 'version=%s\n' "$(plugin_header_version "$repo")"
	printf 'runtime_version=%s\n' "$(runtime_version "$repo")"
	printf 'stable_tag=%s\n' "$(stable_tag "$repo")"
}

preflight_command() {
	local repo=$1
	local version=$2
	RELEASE_REPO=$repo assert_version_consistency "$version" || return 1
	if ! git -C "$repo" rev-parse --is-inside-work-tree >/dev/null 2>&1 \
		&& [ "${GRAND_MEDIA_TEST_SKIP_GIT:-0}" = 1 ]; then
		return 0
	fi
	RELEASE_REPO=$repo assert_clean_master || return 1
	for command in php find xargs jq unzip shasum wc tar cmp; do
		require_command "$command" || return 1
	done
	resolve_wp_cli >/dev/null
}

freemius_upload_command() {
	local manifest=$1
	local source_zip version git_sha
	test -f "$manifest" || { die 'freemius-upload requires an existing manifest'; return 1; }
	if ! jq -e '.stages.build == "passed"' "$manifest" >/dev/null; then
		die 'Release manifest is not ready for Freemius upload'
		return 1
	fi
	version=$(jq -r '.version // empty' "$manifest") || return 1
	git_sha=$(jq -r '.git_sha // empty' "$manifest") || return 1
	assert_safe_release_version "$version" || return 1
	assert_safe_git_sha "$git_sha" || return 1
	source_zip=$(verify_recorded_source_artifact "$manifest") || return 1
	upload_pending "$source_zip" "$manifest"
}

freemius_download_command() {
	local manifest=$1
	local manifest_dir version git_sha product_id deployment_id release_mode destination
	test -f "$manifest" || { die 'freemius-download requires an existing manifest'; return 1; }
	manifest_dir=$(cd "$(dirname "$manifest")" && pwd -P) || return 1
	manifest="$manifest_dir/$(basename "$manifest")"
	if ! jq -e '.stages.build == "passed" and .stages.upload_pending == "passed"' "$manifest" >/dev/null; then
		die 'Release manifest is not ready for Freemius download'
		return 1
	fi
	version=$(jq -r '.version // empty' "$manifest") || return 1
	git_sha=$(jq -r '.git_sha // empty' "$manifest") || return 1
	product_id=$(jq -r '.freemius.product_id // empty' "$manifest") || return 1
	deployment_id=$(jq -r '.freemius.deployment_id // empty' "$manifest") || return 1
	release_mode=$(jq -r '.freemius.release_mode // empty' "$manifest") || return 1
	assert_safe_release_version "$version" || return 1
	assert_safe_git_sha "$git_sha" || return 1
	test "$product_id" = "$FREEMIUS_PRODUCT_ID" || { die 'Freemius product does not match Grand Media'; return 1; }
	case $deployment_id in ''|*[!0-9]*) die 'Freemius deployment ID is invalid'; return 1 ;; esac
	test "$release_mode" = pending || { die 'Freemius deployment is not pending'; return 1; }
	destination="$manifest_dir/$GRAND_MEDIA_SLUG-$version-free.zip"
	download_free "$deployment_id" "$destination" "$manifest"
}

verify_archive_layout() {
	local zip=$1
	local label=${2:-Free}
	local entries entry entry_types
	test -f "$zip" || { die "$label ZIP does not exist"; return 1; }
	if ! unzip -tqq "$zip" >/dev/null 2>&1; then
		die "$label ZIP is invalid"
		return 1
	fi
	entries=$(unzip -Z1 "$zip") || { die "$label ZIP is invalid"; return 1; }
	test -n "$entries" || { die "$label ZIP is empty"; return 1; }
	while IFS= read -r entry; do
		case $entry in
			/*|\\*|[A-Za-z]:/*|[A-Za-z]:\\*)
				die "Unsafe ZIP entry: $entry"
				return 1
				;;
		esac
		case "/$entry/" in
			*/../*)
				die "Unsafe ZIP entry: $entry"
				return 1
				;;
		esac
		case $entry in
			"$GRAND_MEDIA_SLUG"|"$GRAND_MEDIA_SLUG"/*) ;;
			*)
				die "$label ZIP entries must be under $GRAND_MEDIA_SLUG/"
				return 1
				;;
		esac
	done <<EOF
$entries
EOF
	printf '%s\n' "$entries" | grep -Fqx "$GRAND_MEDIA_SLUG/" || {
		die "$label ZIP is missing $GRAND_MEDIA_SLUG/ root"
		return 1
	}
	entry_types=$(unzip -Z -l "$zip") || { die "Unable to inspect $label ZIP entry types"; return 1; }
	if printf '%s\n' "$entry_types" | grep '^l' >/dev/null; then
		die "$label ZIP contains a symbolic link"
		return 1
	fi
}

extract_free_zip() {
	local zip=$1
	local target=$2
	local label=${3:-Free}
	local target_name
	target_name=$(basename "$target")
	if test -e "$target" || test -L "$target"; then
		die "Extraction target already exists: $target_name"
		return 1
	fi
	verify_archive_layout "$zip" "$label" || return 1
	mkdir "$target" || return 1
	unzip -qq "$zip" -d "$target" || return 1
	test -d "$target/$GRAND_MEDIA_SLUG" && ! test -L "$target/$GRAND_MEDIA_SLUG" || {
		die "$label ZIP did not extract a $GRAND_MEDIA_SLUG root"
		return 1
	}
}

verify_free_metadata() {
	local root=$1
	local version=$2
	RELEASE_REPO=$root assert_version_consistency "$version"
}

verify_free_exclusions() {
	local root=$1
	local release_root path
	if grep -F 'wp_org_gatekeeper' "$root/grand-media.php" >/dev/null; then
		die 'Free artifact contains wp_org_gatekeeper'
		return 1
	fi
	release_root=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd -P) || return 1
	test -f "$release_root/.distignore" || { die 'Source .distignore does not exist'; return 1; }
	while IFS= read -r path; do
		case $path in ''|'#'*) continue ;; esac
		if test -e "$root/$path" || test -L "$root/$path"; then
			die "Free artifact contains development path: $path"
			return 1
		fi
	done < "$release_root/.distignore"
}

run_artifact_gate() (
	local root=$1
	local work_dir=$2
	local manifest=$3
	local runner=${GRAND_MEDIA_ARTIFACT_PLUGIN_CHECK_CMD:-}
	local activation_runner=${GRAND_MEDIA_ARTIFACT_ACTIVATION_CMD:-}
	local log="$work_dir/artifact-plugin-check.log"
	local activation_log="$work_dir/artifact-activation.log"
	local temporary="$log.tmp.$$"
	local activation_temporary="$activation_log.tmp.$$"
	local rc
	trap 'rm -f "$temporary" "$activation_temporary"' EXIT
	trap 'exit 1' HUP INT TERM
	test -n "$runner" || { die 'artifact Plugin Check runner is not configured'; return 1; }
	test -n "$activation_runner" || { die 'artifact isolated activation runner is not configured'; return 1; }
	if ! find "$root" -name '*.php' -not -path "$root/vendor/*" -print0 | xargs -0 -n1 php -l; then
		die 'Artifact PHP lint failed'
		return 1
	fi
	rm -f "$activation_temporary" || return 1
	if "$activation_runner" "$root" > "$activation_temporary" 2>&1; then
		rc=0
	else
		rc=$?
	fi
	mv "$activation_temporary" "$activation_log" || return 1
	if test "$rc" -ne 0; then
		manifest_set "$manifest" \
			--arg activation_log "$activation_log" \
			--argjson activation_exit "$rc" \
			'.verification = { activation: { exit_code: $activation_exit, log: $activation_log } }' || return 1
		die "artifact isolated activation failed with exit code $rc; see $activation_log"
		return 1
	fi
	rm -f "$temporary" || return 1
	if "$runner" "$root" > "$temporary" 2>&1; then
		rc=0
	else
		rc=$?
	fi
	mv "$temporary" "$log" || return 1
	if test "$rc" -ne 0; then
		manifest_set "$manifest" \
			--arg plugin_check_log "$log" \
			--arg activation_log "$activation_log" \
			--argjson plugin_check_exit "$rc" \
			'.verification = {
			  activation: { exit_code: 0, log: $activation_log },
			  plugin_check: { exit_code: $plugin_check_exit, log: $plugin_check_log }
			}' || return 1
		die "artifact Plugin Check failed with exit code $rc; see $log"
		return 1
	fi
	trap - EXIT HUP INT TERM
)

verify_recorded_artifact() {
	local manifest=$1
	local free_zip expected_size expected_sha actual_size actual_sha
	free_zip=$(jq -r '.free_zip.path // empty' "$manifest") || return 1
	expected_size=$(jq -r '.free_zip.bytes // empty' "$manifest") || return 1
	expected_sha=$(jq -r '.free_zip.sha256 // empty' "$manifest") || return 1
	test -n "$free_zip" || { die 'Release manifest free ZIP path is missing'; return 1; }
	test -f "$free_zip" || { die 'Recorded free ZIP does not exist'; return 1; }
	case $expected_size in ''|*[!0-9]*) die 'Release manifest free ZIP size is invalid'; return 1 ;; esac
	case $expected_sha in ''|*[!0-9a-f]*) die 'Release manifest free ZIP SHA-256 is invalid'; return 1 ;; esac
	test "${#expected_sha}" -eq 64 || { die 'Release manifest free ZIP SHA-256 is invalid'; return 1; }
	actual_size=$(wc -c < "$free_zip" | tr -d '[:space:]') || return 1
	actual_sha=$(shasum -a 256 "$free_zip" | awk '{ print $1 }') || return 1
	test "$actual_size" = "$expected_size" || { die 'Recorded free ZIP size does not match manifest'; return 1; }
	test "$actual_sha" = "$expected_sha" || { die 'Recorded free ZIP SHA-256 does not match manifest'; return 1; }
	printf '%s\n' "$free_zip"
}

assert_verification_provenance() {
	local manifest=$1
	local source_sha deployment_id free_sha verified_source_sha verified_deployment_id verified_free_sha
	assert_download_provenance "$manifest" || return 1
	source_sha=$(jq -r '.source_zip_sha256 // empty' "$manifest") || return 1
	deployment_id=$(jq -r '.freemius.deployment_id // empty' "$manifest") || return 1
	free_sha=$(jq -r '.free_zip.sha256 // empty' "$manifest") || return 1
	verified_source_sha=$(jq -r '.verification.source_zip_sha256 // empty' "$manifest") || return 1
	verified_deployment_id=$(jq -r '.verification.deployment_id // empty' "$manifest") || return 1
	verified_free_sha=$(jq -r '.verification.free_zip_sha256 // empty' "$manifest") || return 1
	test "$verified_source_sha" = "$source_sha" \
		&& test "$verified_deployment_id" = "$deployment_id" \
		&& test "$verified_free_sha" = "$free_sha" || {
		die 'Artifact verification provenance does not match downloaded evidence'
		return 1
	}
}

invalidate_verification_evidence() {
	local manifest=$1
	manifest_set "$manifest" \
		'del(
		  .stages.verify,
		  .stages.svn_prepare,
		  .stages.svn_publish,
		  .verification,
		  .svn,
		  .free_extracted_root,
		  .transformation_summary
		)'
}

assert_svn_publication_not_recorded() {
	local manifest=$1
	if jq -e '(.stages.svn_publish? == "passed") or ((.svn? // {}) | has("publication"))' "$manifest" >/dev/null; then
		die 'SVN publication is already recorded; earlier stages cannot invalidate published evidence'
		return 1
	fi
}

create_transformation_summary() (
	local source_root=$1
	local free_root=$2
	local summary=$3
	local temporary="$summary.tmp.$$"
	local rc
	trap 'rm -f "$temporary"' EXIT
	trap 'exit 1' HUP INT TERM
	rm -f "$temporary" || return 1
	if diff --brief -r "$source_root" "$free_root" > "$temporary"; then
		rc=0
	else
		rc=$?
	fi
	if test "$rc" -gt 1; then
		return 1
	fi
	mv "$temporary" "$summary" || return 1
	trap - EXIT HUP INT TERM
)

verify_command() {
	local manifest=$1
	local work_dir version source_zip free_zip free_target source_target free_root source_root summary
	local plugin_check_exit plugin_check_log activation_exit activation_log free_zip_sha source_zip_sha deployment_id
	test -f "$manifest" || { die 'verify requires an existing manifest'; return 1; }
	manifest=$(cd "$(dirname "$manifest")" && pwd)/$(basename "$manifest") || return 1
	work_dir=$(dirname "$manifest")
	assert_svn_publication_not_recorded "$manifest" || return 1
	invalidate_verification_evidence "$manifest" || return 1
	version=$(jq -r '.version // empty' "$manifest") || return 1
	test -n "$version" || { die 'Release manifest version is missing'; return 1; }
	if ! jq -e '.stages.build == "passed" and .stages.upload_pending == "passed" and .stages.download_free == "passed"' "$manifest" >/dev/null; then
		die 'Release manifest is not ready for artifact verification'
		return 1
	fi
	test -n "${GRAND_MEDIA_ARTIFACT_PLUGIN_CHECK_CMD:-}" || { die 'artifact Plugin Check runner is not configured'; return 1; }
	test -n "${GRAND_MEDIA_ARTIFACT_ACTIVATION_CMD:-}" || { die 'artifact isolated activation runner is not configured'; return 1; }
	source_zip=$(verify_recorded_source_artifact "$manifest") || return 1
	free_zip=$(verify_recorded_artifact "$manifest") || return 1
	assert_download_provenance "$manifest" || return 1
	source_zip_sha=$(jq -r '.source_zip_sha256 // empty' "$manifest") || return 1
	deployment_id=$(jq -r '.freemius.deployment_id // empty' "$manifest") || return 1
	free_zip_sha=$(jq -r '.free_zip.sha256 // empty' "$manifest") || return 1
	free_target="$work_dir/free-extracted"
	source_target="$work_dir/source-extracted"
	extract_free_zip "$free_zip" "$free_target" Free || return 1
	free_root="$free_target/$GRAND_MEDIA_SLUG"
	verify_free_metadata "$free_root" "$version" || return 1
	verify_free_exclusions "$free_root" || return 1
	extract_free_zip "$source_zip" "$source_target" Source || return 1
	source_root="$source_target/$GRAND_MEDIA_SLUG"
	run_artifact_gate "$free_root" "$work_dir" "$manifest" || return 1
	plugin_check_exit=0
	plugin_check_log="$work_dir/artifact-plugin-check.log"
	activation_exit=0
	activation_log="$work_dir/artifact-activation.log"
	summary="$work_dir/freemius-transformations.txt"
	create_transformation_summary "$source_root" "$free_root" "$summary" || return 1
	manifest_set "$manifest" \
		--arg free_root "$free_root" \
		--arg summary "$summary" \
		--arg free_zip_sha "$free_zip_sha" \
		--arg source_zip_sha "$source_zip_sha" \
		--arg deployment_id "$deployment_id" \
		--arg plugin_check_log "$plugin_check_log" \
		--argjson plugin_check_exit "$plugin_check_exit" \
		--arg activation_log "$activation_log" \
		--argjson activation_exit "$activation_exit" \
		'.free_extracted_root = $free_root |
		 .transformation_summary = $summary |
		 .verification = {
		   php_lint: "passed",
		   deployment_id: $deployment_id,
		   source_zip_sha256: $source_zip_sha,
		   free_zip_sha256: $free_zip_sha,
		   plugin_check: { exit_code: $plugin_check_exit, log: $plugin_check_log },
		   activation: { exit_code: $activation_exit, log: $activation_log }
		 } |
		 .stages.verify = "passed"' || return 1
	printf 'verified_root=%s\ntransformation_summary=%s\n' "$free_root" "$summary"
}

invalidate_svn_prepare_evidence() {
	local manifest=$1
	manifest_set "$manifest" 'del(.stages.svn_prepare, .stages.svn_publish, .svn)'
}

assert_clean_svn_checkout() {
	local target=$1
	local status
	status=$(svn status "$target") || { die 'Unable to read SVN checkout status'; return 1; }
	test -z "$status" || { die 'SVN checkout is not clean'; return 1; }
}

assert_svn_checkout_layout() {
	local checkout=$1
	local top_level path canonical_path canonical_parent
	for top_level in .svn trunk tags assets; do
		path="$checkout/$top_level"
		test -d "$path" && ! test -L "$path" || {
			die "SVN checkout requires a real top-level $top_level directory"
			return 1
		}
		canonical_path=$(cd "$path" && pwd -P) || {
			die "Unable to canonicalize SVN checkout $top_level directory"
			return 1
		}
		canonical_parent=$(cd "$(dirname "$canonical_path")" && pwd -P) || {
			die "Unable to canonicalize SVN checkout $top_level parent"
			return 1
		}
		test "$canonical_path" = "$path" && test "$canonical_parent" = "$checkout" || {
			die "SVN checkout $top_level directory is not a direct safe child"
			return 1
		}
	done
	printf '%s\n' "$checkout/trunk"
}

assert_svn_checkout_outside_tool_repo() {
	local tool_repo=$1
	local checkout=$2
	local trunk=$3
	local candidate
	for candidate in "$checkout" "$trunk"; do
		case "$candidate/" in
			"$tool_repo"/*) die 'SVN checkout must be outside the plugin Git root'; return 1 ;;
		esac
		case "$tool_repo/" in
			"$candidate"/*) die 'SVN checkout must be outside the plugin Git root'; return 1 ;;
		esac
	done
}

remote_tag_exists() {
	local repository_url=$1
	local version=$2
	local tags
	tags=$(svn list "$repository_url/tags") || { die 'Unable to list remote SVN tags'; return 2; }
	printf '%s\n' "$tags" | grep -Fqx "$version/"
}

schedule_svn_changes() {
	local status line missing target
	svn add --force trunk || { die 'Unable to schedule SVN additions'; return 1; }
	status=$(svn status trunk) || { die 'Unable to inspect prepared SVN trunk'; return 1; }
	while IFS= read -r line; do
		case $line in
			'!       '*)
				missing=${line#'!       '}
				case $missing in
					trunk/*)
						case "/$missing/" in
							*/../*) die 'SVN reported an unsafe missing path'; return 1 ;;
						esac
						;;
					*) die 'SVN reported a missing path outside trunk'; return 1 ;;
				esac
				case $missing in
					*@*) target="$missing@" ;;
					*) target=$missing ;;
				esac
				svn rm --force "$target" || { die "Unable to schedule SVN removal: $missing"; return 1; }
				;;
		esac
	done <<EOF
$status
EOF
}

canonicalize_svn_temp_parent() {
	local selected_parent=$1
	test -n "$selected_parent" && test -d "$selected_parent" && ! test -L "$selected_parent" || {
		die 'SVN preparation temp parent does not exist'
		return 1
	}
	(cd "$selected_parent" && pwd -P) || {
		die 'Unable to canonicalize SVN preparation temp parent'
		return 1
	}
}

is_svn_prepare_temp_basename() {
	local suffix
	case $1 in
		grand-media-svn-prepare.*) suffix=${1#grand-media-svn-prepare.} ;;
		*) return 1 ;;
	esac
	case $suffix in
		??????)
			case $suffix in *[!A-Za-z0-9]*) return 1 ;; *) return 0 ;; esac
			;;
		*) return 1 ;;
	esac
}

create_svn_prepare_temp() {
	local selected_parent=$1
	local temporary_parent returned_root temporary_root actual_parent basename marker entries
	temporary_parent=$(canonicalize_svn_temp_parent "$selected_parent") || return 1
	returned_root=$(mktemp -d "$temporary_parent/grand-media-svn-prepare.XXXXXX") || return 1
	if ! test -d "$returned_root" || test -L "$returned_root"; then
		die 'Unsafe SVN preparation temporary directory'
		return 1
	fi
	temporary_root=$(cd "$returned_root" && pwd -P) || {
		die 'Unsafe SVN preparation temporary directory'
		return 1
	}
	actual_parent=$(cd "$(dirname "$temporary_root")" && pwd -P) || {
		die 'Unsafe SVN preparation temporary directory'
		return 1
	}
	basename=$(basename "$temporary_root") || return 1
	if test "$actual_parent" != "$temporary_parent" || ! is_svn_prepare_temp_basename "$basename"; then
		die 'Unsafe SVN preparation temporary directory'
		return 1
	fi
	entries=$(find "$temporary_root" -mindepth 1 -maxdepth 1 -print -quit) || return 1
	if test -n "$entries"; then
		die 'Unsafe SVN preparation temporary directory is not empty'
		return 1
	fi
	require_command cmp || return 1
	marker="$temporary_root/.grand-media-svn-prepare"
	if ! (umask 077; set -C; printf 'path=%s\nparent=%s\n' "$temporary_root" "$temporary_parent" > "$marker"); then
		die 'Unable to create exclusive SVN preparation temp marker'
		return 1
	fi
	if ! test -f "$marker" || test -L "$marker"; then
		die 'Unsafe SVN preparation temp marker'
		return 1
	fi
	if ! printf 'path=%s\nparent=%s\n' "$temporary_root" "$temporary_parent" | cmp -s - "$marker"; then
		die 'SVN preparation temp marker ownership mismatch'
		return 1
	fi
	printf '%s\n' "$temporary_root"
}

cleanup_svn_prepare_temp() {
	local temporary_root=$1
	local expected_parent=$2
	local canonical_parent canonical_root actual_parent basename marker
	test -n "$temporary_root" && test -n "$expected_parent" || return 1
	test -d "$expected_parent" && ! test -L "$expected_parent" || return 1
	canonical_parent=$(cd "$expected_parent" && pwd -P) || return 1
	test "$canonical_parent" = "$expected_parent" || return 1
	test -d "$temporary_root" && ! test -L "$temporary_root" || return 1
	canonical_root=$(cd "$temporary_root" && pwd -P) || return 1
	test "$canonical_root" = "$temporary_root" || return 1
	actual_parent=$(cd "$(dirname "$canonical_root")" && pwd -P) || return 1
	test "$actual_parent" = "$expected_parent" || return 1
	basename=$(basename "$canonical_root") || return 1
	is_svn_prepare_temp_basename "$basename" || return 1
	marker="$canonical_root/.grand-media-svn-prepare"
	test -f "$marker" && ! test -L "$marker" || return 1
	command -v cmp >/dev/null 2>&1 || return 1
	printf 'path=%s\nparent=%s\n' "$canonical_root" "$expected_parent" | cmp -s - "$marker" || return 1
	rm -rf "$canonical_root"
}

svn_prepare_exit_cleanup() {
	local operation_status=$1
	local temporary_root=$2
	local temporary_parent=$3
	trap - EXIT
	if cleanup_svn_prepare_temp "$temporary_root" "$temporary_parent"; then
		exit "$operation_status"
	fi
	if test "$operation_status" -ne 0; then
		exit "$operation_status"
	fi
	exit 1
}

svn_publish_exit_cleanup() {
	local operation_status=$1
	local temporary_root=$2
	local temporary_parent=$3
	local commit_attempted=$4
	trap - EXIT
	if cleanup_svn_prepare_temp "$temporary_root" "$temporary_parent"; then
		exit "$operation_status"
	fi
	if test "$commit_attempted" -eq 1; then
		die 'SVN commit may already have succeeded; inspect before retrying'
	fi
	exit 1
}

svn_publish_interrupted() {
	local commit_attempted=$1
	if test "$commit_attempted" -eq 1; then
		die 'SVN commit may have succeeded; do not retry automatically; inspect remote state first'
	fi
	exit 1
}

freemius_release_interrupted() {
	die 'Freemius release may have succeeded; do not retry automatically; inspect remote state first'
	exit 1
}

svn_prepare() (
	local manifest=$1
	local supplied_checkout=$2
	local manifest_dir checkout tool_repo free_root recorded_root version repository_root working_copy_url start_revision
	local free_zip recorded_free_sha verified_free_sha source_sha deployment_id temporary_parent temporary_root fresh_target fresh_root
	local trunk tag remote_rc status_summary diff_summary
	require_command jq || return 1
	test -f "$manifest" || { die 'svn-prepare requires an existing manifest'; return 1; }
	manifest_dir=$(cd "$(dirname "$manifest")" && pwd -P) || return 1
	manifest="$manifest_dir/$(basename "$manifest")"
	assert_svn_publication_not_recorded "$manifest" || return 1
	invalidate_svn_prepare_evidence "$manifest" || return 1

	if ! jq -e '.stages.build == "passed" and .stages.upload_pending == "passed" and .stages.download_free == "passed" and .stages.verify == "passed"' "$manifest" >/dev/null; then
		die 'Release manifest is not ready for SVN preparation'
		return 1
	fi
	version=$(jq -r '.version // empty' "$manifest") || return 1
	assert_safe_release_version "$version" || return 1
	recorded_free_sha=$(jq -r '.free_zip.sha256 // empty' "$manifest") || return 1
	verified_free_sha=$(jq -r '.verification.free_zip_sha256 // empty' "$manifest") || return 1
	test -n "$verified_free_sha" && test "$verified_free_sha" = "$recorded_free_sha" || {
		die 'Verified free ZIP SHA-256 does not match recorded artifact'
		return 1
	}
	recorded_root=$(jq -r '.free_extracted_root // empty' "$manifest") || return 1
	test -n "$recorded_root" && test -d "$recorded_root" && ! test -L "$recorded_root" || { die 'Verified free root does not exist'; return 1; }
	free_root=$(cd "$recorded_root" && pwd -P) || { die 'Verified free root does not exist'; return 1; }

	test -n "$supplied_checkout" && test -d "$supplied_checkout" && ! test -L "$supplied_checkout" || { die 'SVN checkout does not exist'; return 1; }
	checkout=$(cd "$supplied_checkout" && pwd -P) || { die 'Unable to canonicalize SVN checkout'; return 1; }
	tool_repo=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd -P) || return 1
	test "$checkout" != / || { die 'SVN checkout must not be the filesystem root'; return 1; }
	assert_svn_checkout_outside_tool_repo "$tool_repo" "$checkout" "$checkout/trunk" || return 1
	trunk=$(assert_svn_checkout_layout "$checkout") || return 1
	assert_svn_checkout_outside_tool_repo "$tool_repo" "$checkout" "$trunk" || return 1
	case "$free_root/" in "$checkout/"*) die 'SVN checkout must not contain the verified free root'; return 1 ;; esac
	case "$checkout/" in "$free_root/"*) die 'SVN checkout must not be inside the verified free root'; return 1 ;; esac
	case "$manifest/" in "$checkout/"*) die 'SVN checkout must not contain the release manifest'; return 1 ;; esac

	require_command svn || return 1
	verify_recorded_source_artifact "$manifest" >/dev/null || return 1
	assert_verification_provenance "$manifest" || return 1
	source_sha=$(jq -r '.source_zip_sha256 // empty' "$manifest") || return 1
	deployment_id=$(jq -r '.freemius.deployment_id // empty' "$manifest") || return 1
	require_command rsync || return 1
	require_command diff || return 1
	free_zip=$(verify_recorded_artifact "$manifest") || return 1
	temporary_parent=$(canonicalize_svn_temp_parent "${TMPDIR:-/tmp}") || return 1
	temporary_root=$(create_svn_prepare_temp "$temporary_parent") || return 1
	trap 'svn_prepare_exit_cleanup "$?" "$temporary_root" "$temporary_parent"' EXIT
	trap 'exit 1' HUP INT TERM
	fresh_target="$temporary_root/free-extracted"
	extract_free_zip "$free_zip" "$fresh_target" Free || return 1
	fresh_root="$fresh_target/$GRAND_MEDIA_SLUG"
	if ! diff -qr "$fresh_root" "$free_root" >/dev/null; then
		die 'Recorded verified root does not match hash-bound free ZIP'
		return 1
	fi
	RELEASE_REPO=$fresh_root assert_version_consistency "$version" || return 1

	cd "$checkout" || return 1
	assert_clean_svn_checkout . || return 1
	repository_root=$(svn info --show-item repos-root-url .) || { die 'Unable to read SVN repository root'; return 1; }
	test "$repository_root" = "$WORDPRESS_ORG_SVN_REPOSITORY_ROOT" || {
		die 'SVN repository root does not match Grand Media'
		return 1
	}
	working_copy_url=$(svn info --show-item url .) || { die 'Unable to read SVN working-copy URL'; return 1; }
	test "$working_copy_url" = "$WORDPRESS_ORG_SVN_WORKING_COPY_URL" || {
		die 'SVN working-copy URL does not match Grand Media'
		return 1
	}
	if ! svn update .; then
		die 'SVN update failed'
		return 1
	fi
	assert_clean_svn_checkout . || return 1
	trunk=$(assert_svn_checkout_layout "$checkout") || return 1
	assert_svn_checkout_outside_tool_repo "$tool_repo" "$checkout" "$trunk" || return 1
	start_revision=$(svn info --show-item revision .) || { die 'Unable to read starting SVN revision'; return 1; }
	case $start_revision in ''|*[!0-9]*) die 'Starting SVN revision is invalid'; return 1 ;; esac
	if remote_tag_exists "$working_copy_url" "$version"; then
		die "SVN tag $version already exists remotely"
		return 1
	else
		remote_rc=$?
		test "$remote_rc" -eq 1 || return "$remote_rc"
	fi
	tag="$checkout/tags/$version"
	test ! -e "$tag" && ! test -L "$tag" || { die 'Local SVN tag destination already exists'; return 1; }

	rsync -a --delete --exclude=.svn "$fresh_root/" "$trunk/" || { die 'Unable to mirror verified free root into SVN trunk'; return 1; }
	schedule_svn_changes || return 1
	if remote_tag_exists "$working_copy_url" "$version"; then
		die "SVN tag $version already exists remotely"
		return 1
	else
		remote_rc=$?
		test "$remote_rc" -eq 1 || return "$remote_rc"
	fi
	svn copy trunk "tags/$version" || { die 'Unable to create local SVN tag'; return 1; }
	if ! diff -qr --exclude=.svn "$fresh_root" "$trunk" >/dev/null; then
		die 'Prepared SVN trunk does not match verified free root'
		return 1
	fi
	if ! diff -qr --exclude=.svn "$fresh_root" "$tag" >/dev/null; then
		die 'Prepared SVN tag does not match verified free root'
		return 1
	fi
	status_summary=$(svn status .) || { die 'Unable to read prepared SVN status'; return 1; }
	diff_summary=$(svn diff --summarize .) || { die 'Unable to summarize prepared SVN diff'; return 1; }
	printf '%s\n' "$status_summary"
	printf '%s\n' "$diff_summary"
	manifest_set "$manifest" \
		--arg checkout "$checkout" \
		--arg repository_root "$repository_root" \
		--arg working_copy_url "$working_copy_url" \
		--arg start_revision "$start_revision" \
		--arg verified_root "$free_root" \
		--arg deployment_id "$deployment_id" \
		--arg source_sha "$source_sha" \
		--arg free_sha "$recorded_free_sha" \
		--arg trunk "$trunk" \
		--arg tag "$tag" \
		--arg status "$status_summary" \
		--arg diff_summary "$diff_summary" \
		'.svn = {
		  checkout: $checkout,
		  repository_root: $repository_root,
		  url: $working_copy_url,
		  start_revision: $start_revision,
		  verified_root: $verified_root,
		  deployment_id: $deployment_id,
		  source_zip_sha256: $source_sha,
		  free_zip_sha256: $free_sha,
		  trunk: $trunk,
		  tag: $tag,
		  status: $status,
		  diff_summary: $diff_summary
		} | .stages.svn_prepare = "passed"' || return 1
	printf 'manifest=%s\nsvn_checkout=%s\nsvn_tag=%s\n' "$manifest" "$checkout" "$tag"
)

required_confirmation() {
	local action=$1
	local version=$2
	local git_sha=$3
	case $action in
		svn) printf 'publish svn %s %s\n' "$version" "$git_sha" ;;
		freemius) printf 'release freemius %s %s\n' "$version" "$git_sha" ;;
		*) die 'Unknown protected release action'; return 1 ;;
	esac
}

assert_exact_confirmation() {
	local action=$1
	local version=$2
	local git_sha=$3
	local supplied=$4
	local expected
	expected=$(required_confirmation "$action" "$version" "$git_sha") || return 1
	test "$supplied" = "$expected" || {
		die "Exact --confirm value required: $expected"
		return 1
	}
}

svn_status_has_scheduled_tag() {
	local status=$1
	local version=$2
	local line
	while IFS= read -r line; do
		case $line in
			A*"tags/$version"|A*"tags/$version/") return 0 ;;
		esac
	done <<EOF
$status
EOF
	return 1
}

svn_publish() (
	local manifest=$1
	local confirmation=$2
	local manifest_dir version git_sha checkout recorded_checkout tool_repo repository_root working_copy_url start_revision current_revision
	local recorded_root free_root recorded_trunk recorded_tag trunk tag recorded_status recorded_diff current_status current_diff
	local free_zip recorded_free_sha verified_free_sha source_sha deployment_id svn_source_sha svn_deployment_id svn_free_sha temporary_parent temporary_root fresh_target fresh_root remote_rc
	local commit_message commit_output commit_response revision timestamp commit_attempted=0
	require_command jq || return 1
	test -f "$manifest" || { die 'svn-publish requires an existing manifest'; return 1; }
	manifest_dir=$(cd "$(dirname "$manifest")" && pwd -P) || return 1
	manifest="$manifest_dir/$(basename "$manifest")"
	version=$(jq -r '.version // empty' "$manifest") || return 1
	git_sha=$(jq -r '.git_sha // empty' "$manifest") || return 1
	assert_safe_release_version "$version" || return 1
	assert_safe_git_sha "$git_sha" || return 1
	assert_exact_confirmation svn "$version" "$git_sha" "$confirmation" || return 1
	if ! jq -e '.stages.build == "passed" and .stages.upload_pending == "passed" and .stages.download_free == "passed" and .stages.verify == "passed" and .stages.svn_prepare == "passed"' "$manifest" >/dev/null; then
		die 'Release manifest is not ready for SVN publication'
		return 1
	fi
	verify_recorded_source_artifact "$manifest" >/dev/null || return 1
	assert_verification_provenance "$manifest" || return 1
	source_sha=$(jq -r '.source_zip_sha256 // empty' "$manifest") || return 1
	deployment_id=$(jq -r '.freemius.deployment_id // empty' "$manifest") || return 1

	recorded_checkout=$(jq -r '.svn.checkout // empty' "$manifest") || return 1
	test -n "$recorded_checkout" && test -d "$recorded_checkout" && ! test -L "$recorded_checkout" || { die 'Recorded SVN checkout does not exist'; return 1; }
	checkout=$(cd "$recorded_checkout" && pwd -P) || { die 'Unable to canonicalize recorded SVN checkout'; return 1; }
	test "$checkout" = "$recorded_checkout" || { die 'Recorded SVN checkout is not canonical'; return 1; }
	tool_repo=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd -P) || return 1
	assert_svn_checkout_outside_tool_repo "$tool_repo" "$checkout" "$checkout/trunk" || return 1
	trunk=$(assert_svn_checkout_layout "$checkout") || return 1
	assert_svn_checkout_outside_tool_repo "$tool_repo" "$checkout" "$trunk" || return 1
	repository_root=$(jq -r '.svn.repository_root // empty' "$manifest") || return 1
	working_copy_url=$(jq -r '.svn.url // empty' "$manifest") || return 1
	start_revision=$(jq -r '.svn.start_revision // empty' "$manifest") || return 1
	recorded_root=$(jq -r '.svn.verified_root // empty' "$manifest") || return 1
	recorded_trunk=$(jq -r '.svn.trunk // empty' "$manifest") || return 1
	recorded_tag=$(jq -r '.svn.tag // empty' "$manifest") || return 1
	recorded_status=$(jq -r '.svn.status // empty' "$manifest") || return 1
	recorded_diff=$(jq -r '.svn.diff_summary // empty' "$manifest") || return 1
	svn_source_sha=$(jq -r '.svn.source_zip_sha256 // empty' "$manifest") || return 1
	svn_deployment_id=$(jq -r '.svn.deployment_id // empty' "$manifest") || return 1
	svn_free_sha=$(jq -r '.svn.free_zip_sha256 // empty' "$manifest") || return 1
	test "$repository_root" = "$WORDPRESS_ORG_SVN_REPOSITORY_ROOT" || { die 'Recorded SVN repository root does not match Grand Media'; return 1; }
	test "$working_copy_url" = "$WORDPRESS_ORG_SVN_WORKING_COPY_URL" || { die 'Recorded SVN working-copy URL does not match Grand Media'; return 1; }
	case $start_revision in ''|*[!0-9]*) die 'Recorded SVN revision is invalid'; return 1 ;; esac
	tag="$checkout/tags/$version"
	test "$recorded_trunk" = "$trunk" || { die 'Recorded SVN trunk path does not match checkout'; return 1; }
	test "$recorded_tag" = "$tag" || { die 'Recorded SVN tag path does not match version'; return 1; }
	test "$svn_source_sha" = "$source_sha" && test "$svn_deployment_id" = "$deployment_id" || {
		die 'SVN preparation provenance does not match verified artifact'
		return 1
	}
	test -d "$tag" && ! test -L "$tag" || { die 'Prepared local SVN tag does not exist'; return 1; }
	svn_status_has_scheduled_tag "$recorded_status" "$version" || { die 'Prepared SVN evidence does not contain the scheduled tag'; return 1; }

	recorded_free_sha=$(jq -r '.free_zip.sha256 // empty' "$manifest") || return 1
	verified_free_sha=$(jq -r '.verification.free_zip_sha256 // empty' "$manifest") || return 1
	test "$svn_free_sha" = "$recorded_free_sha" || { die 'SVN preparation provenance does not match free ZIP'; return 1; }
	test -n "$verified_free_sha" && test "$verified_free_sha" = "$recorded_free_sha" || { die 'Verified free ZIP SHA-256 does not match recorded artifact'; return 1; }
	free_zip=$(verify_recorded_artifact "$manifest") || return 1
	test -n "$recorded_root" && test -d "$recorded_root" && ! test -L "$recorded_root" || { die 'Recorded verified free root does not exist'; return 1; }
	free_root=$(cd "$recorded_root" && pwd -P) || return 1
	test "$free_root" = "$recorded_root" || { die 'Recorded verified free root is not canonical'; return 1; }
	case "$free_root/" in "$checkout/"*) die 'SVN checkout must not contain the verified free root'; return 1 ;; esac
	case "$checkout/" in "$free_root/"*) die 'SVN checkout must not be inside the verified free root'; return 1 ;; esac
	case "$manifest/" in "$checkout/"*) die 'SVN checkout must not contain the release manifest'; return 1 ;; esac
	temporary_parent=$(canonicalize_svn_temp_parent "${TMPDIR:-/tmp}") || return 1
	temporary_root=$(create_svn_prepare_temp "$temporary_parent") || return 1
	trap 'svn_publish_exit_cleanup "$?" "$temporary_root" "$temporary_parent" "$commit_attempted"' EXIT
	trap 'svn_publish_interrupted "$commit_attempted"' HUP INT TERM
	fresh_target="$temporary_root/free-extracted"
	extract_free_zip "$free_zip" "$fresh_target" Free || return 1
	fresh_root="$fresh_target/$GRAND_MEDIA_SLUG"
	RELEASE_REPO=$fresh_root assert_version_consistency "$version" || return 1
	for recorded_trunk in "$free_root" "$trunk" "$tag"; do
		if ! diff -qr --exclude=.svn "$fresh_root" "$recorded_trunk" >/dev/null; then
			die 'Prepared SVN content does not match the verified free artifact'
			return 1
		fi
	done

	require_command svn || return 1
	cd "$checkout" || return 1
	trunk=$(assert_svn_checkout_layout "$checkout") || return 1
	assert_svn_checkout_outside_tool_repo "$tool_repo" "$checkout" "$trunk" || return 1
	test "$(svn info --show-item repos-root-url .)" = "$repository_root" || { die 'SVN repository root drifted after preparation'; return 1; }
	test "$(svn info --show-item url .)" = "$working_copy_url" || { die 'SVN working-copy URL drifted after preparation'; return 1; }
	current_revision=$(svn info --show-item revision .) || { die 'Unable to read current SVN revision'; return 1; }
	test "$current_revision" = "$start_revision" || { die 'SVN checkout revision drifted after preparation'; return 1; }
	current_status=$(svn status .) || { die 'Unable to read current SVN status'; return 1; }
	current_diff=$(svn diff --summarize .) || { die 'Unable to summarize current SVN diff'; return 1; }
	test "$current_status" = "$recorded_status" || { die 'SVN status drifted after preparation'; return 1; }
	test "$current_diff" = "$recorded_diff" || { die 'SVN diff drifted after preparation'; return 1; }
	svn_status_has_scheduled_tag "$current_status" "$version" || { die 'Current SVN status does not contain the scheduled tag'; return 1; }
	if remote_tag_exists "$working_copy_url" "$version"; then
		die "SVN tag $version now exists remotely; refusing to overwrite it"
		return 1
	else
		remote_rc=$?
		test "$remote_rc" -eq 1 || return "$remote_rc"
	fi

	commit_message="Release $version"
	commit_response="$temporary_root/svn-commit-output"
	commit_attempted=1
	trap 'svn_publish_interrupted "$commit_attempted"' HUP INT TERM
	if ! LC_ALL=C svn commit . -m "$commit_message" > "$commit_response"; then
		die 'SVN commit failed and may already have succeeded; inspect before retrying'
		return 1
	fi
	commit_output=$(cat "$commit_response") || { die 'SVN commit may already have succeeded; inspect before retrying'; return 1; }
	if ! revision=$(printf '%s\n' "$commit_output" | sed -n 's/^Committed revision \([0-9][0-9]*\)\.$/\1/p' | tail -1); then
		die 'SVN commit may already have succeeded; inspect before retrying'
		return 1
	fi
	case $revision in ''|*[!0-9]*) die 'SVN commit may already have succeeded; inspect before retrying'; return 1 ;; esac
	if ! cleanup_svn_prepare_temp "$temporary_root" "$temporary_parent"; then
		die 'SVN commit may already have succeeded; inspect before retrying'
		return 1
	fi
	trap - EXIT
	timestamp=$(date -u +%Y-%m-%dT%H:%M:%SZ) || { die 'SVN commit may already have succeeded; inspect before retrying'; return 1; }
	if ! manifest_set "$manifest" \
		--argjson revision "$revision" \
		--arg timestamp "$timestamp" \
		'.svn.publication = { revision: $revision, timestamp: $timestamp } | .stages.svn_publish = "passed"'; then
		die 'SVN commit may already have succeeded; inspect before retrying'
		return 1
	fi
	if ! printf 'svn_revision=%s\n' "$revision"; then
		die 'SVN commit may already have succeeded; inspect before retrying'
		return 1
	fi
	trap - HUP INT TERM
)

validate_freemius_deployment_response() {
	local response=$1
	local expected_id=$2
	local expected_version=$3
	local expected_mode=$4
	local product_id deployment_id response_version release_mode
	if ! printf '%s\n' "$response" | jq -e '(.plugin_id | type) == "string" and (.version | type) == "string" and (.release_mode | type) == "string"' >/dev/null; then
		die 'Freemius deployment response fields are invalid'
		return 1
	fi
	product_id=$(printf '%s\n' "$response" | jq -r '.plugin_id // empty') || return 1
	deployment_id=$(printf '%s\n' "$response" | jq -r '.id // empty') || return 1
	response_version=$(printf '%s\n' "$response" | jq -r '.version // empty') || return 1
	release_mode=$(printf '%s\n' "$response" | jq -r '.release_mode // empty') || return 1
	test "$product_id" = "$FREEMIUS_PRODUCT_ID" || { die 'Freemius deployment product does not match Grand Media'; return 1; }
	test "$deployment_id" = "$expected_id" || { die 'Freemius deployment ID does not match manifest'; return 1; }
	test "$response_version" = "$expected_version" || { die 'Freemius deployment version does not match manifest'; return 1; }
	test "$release_mode" = "$expected_mode" || { die "Freemius deployment is not $expected_mode"; return 1; }
}

select_freemius_deployment_from_list() {
	local response=$1
	local expected_id=$2
	local deployment
	if ! deployment=$(printf '%s\n' "$response" | jq -cer --arg expected_id "$expected_id" '
		if (.tags | type) != "array" then
			error("deployment list does not contain tags")
		else
			[.tags[] | select((.id | tostring) == $expected_id)] as $matches |
			if ($matches | length) == 1 then $matches[0]
			else error("deployment list must contain the exact recorded ID once") end
		end'); then
		die 'Freemius deployment list does not contain the exact recorded deployment'
		return 1
	fi
	printf '%s\n' "$deployment"
}

freemius_release() (
	local manifest=$1
	local confirmation=$2
	local manifest_dir version git_sha product_id deployment_id release_mode list_url update_url list_response response response_file timestamp
	require_command jq || return 1
	test -f "$manifest" || { die 'freemius-release requires an existing manifest'; return 1; }
	manifest_dir=$(cd "$(dirname "$manifest")" && pwd -P) || return 1
	manifest="$manifest_dir/$(basename "$manifest")"
	version=$(jq -r '.version // empty' "$manifest") || return 1
	git_sha=$(jq -r '.git_sha // empty' "$manifest") || return 1
	assert_safe_release_version "$version" || return 1
	assert_safe_git_sha "$git_sha" || return 1
	assert_exact_confirmation freemius "$version" "$git_sha" "$confirmation" || return 1
	if ! jq -e '.stages.build == "passed" and .stages.upload_pending == "passed" and .stages.download_free == "passed" and .stages.verify == "passed"' "$manifest" >/dev/null; then
		die 'Release manifest is not ready for Freemius release'
		return 1
	fi
	verify_recorded_source_artifact "$manifest" >/dev/null || return 1
	verify_recorded_artifact "$manifest" >/dev/null || return 1
	assert_verification_provenance "$manifest" || return 1
	product_id=$(jq -r '.freemius.product_id // empty' "$manifest") || return 1
	deployment_id=$(jq -r '.freemius.deployment_id // empty' "$manifest") || return 1
	release_mode=$(jq -r '.freemius.release_mode // empty' "$manifest") || return 1
	test "$product_id" = "$FREEMIUS_PRODUCT_ID" || { die 'Freemius product does not match Grand Media'; return 1; }
	case $deployment_id in ''|*[!0-9]*) die 'Freemius deployment ID is invalid'; return 1 ;; esac
	test "$release_mode" = pending || { die 'Manifest-recorded Freemius deployment is not pending'; return 1; }
	list_url="https://api.freemius.com/v1/products/$FREEMIUS_PRODUCT_ID/tags.json?fields=id,plugin_id,version,release_mode&count=50"
	update_url="https://api.freemius.com/v1/products/$FREEMIUS_PRODUCT_ID/tags/$deployment_id.json"
	list_response=$(freemius_request --request GET "$list_url") || { die 'Unable to list Freemius deployments'; return 1; }
	response=$(select_freemius_deployment_from_list "$list_response" "$deployment_id") || return 1
	validate_freemius_deployment_response "$response" "$deployment_id" "$version" pending || return 1
	response_file=$(mktemp "$manifest_dir/.grand-media-freemius-release.XXXXXX") || return 1
	chmod 600 "$response_file" || { rm -f "$response_file"; return 1; }
	trap 'rm -f "$response_file"' EXIT
	trap 'freemius_release_interrupted' HUP INT TERM
	if ! freemius_request --request PUT --header 'Content-Type: application/json' --data '{"release_mode":"released"}' "$update_url" > "$response_file"; then
		die 'Freemius release request failed and may already have succeeded; inspect before retrying'
		return 1
	fi
	response=$(cat "$response_file") || { die 'Freemius release may already have succeeded; inspect before retrying'; return 1; }
	rm -f "$response_file" || { die 'Freemius release may already have succeeded; inspect before retrying'; return 1; }
	trap - EXIT
	if ! validate_freemius_deployment_response "$response" "$deployment_id" "$version" released; then
		die 'Freemius release may already have succeeded; inspect before retrying'
		return 1
	fi
	timestamp=$(date -u +%Y-%m-%dT%H:%M:%SZ) || { die 'Freemius release may already have succeeded; inspect before retrying'; return 1; }
	if ! manifest_set "$manifest" \
		--arg release_mode released \
		--arg timestamp "$timestamp" \
		--arg list_endpoint_class 'GET /v1/products/20980/tags.json?fields=id,plugin_id,version,release_mode&count=50' \
		--arg update_endpoint_class 'PUT /v1/products/20980/tags/{deployment_id}.json' \
		'.freemius.release_mode = $release_mode |
		 .freemius.released_at = $timestamp |
		 .freemius.release_list_endpoint_class = $list_endpoint_class |
		 .freemius.release_update_endpoint_class = $update_endpoint_class |
		 .stages.freemius_release = "passed"'; then
		die 'Freemius release may already have succeeded; inspect before retrying'
		return 1
	fi
	if ! printf 'freemius_deployment=%s\nrelease_mode=released\n' "$deployment_id"; then
		die 'Freemius release may already have succeeded; inspect before retrying'
		return 1
	fi
	trap - HUP INT TERM
)

release_main() {
	local command=${1:-}
	shift || true
	local repo='' version='' work_dir='' resume_manifest='' manifest='' checkout='' confirmation=''
	while [ "$#" -gt 0 ]; do
		case $1 in
			--repo) test "$#" -ge 2 || { die '--repo requires a path'; return 1; }; repo=$2; shift 2 ;;
			--version) test "$#" -ge 2 || { die '--version requires a value'; return 1; }; version=$2; shift 2 ;;
			--work-dir) test "$#" -ge 2 || { die '--work-dir requires a path'; return 1; }; work_dir=$2; shift 2 ;;
			--resume) test "$#" -ge 2 || { die '--resume requires a manifest path'; return 1; }; resume_manifest=$2; shift 2 ;;
			--manifest) test "$#" -ge 2 || { die '--manifest requires a path'; return 1; }; manifest=$2; shift 2 ;;
			--checkout) test "$#" -ge 2 || { die '--checkout requires a path'; return 1; }; checkout=$2; shift 2 ;;
			--confirm) test "$#" -ge 2 || { die '--confirm requires a value'; return 1; }; confirmation=$2; shift 2 ;;
			*) die "Unknown option: $1"; return 1 ;;
		esac
	done
	case $command in
		metadata) test -n "$repo" || { die 'metadata requires --repo PATH'; return 1; }; metadata_command "$repo" ;;
		preflight)
			test -n "$repo" || { die 'preflight requires --repo PATH'; return 1; }
			test -n "$version" || { die 'preflight requires --version VERSION'; return 1; }
			preflight_command "$repo" "$version"
			;;
		build)
			test -n "$repo" || { die 'build requires --repo PATH'; return 1; }
			test -n "$version" || { die 'build requires --version VERSION'; return 1; }
			build_command "$repo" "$version" "$work_dir" "$resume_manifest"
			;;
		freemius-upload)
			test -n "$manifest" || { die 'freemius-upload requires --manifest MANIFEST'; return 1; }
			freemius_upload_command "$manifest"
			;;
		freemius-download)
			test -n "$manifest" || { die 'freemius-download requires --manifest MANIFEST'; return 1; }
			freemius_download_command "$manifest"
			;;
		verify)
			if test -n "$manifest" && test -n "$resume_manifest" && test "$manifest" != "$resume_manifest"; then
				die 'verify accepts only one manifest path'
				return 1
			fi
			manifest=${manifest:-$resume_manifest}
			test -n "$manifest" || { die 'verify requires --manifest MANIFEST'; return 1; }
			verify_command "$manifest"
			;;
		svn-prepare)
			test -n "$manifest" || { die 'svn-prepare requires --manifest MANIFEST'; return 1; }
			test -n "$checkout" || { die 'svn-prepare requires --checkout PATH'; return 1; }
			svn_prepare "$manifest" "$checkout"
			;;
		svn-publish)
			test -n "$manifest" || { die 'svn-publish requires --manifest MANIFEST'; return 1; }
			svn_publish "$manifest" "$confirmation"
			;;
		freemius-release)
			test -n "$manifest" || { die 'freemius-release requires --manifest MANIFEST'; return 1; }
			freemius_release "$manifest" "$confirmation"
			;;
		*) die 'Usage: grand-media-release {metadata|preflight|build|freemius-upload|freemius-download|verify|svn-prepare|svn-publish|freemius-release} [options]'; return 1 ;;
	esac
}
