#!/usr/bin/env bash
set -u

ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
CLI="$ROOT/bin/grand-media-release"
TEST_LOCAL_ACTIVATION_RUNNER="$ROOT/bin/release/runners/test-local-activate"
TEST_LOCAL_PLUGIN_CHECK_RUNNER="$ROOT/bin/release/runners/test-local-plugin-check"
PASS=0
FAIL=0
TEST_FILTER=${1:-}

run_test() {
  if test -n "${TEST_PATTERN:-}" && ! printf '%s\n' "$1" | grep -Eiq "$TEST_PATTERN"; then
    return 0
  fi
  local name=$1
  shift
  if "$@"; then
    PASS=$((PASS + 1))
    printf 'ok - %s\n' "$name"
  else
    FAIL=$((FAIL + 1))
    printf 'not ok - %s\n' "$name"
  fi
}

assert_contains() {
  case $1 in *"$2"*) return 0;; *) return 1;; esac
}

run_if_selected() {
  local group=$1
  local name=$2
  local function=$3
  if test -z "$TEST_FILTER" || test "$TEST_FILTER" = "$group"; then
    run_test "$name" "$function"
  fi
}

make_clean_fixture() {
  local fixture=$1
  local repo="$fixture/repo"
  mkdir -p "$repo"
  ( cd "$ROOT" && tar --exclude=.git -cf - . ) | ( cd "$repo" && tar -xf - ) || return 1
  rm -rf "$repo/tests/compat" || return 1
  mkdir -p "$repo/tests/compat" || return 1
  printf '%s\n' '<?php' 'exit( 0 );' > "$repo/tests/compat/portable.php" || return 1
  git -C "$repo" init -q -b master || return 1
  git -C "$repo" config user.email 'release-test@example.test' || return 1
  git -C "$repo" config user.name 'Release Test' || return 1
  git -C "$repo" config core.autocrlf false || return 1
  git -C "$repo" add -A || return 1
  git -C "$repo" commit -qm 'Fixture source' || return 1
  git -C "$repo" remote add grand-media 'https://example.test/grand-media.git' || return 1
  git -C "$repo" config branch.master.remote grand-media || return 1
  git -C "$repo" config branch.master.merge refs/heads/master || return 1
  git -C "$repo" update-ref refs/remotes/grand-media/master HEAD
}

test_metadata_reports_1_25_1() {
  local output
  output=$("$CLI" metadata --repo "$ROOT") || return 1
  assert_contains "$output" 'version=1.25.1' || return 1
  assert_contains "$output" 'runtime_version=1.25.1' || return 1
  assert_contains "$output" 'stable_tag=1.25.1'
}

test_preflight_rejects_version_mismatch() {
  local fixture output
  fixture=$(mktemp -d)
  cp "$ROOT/grand-media.php" "$ROOT/readme.txt" "$fixture/"
  sed 's/Stable tag: 1.25.1/Stable tag: 9.9.9/' "$fixture/readme.txt" > "$fixture/readme.next"
  mv "$fixture/readme.next" "$fixture/readme.txt"
  output=$(GRAND_MEDIA_TEST_SKIP_GIT=1 "$CLI" preflight --repo "$fixture" --version 1.25.1 2>&1) && return 1
  assert_contains "$output" 'Stable tag 9.9.9 does not match 1.25.1'
}

test_bypass_does_not_skip_git_worktree_checks() {
  local fixture fake_bin output rc
  fixture=$(mktemp -d)
  cp "$ROOT/grand-media.php" "$ROOT/readme.txt" "$fixture/"
  printf 'gitdir: %s/.git\n' "$ROOT" > "$fixture/.git"
  fake_bin=$(mktemp -d)
  printf '#!/usr/bin/env bash\ncase "$*" in *" branch --show-current"*) printf "feature/test-bypass\\n"; exit 0;; esac\nexec /usr/bin/git "$@"\n' > "$fake_bin/git"
  chmod +x "$fake_bin/git"
  output=$(PATH="$fake_bin:$PATH" GRAND_MEDIA_TEST_SKIP_GIT=1 \
    "$CLI" preflight --repo "$fixture" --version 1.25.1 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Current branch feature/test-bypass is not master'
}

test_preflight_rejects_git_status_error() {
  local fixture fake_bin output rc
  fixture=$(mktemp -d)
  cp "$ROOT/grand-media.php" "$ROOT/readme.txt" "$fixture/"
  printf 'gitdir: %s/.git\n' "$ROOT" > "$fixture/.git"
  fake_bin=$(mktemp -d)
  printf '#!/usr/bin/env bash\ncase "$*" in *" branch --show-current"*) printf "master\\n"; exit 0;; *" status --porcelain"*) exit 1;; esac\nexec /usr/bin/git "$@"\n' > "$fake_bin/git"
  chmod +x "$fake_bin/git"
  output=$(PATH="$fake_bin:$PATH" "$CLI" preflight --repo "$fixture" --version 1.25.1 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Unable to determine working-tree status'
}

test_preflight_rejects_configured_upstream_mismatch() {
  local fixture repo upstream_sha output rc
  fixture=$(mktemp -d)
  make_clean_fixture "$fixture" || return 1
  repo="$fixture/repo"
  upstream_sha=$(git -C "$repo" commit-tree "$(git -C "$repo" write-tree)" -p "$(git -C "$repo" rev-parse HEAD)" -m 'Upstream mismatch') || return 1
  git -C "$repo" update-ref refs/remotes/grand-media/master "$upstream_sha" || return 1
  output=$("$CLI" preflight --repo "$repo" --version 1.25.1 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'HEAD does not match configured upstream'
}

test_build_creates_verified_source_archive_and_manifest() {
  local fixture repo work output archive manifest
  fixture=$(mktemp -d)
  make_clean_fixture "$fixture" || return 1
  repo="$fixture/repo"
  work="$fixture/work"
  archive="$work/grand-media-1.25.1-source.zip"
  manifest="$work/release-manifest.json"
  output=$(PATH="$ROOT/tests/release/fakes:$PATH" GRAND_MEDIA_WP_CLI=wp-dev "$CLI" build --repo "$repo" --version 1.25.1 --work-dir "$work") || return 1
  work=$(cd "$work" && pwd -P) || return 1
  archive="$work/grand-media-1.25.1-source.zip"
  manifest="$work/release-manifest.json"
  test -f "$archive" || return 1
  test -f "$manifest" || return 1
  jq -e --arg archive "$archive" '
    .version == "1.25.1"
    and (.git_sha | test("^[0-9a-f]{40}$"))
    and (.timestamp | test("^[0-9]{4}-[0-9]{2}-[0-9]{2}T"))
    and .source_zip == $archive
    and (.source_zip_bytes | type == "number" and . > 0)
    and (.source_zip_sha256 | test("^[0-9a-f]{64}$"))
    and .source_git.branch == "master"
    and .source_git.clean == true
    and .source_git.upstream == "grand-media/master"
    and .source_git.matches_upstream == true
    and .stages.build == "passed"
  ' "$manifest" >/dev/null || return 1
  unzip -Z1 "$archive" | grep -qx 'grand-media/' || return 1
  ! unzip -Z1 "$archive" | grep -q '^grand-media/bin/' || return 1
  ! unzip -Z1 "$archive" | grep -q '^grand-media/tests/' || return 1
  ! unzip -Z1 "$archive" | grep -q '^grand-media/\.superpowers/' || return 1
  assert_contains "$output" "$manifest" || return 1
  assert_contains "$output" "$archive"
}

test_build_archives_tracked_modules_without_ignored_local_modules() {
  local fixture repo work archive
  fixture=$(mktemp -d)
  make_clean_fixture "$fixture" || return 1
  repo="$fixture/repo"
  work="$fixture/work"
  archive="$work/grand-media-1.25.1-source.zip"
  mkdir -p "$repo/module/release-fixture-runtime" "$repo/module/albums-stripes" || return 1
  printf 'tracked runtime module\n' > "$repo/module/release-fixture-runtime/runtime.txt" || return 1
  git -C "$repo" add module/release-fixture-runtime/runtime.txt || return 1
  git -C "$repo" commit -qm 'Add tracked runtime module fixture' || return 1
  git -C "$repo" update-ref refs/remotes/grand-media/master HEAD || return 1
  printf 'ignored local module\n' > "$repo/module/albums-stripes/release-local-only.txt" || return 1
  printf 'ignored local module zip\n' > "$repo/module/albums-stripes.zip" || return 1
  git -C "$repo" check-ignore -q module/albums-stripes/release-local-only.txt || return 1
  git -C "$repo" check-ignore -q module/albums-stripes.zip || return 1
  test -z "$(git -C "$repo" status --porcelain)" || return 1

  PATH="$ROOT/tests/release/fakes:$PATH" GRAND_MEDIA_WP_CLI=wp-dev \
    "$CLI" build --repo "$repo" --version 1.25.1 --work-dir "$work" >/dev/null || return 1

  unzip -Z1 "$archive" | grep -qx 'grand-media/module/release-fixture-runtime/runtime.txt' || return 1
  ! unzip -Z1 "$archive" | grep -q '^grand-media/module/albums-stripes/' || return 1
  ! unzip -Z1 "$archive" | grep -qx 'grand-media/module/albums-stripes.zip'
}

test_build_passes_grand_media_source_directory_to_dist_archive() {
  local fixture repo work archive source_log
  fixture=$(mktemp -d)
  make_clean_fixture "$fixture" || return 1
  repo="$fixture/repo"
  work="$fixture/work"
  archive="$work/grand-media-1.25.1-source.zip"
  source_log="$fixture/dist-archive-source-basename.log"

  FAKE_WP_EXPECT_SOURCE_BASENAME=grand-media \
    FAKE_WP_SOURCE_BASENAME_LOG="$source_log" \
    PATH="$ROOT/tests/release/fakes:$PATH" GRAND_MEDIA_WP_CLI=wp-dev \
    "$CLI" build --repo "$repo" --version 1.25.1 --work-dir "$work" >/dev/null || return 1

  grep -Fx 'grand-media' "$source_log" >/dev/null || return 1
  unzip -Z1 "$archive" | grep -qx 'grand-media/' || return 1
  ! unzip -Z1 "$archive" | grep -q '^grand-media-source-export\.'
}

run_create_tracked_export_with_fake_mktemp() (
  local work=$1
  local returned_path=$2
  local fake_bin=$3
  export PATH="$fake_bin:$PATH"
  export FAKE_MKTEMP_RETURN="$returned_path"
  source "$ROOT/bin/release/lib.sh"
  create_tracked_source_export "$work"
)

test_tracked_export_rejects_hostile_mktemp_and_preexisting_markers() {
  local fixture work fake_bin container marker target output rc mode
  for mode in marker-file marker-symlink outside-container; do
    fixture=$(mktemp -d)
    work="$fixture/work"
    fake_bin="$fixture/fake-bin"
    mkdir -p "$work" "$fake_bin" || return 1
    printf '%s\n' '#!/usr/bin/env bash' 'printf "%s\n" "$FAKE_MKTEMP_RETURN"' > "$fake_bin/mktemp" || return 1
    chmod +x "$fake_bin/mktemp" || return 1
    if test "$mode" = outside-container; then
      container="$fixture/grand-media-source-export.hostile"
      mkdir "$container" || return 1
    else
      container="$work/grand-media-source-export.hostile"
      marker="$container/.grand-media-source-export"
      mkdir "$container" || return 1
      if test "$mode" = marker-file; then
        printf 'preserve marker\n' > "$marker" || return 1
      else
        target="$fixture/preserve-target"
        printf 'preserve target\n' > "$target" || return 1
        ln -s "$target" "$marker" || return 1
      fi
    fi

    output=$(run_create_tracked_export_with_fake_mktemp "$work" "$container" "$fake_bin" 2>&1)
    rc=$?
    test "$rc" -ne 0 || return 1
    test -d "$container" || return 1
    case $mode in
      marker-file) test "$(cat "$marker")" = 'preserve marker' || return 1 ;;
      marker-symlink) test -L "$marker" && test "$(cat "$target")" = 'preserve target' || return 1 ;;
      outside-container) test -z "$(find "$container" -mindepth 1 -print -quit)" || return 1 ;;
    esac
  done
}

test_build_rejects_missing_distignore() {
  local fixture repo work manifest output rc
  fixture=$(mktemp -d)
  make_clean_fixture "$fixture" || return 1
  repo="$fixture/repo"
  work="$fixture/work"
  manifest="$work/release-manifest.json"
  rm "$repo/.distignore" || return 1
  git -C "$repo" add -u || return 1
  git -C "$repo" commit -qm 'Remove distribution exclusions' || return 1
  git -C "$repo" update-ref refs/remotes/grand-media/master HEAD || return 1
  output=$(PATH="$ROOT/tests/release/fakes:$PATH" GRAND_MEDIA_WP_CLI=wp-dev "$CLI" build --repo "$repo" --version 1.25.1 --work-dir "$work" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Source .distignore does not exist' || return 1
  ! jq -e '.stages.build == "passed"' "$manifest" >/dev/null
}

test_build_rejects_invalid_source_archive_root() {
  local fixture repo work manifest output rc
  fixture=$(mktemp -d)
  make_clean_fixture "$fixture" || return 1
  repo="$fixture/repo"
  work="$fixture/work"
  manifest="$work/release-manifest.json"
  output=$(FAKE_WP_DIST_ROOT=wrong-root PATH="$ROOT/tests/release/fakes:$PATH" \
    GRAND_MEDIA_WP_CLI=wp-dev "$CLI" build --repo "$repo" --version 1.25.1 --work-dir "$work" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Source archive must contain exactly one grand-media/ top-level directory' || return 1
  ! jq -e '.stages.build == "passed"' "$manifest" >/dev/null
}

test_build_rejects_excluded_development_files() {
  local fixture repo work manifest output rc
  fixture=$(mktemp -d)
  make_clean_fixture "$fixture" || return 1
  repo="$fixture/repo"
  work="$fixture/work"
  manifest="$work/release-manifest.json"
  output=$(FAKE_WP_SKIP_DISTIGNORE=1 PATH="$ROOT/tests/release/fakes:$PATH" \
    GRAND_MEDIA_WP_CLI=wp-dev "$CLI" build --repo "$repo" --version 1.25.1 --work-dir "$work" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Source archive contains excluded development files' || return 1
  ! jq -e '.stages.build == "passed"' "$manifest" >/dev/null
}

test_build_propagates_source_gate_failure() {
  local fixture repo work manifest output rc
  fixture=$(mktemp -d)
  make_clean_fixture "$fixture" || return 1
  repo="$fixture/repo"
  work="$fixture/work"
  manifest="$work/release-manifest.json"
  printf '%s\n' '<?php' 'fwrite( STDERR, "fixture compat failure\\n" );' 'exit( 1 );' > "$repo/tests/compat/portable.php" || return 1
  git -C "$repo" add tests/compat/portable.php || return 1
  git -C "$repo" commit -qm 'Fail portable source gate' || return 1
  git -C "$repo" update-ref refs/remotes/grand-media/master HEAD || return 1
  output=$(PATH="$ROOT/tests/release/fakes:$PATH" GRAND_MEDIA_WP_CLI=wp-dev "$CLI" build --repo "$repo" --version 1.25.1 --work-dir "$work" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'fixture compat failure' || return 1
  ! jq -e '.stages.build == "passed"' "$manifest" >/dev/null
}

test_build_rejects_non_empty_work_dir_without_matching_resume_manifest() {
  local fixture repo work output rc
  fixture=$(mktemp -d)
  make_clean_fixture "$fixture" || return 1
  repo="$fixture/repo"
  work="$fixture/work"
  mkdir -p "$work"
  touch "$work/unrelated-file"
  output=$(PATH="$ROOT/tests/release/fakes:$PATH" GRAND_MEDIA_WP_CLI=wp-dev "$CLI" build --repo "$repo" --version 1.25.1 --work-dir "$work" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Work directory is not empty; use --resume'
}

test_build_rejects_resume_manifest_with_wrong_source_identity() {
  local fixture repo work manifest output rc
  fixture=$(mktemp -d)
  make_clean_fixture "$fixture" || return 1
  repo="$fixture/repo"
  work="$fixture/work"
  mkdir -p "$work"
  manifest="$work/release-manifest.json"
  jq -n '{ version: "9.9.9", git_sha: "wrong" }' > "$manifest"
  output=$(PATH="$ROOT/tests/release/fakes:$PATH" GRAND_MEDIA_WP_CLI=wp-dev "$CLI" build --repo "$repo" --version 1.25.1 --work-dir "$work" --resume "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Resume manifest does not match current source'
}

test_build_rejects_resume_manifest_outside_work_dir() {
  local fixture repo work external output rc
  fixture=$(mktemp -d)
  make_clean_fixture "$fixture" || return 1
  repo="$fixture/repo"
  work="$fixture/work"
  external="$fixture/external-release-manifest.json"
  mkdir -p "$work"
  printf 'occupied\n' > "$work/keep"
  jq -n --arg git_sha "$(git -C "$repo" rev-parse HEAD)" \
    '{version: "1.25.1", git_sha: $git_sha}' > "$external"
  output=$(PATH="$ROOT/tests/release/fakes:$PATH" GRAND_MEDIA_WP_CLI=wp-dev \
    "$CLI" build --repo "$repo" --version 1.25.1 --work-dir "$work" --resume "$external" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Resume manifest must be <work-dir>/release-manifest.json'
}

test_build_rejects_external_resume_with_empty_explicit_work_dir() {
  local fixture repo work external output rc
  fixture=$(mktemp -d)
  make_clean_fixture "$fixture" || return 1
  repo="$fixture/repo"
  work="$fixture/work"
  external="$fixture/external-release-manifest.json"
  mkdir -p "$work"
  jq -n --arg git_sha "$(git -C "$repo" rev-parse HEAD)" \
    '{version: "1.25.1", git_sha: $git_sha}' > "$external"
  output=$(PATH="$ROOT/tests/release/fakes:$PATH" GRAND_MEDIA_WP_CLI=wp-dev \
    "$CLI" build --repo "$repo" --version 1.25.1 --work-dir "$work" --resume "$external" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Resume manifest must be <work-dir>/release-manifest.json' || return 1
  ! test -e "$work/release-manifest.json" || return 1
  ! test -e "$work/grand-media-1.25.1-source.zip"
}

test_build_rejects_resume_without_work_dir() {
  local fixture repo external before output rc
  fixture=$(mktemp -d)
  make_clean_fixture "$fixture" || return 1
  repo="$fixture/repo"
  external="$fixture/external-release-manifest.json"
  jq -n --arg git_sha "$(git -C "$repo" rev-parse HEAD)" \
    '{version: "1.25.1", git_sha: $git_sha}' > "$external"
  before=$(shasum -a 256 "$external" | awk '{print $1}') || return 1
  output=$(PATH="$ROOT/tests/release/fakes:$PATH" GRAND_MEDIA_WP_CLI=wp-dev \
    "$CLI" build --repo "$repo" --version 1.25.1 --resume "$external" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" '--resume requires matching --work-dir' || return 1
  test "$before" = "$(shasum -a 256 "$external" | awk '{print $1}')" || return 1
  ! find "$fixture" -name 'grand-media-1.25.1-source.zip' -print -quit | grep -q .
}

test_build_accepts_matching_resume_in_nonempty_work_dir() {
  local fixture repo work manifest git_sha
  fixture=$(mktemp -d)
  make_clean_fixture "$fixture" || return 1
  repo="$fixture/repo"
  work="$fixture/work"
  manifest="$work/release-manifest.json"
  mkdir -p "$work"
  git_sha=$(git -C "$repo" rev-parse HEAD) || return 1
  jq -n --arg git_sha "$git_sha" '{version: "1.25.1", git_sha: $git_sha, stages: {}}' > "$manifest"
  PATH="$ROOT/tests/release/fakes:$PATH" GRAND_MEDIA_WP_CLI=wp-dev \
    "$CLI" build --repo "$repo" --version 1.25.1 --work-dir "$work" --resume "$manifest" >/dev/null || return 1
  test -f "$work/grand-media-1.25.1-source.zip" || return 1
  jq -e '.stages.build == "passed"' "$manifest" >/dev/null
}

test_build_rejects_work_dir_inside_source_repo() {
  local fixture repo work output rc fake_bin
  fixture=$(mktemp -d)
  make_clean_fixture "$fixture" || return 1
  repo="$fixture/repo"

  for work in "$repo/release-work" "$fixture/linked-work"; do
    if test "$work" = "$fixture/linked-work"; then
      mkdir -p "$repo/.git/real-release-work"
      ln -s "$repo/.git/real-release-work" "$work"
    fi
    output=$(PATH="$ROOT/tests/release/fakes:$PATH" GRAND_MEDIA_WP_CLI=wp-dev \
      "$CLI" build --repo "$repo" --version 1.25.1 --work-dir "$work" 2>&1)
    rc=$?
    test "$rc" -ne 0 || return 1
    assert_contains "$output" 'Work directory must be outside the source repository' || return 1
  done

  mkdir -p "$repo/.git/hostile-tmp"
  output=$(PATH="$ROOT/tests/release/fakes:$PATH" GRAND_MEDIA_WP_CLI=wp-dev TMPDIR="$repo/.git/hostile-tmp" \
    "$CLI" build --repo "$repo" --version 1.25.1 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Work directory must be outside the source repository' || return 1

  fake_bin="$fixture/fake-bin"
  mkdir -p "$fake_bin"
  printf '%s\n' '#!/usr/bin/env bash' 'mkdir -p "$FAKE_MKTEMP_WORK"' 'printf "%s\\n" "$FAKE_MKTEMP_WORK"' > "$fake_bin/mktemp"
  printf '%s\n' '#!/usr/bin/env bash' 'printf "wp-dev must not run\\n" >&2' 'exit 99' > "$fake_bin/wp-dev"
  chmod +x "$fake_bin/mktemp" "$fake_bin/wp-dev"
  output=$(PATH="$fake_bin:$ROOT/tests/release/fakes:$PATH" GRAND_MEDIA_WP_CLI=wp-dev \
    FAKE_MKTEMP_WORK="$repo/release-work" \
    "$CLI" build --repo "$repo" --version 1.25.1 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Work directory must be outside the source repository' || return 1
  ! assert_contains "$output" 'wp-dev must not run' || return 1

  ! find "$repo" -name 'grand-media-1.25.1-source.zip' -print -quit | grep -q .
}

test_build_rejects_resume_after_build_completed() {
  local fixture repo work manifest output rc
  fixture=$(mktemp -d)
  make_clean_fixture "$fixture" || return 1
  repo="$fixture/repo"
  work="$fixture/work"
  manifest="$work/release-manifest.json"
  PATH="$ROOT/tests/release/fakes:$PATH" GRAND_MEDIA_WP_CLI=wp-dev \
    "$CLI" build --repo "$repo" --version 1.25.1 --work-dir "$work" >/dev/null || return 1
  output=$(PATH="$ROOT/tests/release/fakes:$PATH" GRAND_MEDIA_WP_CLI=wp-dev \
    "$CLI" build --repo "$repo" --version 1.25.1 --work-dir "$work" --resume "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Resume manifest already contains completed build evidence'
}

make_freemius_fixture() {
  local fixture=$1
  local manifest=$fixture/release-manifest.json
  local source_size source_sha
  mkdir -p "$fixture"
  printf '\120\113\003\004source' > "$fixture/source.zip"
  printf '\120\113\003\004free' > "$fixture/free.zip"
  source_size=$(wc -c < "$fixture/source.zip" | tr -d '[:space:]') || return 1
  source_sha=$(shasum -a 256 "$fixture/source.zip" | awk '{ print $1 }') || return 1
  jq -n --arg source_zip "$fixture/source.zip" --arg source_sha "$source_sha" --argjson source_size "$source_size" \
    '{
      version: "1.25.1",
      git_sha: "0123456789abcdef0123456789abcdef01234567",
      source_zip: $source_zip,
      source_zip_bytes: $source_size,
      source_zip_sha256: $source_sha,
      stages: { build: "passed" }
    }' > "$manifest"
}

freemius_test_token() {
  printf 'test-token-%s' "${1##*/}"
}

run_freemius() {
  local fixture=$1
  shift
  (
    export PATH="$ROOT/tests/release/fakes:$PATH"
    export FAKE_CURL_LOG="$fixture/curl.log"
    export FAKE_FREE_ZIP="$fixture/free.zip"
    export FAKE_CURL_MODE="${FAKE_CURL_MODE:-upload-ok}"
    export FAKE_CURL_PRODUCT_ID="${FAKE_CURL_PRODUCT_ID:-20980}"
    export FAKE_CURL_DEPLOYMENT_ID="${FAKE_CURL_DEPLOYMENT_ID:-9001}"
    export FAKE_CURL_RELEASE_MODE="${FAKE_CURL_RELEASE_MODE:-pending}"
    export FAKE_CURL_UPLOAD_MARKER="$fixture/freemius-upload.marker"
    export FAKE_CURL_UPLOAD_COUNT="$fixture/freemius-upload.count"
    export FAKE_MV_COUNT="${FAKE_MV_COUNT:-}"
    export FAKE_RM_COUNT="${FAKE_RM_COUNT:-}"
    if test -n "${TMPDIR:-}"; then
      export TMPDIR
    fi
    FREEMIUS_API_TOKEN=${FREEMIUS_TEST_TOKEN:-$(freemius_test_token "$fixture")}
    export FREEMIUS_API_TOKEN
    export FAKE_CURL_SENTINEL="$FREEMIUS_API_TOKEN"
    source "$ROOT/bin/release/lib.sh"
    "$@"
  )
}

test_freemius_uploads_pending_and_downloads_explicit_free_zip() {
  local fixture manifest download token
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  download="$fixture/free-download.zip"
  token=$(freemius_test_token "$fixture")
  run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" || return 1
  FAKE_CURL_MODE=download-free run_freemius "$fixture" download_free 9001 "$download" "$manifest" || return 1
  jq -e '
    .freemius.product_id == 20980 and
    .freemius.deployment_id == "9001" and
    .freemius.release_mode == "pending" and
    .freemius.upload_release_mode == "pending" and
    .freemius.upload_endpoint_class == "POST /v1/products/20980/tags.json" and
    .free_zip.path == $free_zip and
    .free_zip.download_endpoint_class == "GET /v1/products/20980/tags/{deployment_id}.zip?is_premium=false" and
    (.free_zip.bytes | type == "number" and . > 0) and
    (.free_zip.sha256 | test("^[0-9a-f]{64}$")) and
    .stages.upload_pending == "passed" and
    .stages.download_free == "passed"
  ' --arg free_zip "$download" "$manifest" >/dev/null || return 1
  grep -Fx 'POST /v1/products/20980/tags.json' "$fixture/curl.log" >/dev/null || return 1
  grep -Fx 'GET /v1/products/20980/tags/9001.zip?is_premium=false' "$fixture/curl.log" >/dev/null || return 1
  ! grep -F "$token" "$manifest" "$fixture/curl.log" || return 1
  test "$(dd if="$download" bs=1 count=2 2>/dev/null)" = 'PK'
}

test_freemius_rejects_absent_token() {
  local fixture manifest output rc
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  output=$( (
    export PATH="$ROOT/tests/release/fakes:$PATH"
    export FAKE_CURL_LOG="$fixture/curl.log"
    source "$ROOT/bin/release/lib.sh"
    unset FREEMIUS_API_TOKEN
    upload_pending "$fixture/source.zip" "$manifest"
  ) 2>&1 )
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'FREEMIUS_API_TOKEN is required'
}

test_freemius_rejects_http_failure() {
  local fixture manifest output rc
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  output=$(FAKE_CURL_MODE=http-error run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Freemius upload failed'
}

test_freemius_rejects_malformed_upload_json() {
  local fixture manifest output rc
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  output=$(FAKE_CURL_MODE=bad-json run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Freemius upload response is not valid JSON'
}

test_freemius_rejects_upload_product_mismatch() {
  local fixture manifest output rc
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  output=$(FAKE_CURL_PRODUCT_ID=9999 run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Freemius response product must be string 20980'
}

test_freemius_rejects_numeric_upload_product_id_as_ambiguous() {
  local fixture manifest output rc
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  output=$(FAKE_CURL_MODE=upload-numeric-product-id run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Freemius response product must be string 20980' || return 1
  assert_contains "$output" 'A pending Freemius deployment may exist; do not retry automatically' || return 1
  test -e "$fixture/freemius-upload.marker" || return 1
  jq -e '.freemius_upload_attempt.source_zip_sha256 == .source_zip_sha256' "$manifest" >/dev/null || return 1
  ! jq -e '.freemius' "$manifest" >/dev/null || return 1
  ! jq -e '.stages.upload_pending == "passed"' "$manifest" >/dev/null
}

test_freemius_rejects_upload_version_mismatch() {
  local fixture manifest output rc
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  jq '.version = "9.9.9"' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" || return 1
  output=$(run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Freemius response version does not match manifest'
}

test_freemius_rejects_non_pending_release_mode() {
  local fixture manifest output rc
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  output=$(FAKE_CURL_RELEASE_MODE=released run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Freemius response release mode is not pending'
}

test_freemius_rejects_download_without_zip_magic() {
  local fixture manifest download output rc
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  download="$fixture/free-download.zip"
  run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" || return 1
  output=$(FAKE_CURL_MODE=premium-response run_freemius "$fixture" download_free 9001 "$download" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Downloaded free ZIP does not have ZIP magic' || return 1
  ! test -e "$download"
}

test_freemius_rejects_malicious_upload_deployment_id() {
  local fixture manifest output rc
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  output=$(FAKE_CURL_DEPLOYMENT_ID='9001?is_premium=true' run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Freemius response deployment ID is invalid'
  ! jq -e '.freemius' "$manifest" >/dev/null
}

test_freemius_rejects_malicious_download_deployment_id() {
  local fixture manifest download output rc
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  download="$fixture/free-download.zip"
  jq '.freemius = { product_id: 20980, deployment_id: "9001#premium", release_mode: "pending" }' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" || return 1
  output=$(FAKE_CURL_MODE=download-free run_freemius "$fixture" download_free '9001#premium' "$download" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Freemius deployment ID is invalid' || return 1
  ! test -e "$download"
}

test_freemius_cleans_partial_on_size_hash_and_move_failure() {
  local fixture manifest download fake_bin output rc command
  for command in wc shasum mv; do
    fixture=$(mktemp -d)
    make_freemius_fixture "$fixture" || return 1
    manifest="$fixture/release-manifest.json"
    download="$fixture/free-download.zip"
    fake_bin="$fixture/fail-bin"
    mkdir -p "$fake_bin"
    printf '#!/usr/bin/env bash\nexit 1\n' > "$fake_bin/$command"
    chmod +x "$fake_bin/$command"
    run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" || return 1
    output=$(PATH="$fake_bin:$PATH" FAKE_CURL_MODE=download-free run_freemius "$fixture" download_free 9001 "$download" "$manifest" 2>&1)
    rc=$?
    test "$rc" -ne 0 || return 1
    ! test -e "$download" || return 1
    ! test -e "$download.partial" || return 1
    ! jq -e '.stages.download_free == "passed"' "$manifest" >/dev/null || return 1
  done
}

test_freemius_cleans_manifest_temp_on_manifest_move_failure() {
  local fixture manifest fake_bin output rc
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  fake_bin="$fixture/fail-bin"
  mkdir -p "$fake_bin"
  printf '#!/usr/bin/env bash\nexit 1\n' > "$fake_bin/mv"
  chmod +x "$fake_bin/mv"
  output=$(PATH="$fake_bin:$PATH" run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  ! find "$fixture" -name 'release-manifest.json.tmp.*' -print -quit | grep -q .
}

test_freemius_cleans_download_final_when_manifest_move_fails() {
  local fixture manifest download fake_bin output rc
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  download="$fixture/free-download.zip"
  fake_bin="$fixture/fail-bin"
  mkdir -p "$fake_bin"
  printf '%s' '#!/usr/bin/env bash
count=0
test -f "$FAKE_MV_COUNT" && count=$(cat "$FAKE_MV_COUNT")
count=$((count + 1))
printf "%s" "$count" > "$FAKE_MV_COUNT"
test "$count" -eq 2 && exit 1
exec /bin/mv "$@"
' > "$fake_bin/mv"
  chmod +x "$fake_bin/mv"
  run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" || return 1
  output=$(PATH="$fake_bin:$PATH" FAKE_MV_COUNT="$fixture/mv-count" FAKE_CURL_MODE=download-free run_freemius "$fixture" download_free 9001 "$download" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  ! test -e "$download" || return 1
  ! test -e "$download.partial" || return 1
  ! jq -e '.stages.download_free == "passed"' "$manifest" >/dev/null
}

test_freemius_refuses_preexisting_final_download_path() {
  local fixture manifest download output rc
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  download="$fixture/free-download.zip"
  printf 'existing' > "$download"
  run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" || return 1
  output=$(FAKE_CURL_MODE=download-free run_freemius "$fixture" download_free 9001 "$download" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Free ZIP destination already exists' || return 1
  test "$(cat "$download")" = existing || return 1
  ! test -e "$download.partial"
}

test_freemius_keeps_response_cleanup_trap_until_removal_succeeds() {
  local fixture manifest fake_bin output rc
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  fake_bin="$fixture/fail-bin"
  mkdir -p "$fake_bin" "$fixture/tmp"
  printf '%s' '#!/usr/bin/env bash
count=0
test -f "$FAKE_RM_COUNT" && count=$(cat "$FAKE_RM_COUNT")
count=$((count + 1))
printf "%s" "$count" > "$FAKE_RM_COUNT"
test "$count" -eq 1 && exit 1
exec /bin/rm "$@"
' > "$fake_bin/rm"
  chmod +x "$fake_bin/rm"
  output=$(PATH="$fake_bin:$PATH" TMPDIR="$fixture/tmp" FAKE_RM_COUNT="$fixture/rm-count" run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  ! find "$fixture/tmp" -type f -print -quit | grep -q . || return 1
  ! jq -e '.stages.upload_pending == "passed"' "$manifest" >/dev/null
}

test_freemius_escapes_token_and_disables_hostile_curlrc() {
  local fixture manifest home trace token
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  home="$fixture/curl-home"
  trace="$fixture/hostile-trace.log"
  token='quoted"token\with-backslash'
  mkdir -p "$home"
  printf 'trace-ascii = "%s"\nverbose\n' "$trace" > "$home/.curlrc"
  CURL_HOME="$home" FREEMIUS_TEST_TOKEN="$token" run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" || return 1
  ! test -e "$trace" || return 1
  ! grep -F "$token" "$manifest" "$fixture/curl.log"
}

test_freemius_rejects_token_with_cr_or_lf() {
  local fixture manifest output rc token
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  token=$'bad-token\r\nheader = "injected"'
  output=$(FREEMIUS_TEST_TOKEN="$token" run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'FREEMIUS_API_TOKEN must not contain CR or LF' || return 1
  ! test -e "$fixture/curl.log"
}

test_freemius_upload_attempt_is_one_shot() {
  local fixture manifest output rc before
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" || return 1
  test "$(cat "$fixture/freemius-upload.count")" = 1 || return 1
  jq -e '
    .freemius_upload_attempt.source_zip_sha256 == .source_zip_sha256 and
    .freemius.source_zip_sha256 == .source_zip_sha256 and
    .stages.upload_pending == "passed"
  ' "$manifest" >/dev/null || return 1
  before=$(shasum -a 256 "$manifest" | awk '{print $1}') || return 1
  output=$(run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Freemius upload was already attempted; do not retry automatically' || return 1
  test "$(cat "$fixture/freemius-upload.count")" = 1 || return 1
  test "$before" = "$(shasum -a 256 "$manifest" | awk '{print $1}')" || return 1
  rm "$fixture/source.zip" || return 1
  output=$(run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Freemius upload was already attempted; do not retry automatically' || return 1
  test "$(cat "$fixture/freemius-upload.count")" = 1
}

test_freemius_upload_ambiguity_preserves_attempt_and_blocks_retry() {
  local fixture manifest mode output retry_output rc fake_bin
  for mode in http-error bad-json product-mismatch response-cleanup final-manifest; do
    fixture=$(mktemp -d)
    make_freemius_fixture "$fixture" || return 1
    manifest="$fixture/release-manifest.json"
    jq '.free_zip = {stale: true} | .verification = {stale: true} | .svn = {stale: true} |
        .stages.download_free = "passed" | .stages.verify = "passed" |
        .stages.svn_prepare = "passed" | .stages.svn_publish = "passed" |
        .stages.freemius_release = "passed"' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" || return 1
    fake_bin=''
    case $mode in
      http-error) output=$(FAKE_CURL_MODE=http-error run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1) ;;
      bad-json) output=$(FAKE_CURL_MODE=bad-json run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1) ;;
      product-mismatch) output=$(FAKE_CURL_PRODUCT_ID=9999 run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1) ;;
      response-cleanup)
        fake_bin="$fixture/fail-bin"
        mkdir -p "$fake_bin" "$fixture/tmp"
        printf '%s' '#!/usr/bin/env bash
count=0
test -f "$FAKE_RM_COUNT" && count=$(cat "$FAKE_RM_COUNT")
count=$((count + 1))
printf "%s" "$count" > "$FAKE_RM_COUNT"
test "$count" -eq 1 && exit 1
exec /bin/rm "$@"
' > "$fake_bin/rm"
        chmod +x "$fake_bin/rm"
        output=$(PATH="$fake_bin:$PATH" TMPDIR="$fixture/tmp" FAKE_RM_COUNT="$fixture/rm-count" run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1)
        ;;
      final-manifest)
        fake_bin="$fixture/fail-bin"
        mkdir -p "$fake_bin"
        printf '%s' '#!/usr/bin/env bash
count=0
test -f "$FAKE_MV_COUNT" && count=$(cat "$FAKE_MV_COUNT")
count=$((count + 1))
printf "%s" "$count" > "$FAKE_MV_COUNT"
test "$count" -eq 2 && exit 1
exec /bin/mv "$@"
' > "$fake_bin/mv"
        chmod +x "$fake_bin/mv"
        output=$(PATH="$fake_bin:$PATH" FAKE_MV_COUNT="$fixture/mv-count" run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1)
        ;;
    esac
    rc=$?
    test "$rc" -ne 0 || return 1
    assert_contains "$output" 'A pending Freemius deployment may exist; do not retry automatically' || return 1
    test "$(cat "$fixture/freemius-upload.count")" = 1 || return 1
    jq -e '.freemius_upload_attempt.source_zip_sha256 == .source_zip_sha256' "$manifest" >/dev/null || return 1
    jq -e '
      (has("free_zip") | not) and (has("verification") | not) and (has("svn") | not) and
      (.stages | has("download_free") | not) and (.stages | has("verify") | not) and
      (.stages | has("svn_prepare") | not) and (.stages | has("svn_publish") | not) and
      (.stages | has("freemius_release") | not)
    ' "$manifest" >/dev/null || return 1
    retry_output=$(run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" 2>&1)
    rc=$?
    test "$rc" -ne 0 || return 1
    assert_contains "$retry_output" 'already attempted' || return 1
    test "$(cat "$fixture/freemius-upload.count")" = 1 || return 1
  done
}

test_freemius_download_is_one_shot_and_bound_to_upload() {
  local fixture manifest download output rc before
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  download="$fixture/free-download.zip"
  run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" || return 1
  FAKE_CURL_MODE=download-free run_freemius "$fixture" download_free 9001 "$download" "$manifest" || return 1
  jq -e '
    .free_zip.deployment_id == .freemius.deployment_id and
    .free_zip.source_zip_sha256 == .freemius.source_zip_sha256
  ' "$manifest" >/dev/null || return 1
  before=$(shasum -a 256 "$download" | awk '{print $1}') || return 1
  output=$(FAKE_CURL_MODE=download-free run_freemius "$fixture" download_free 9001 "$fixture/other.zip" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Freemius free download was already recorded' || return 1
  test "$(grep -Fc 'GET /v1/products/20980/tags/9001.zip?is_premium=false' "$fixture/curl.log")" = 1 || return 1
  test "$before" = "$(shasum -a 256 "$download" | awk '{print $1}')" || return 1

  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  run_freemius "$fixture" upload_pending "$fixture/source.zip" "$manifest" || return 1
  jq '.freemius.source_zip_sha256 = "ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff"' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" || return 1
  output=$(FAKE_CURL_MODE=download-free run_freemius "$fixture" download_free 9001 "$fixture/free-download.zip" "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  ! grep -F 'GET ' "$fixture/curl.log" >/dev/null
}

test_freemius_cli_uploads_and_downloads() {
  local fixture manifest destination output
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  destination="$(cd "$fixture" && pwd -P)/grand-media-1.25.1-free.zip"
  if ! output=$(run_freemius "$fixture" "$CLI" freemius-upload --manifest "$manifest" 2>&1); then
    printf '%s\n' "$output" >&2
    return 1
  fi
  if ! output=$(FAKE_CURL_MODE=download-free run_freemius "$fixture" "$CLI" freemius-download --manifest "$manifest" 2>&1); then
    printf '%s\n' "$output" >&2
    return 1
  fi
  test -f "$destination" || return 1
  jq -e '.stages.upload_pending == "passed" and .stages.download_free == "passed" and .free_zip.path == $destination' --arg destination "$destination" "$manifest" >/dev/null || return 1
  grep -Fx 'POST /v1/products/20980/tags.json' "$fixture/curl.log" >/dev/null || return 1
  grep -Fx 'GET /v1/products/20980/tags/9001.zip?is_premium=false' "$fixture/curl.log" >/dev/null
}

write_artifact_metadata() {
  local root=$1
  local version=${2:-1.25.1}
  mkdir -p "$root"
  printf '%s\n' '<?php' '/**' " * Version: $version" ' */' 'class Gmedia {' "	public \$version = '$version';" '}' > "$root/grand-media.php"
  printf 'Stable tag: %s\n' "$version" > "$root/readme.txt"
}

refresh_artifact_manifest() {
  local fixture=$1
  local manifest="$fixture/release-manifest.json"
  local source_zip="$fixture/grand-media-1.25.1-source.zip"
  local free_zip="$fixture/grand-media-1.25.1-free.zip"
  local source_size free_size source_sha free_sha
  source_size=$(wc -c < "$source_zip" | tr -d '[:space:]') || return 1
  free_size=$(wc -c < "$free_zip" | tr -d '[:space:]') || return 1
  source_sha=$(shasum -a 256 "$source_zip" | awk '{ print $1 }') || return 1
  free_sha=$(shasum -a 256 "$free_zip" | awk '{ print $1 }') || return 1
  jq -n \
    --arg source_zip "$source_zip" \
    --arg free_zip "$free_zip" \
    --arg source_sha "$source_sha" \
    --arg free_sha "$free_sha" \
    --argjson source_size "$source_size" \
    --argjson free_size "$free_size" \
    '{
      version: "1.25.1",
      git_sha: "0123456789abcdef0123456789abcdef01234567",
      source_zip: $source_zip,
      source_zip_bytes: $source_size,
      source_zip_sha256: $source_sha,
      freemius_upload_attempt: { source_zip_sha256: $source_sha, timestamp: "2026-09-01T00:00:00Z" },
      freemius: {
        product_id: 20980,
        deployment_id: "9001",
        release_mode: "pending",
        upload_release_mode: "pending",
        source_zip_sha256: $source_sha,
        upload_endpoint_class: "POST /v1/products/20980/tags.json"
      },
      free_zip: {
        path: $free_zip,
        bytes: $free_size,
        sha256: $free_sha,
        deployment_id: "9001",
        source_zip_sha256: $source_sha,
        download_endpoint_class: "GET /v1/products/20980/tags/{deployment_id}.zip?is_premium=false"
      },
      stages: { build: "passed", upload_pending: "passed", download_free: "passed" }
    }' > "$manifest"
}

rebuild_artifact_zips() {
  local fixture=$1
  rm -f "$fixture/grand-media-1.25.1-source.zip" "$fixture/grand-media-1.25.1-free.zip"
  ( cd "$fixture/source" && /usr/bin/zip -qr "$fixture/grand-media-1.25.1-source.zip" grand-media ) || return 1
  ( cd "$fixture/free" && /usr/bin/zip -qr "$fixture/grand-media-1.25.1-free.zip" grand-media ) || return 1
  refresh_artifact_manifest "$fixture"
}

make_artifact_fixture() {
  local fixture=$1
  mkdir -p "$fixture/source/grand-media" "$fixture/free/grand-media"
  write_artifact_metadata "$fixture/source/grand-media" || return 1
  write_artifact_metadata "$fixture/free/grand-media" || return 1
  printf 'removed by Freemius\n' > "$fixture/source/grand-media/source-only.txt"
  printf '%s\n' '#!/usr/bin/env bash' 'test "$#" -eq 1 || exit 91' 'test -f "$1/grand-media.php" || exit 92' 'printf "artifact Plugin Check passed\\n"' > "$fixture/plugin-check"
  chmod +x "$fixture/plugin-check"
  printf '%s\n' '#!/usr/bin/env bash' 'test "$#" -eq 1 || exit 93' 'test -f "$1/grand-media.php" || exit 94' 'printf "isolated install and activation passed\\n"' > "$fixture/activate-check"
  chmod +x "$fixture/activate-check"
  rebuild_artifact_zips "$fixture"
}

run_artifact_verify() {
  local fixture=$1
  shift
  GRAND_MEDIA_ARTIFACT_PLUGIN_CHECK_CMD="$fixture/plugin-check" \
    GRAND_MEDIA_ARTIFACT_ACTIVATION_CMD="$fixture/activate-check" \
    "$CLI" verify --resume "$fixture/release-manifest.json" "$@"
}

assert_verify_failed_without_passed_stage() {
  local fixture=$1
  local expected=$2
  shift 2
  local output rc
  output=$(run_artifact_verify "$fixture" "$@" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" "$expected" || return 1
  ! jq -e '.stages.verify == "passed"' "$fixture/release-manifest.json" >/dev/null
}

make_zip_with_named_entry() {
  local zip=$1
  local name=$2
  php -r '$zip = new ZipArchive(); if ($zip->open($argv[1], ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) { exit(1); } $zip->addFromString($argv[2], "unsafe"); $zip->close();' "$zip" "$name"
}

append_named_zip_entry() {
  local zip=$1
  local name=$2
  php -r '$zip = new ZipArchive(); if ($zip->open($argv[1]) !== true) { exit(1); } $zip->addFromString($argv[2], "second root"); $zip->close();' "$zip" "$name"
}

test_verify_accepts_exact_free_artifact_and_records_evidence() {
  local fixture manifest root summary log activation_log before after output
  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  root="$fixture/free-extracted/grand-media"
  summary="$fixture/freemius-transformations.txt"
  log="$fixture/artifact-plugin-check.log"
  activation_log="$fixture/artifact-activation.log"
  before=$(git -C "$ROOT" status --porcelain=v1 --untracked-files=all) || return 1
  output=$(run_artifact_verify "$fixture") || return 1
  after=$(git -C "$ROOT" status --porcelain=v1 --untracked-files=all) || return 1
  test "$before" = "$after" || return 1
  test -d "$root" || return 1
  test -f "$summary" || return 1
  test -f "$log" || return 1
  test -f "$activation_log" || return 1
  grep -F 'source-only.txt' "$summary" >/dev/null || return 1
  ! grep -F 'removed by Freemius' "$summary" >/dev/null || return 1
  grep -Fx 'artifact Plugin Check passed' "$log" >/dev/null || return 1
  grep -Fx 'isolated install and activation passed' "$activation_log" >/dev/null || return 1
  jq -e \
    --arg root "$root" \
    --arg summary "$summary" \
    --arg log "$log" \
    --arg activation_log "$activation_log" '
      .free_extracted_root == $root and
      .transformation_summary == $summary and
      .verification.php_lint == "passed" and
      .verification.deployment_id == .freemius.deployment_id and
      .verification.source_zip_sha256 == .source_zip_sha256 and
      .verification.free_zip_sha256 == .free_zip.sha256 and
      .verification.plugin_check.exit_code == 0 and
      .verification.plugin_check.log == $log and
      .verification.activation.exit_code == 0 and
      .verification.activation.log == $activation_log and
      .stages.verify == "passed"
    ' "$manifest" >/dev/null || return 1
  assert_contains "$output" "verified_root=$root" || return 1
  assert_contains "$output" "transformation_summary=$summary"
}

test_verify_installs_before_plugin_check() {
  local fixture order
  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  order="$fixture/runner-order.log"
  printf '%s\n' '#!/usr/bin/env bash' 'printf "activation\\n" >> "$RUNNER_ORDER_LOG"' > "$fixture/activate-check"
  printf '%s\n' '#!/usr/bin/env bash' 'test "$(tail -n 1 "$RUNNER_ORDER_LOG")" = activation || exit 71' 'printf "plugin-check\\n" >> "$RUNNER_ORDER_LOG"' > "$fixture/plugin-check"
  chmod +x "$fixture/activate-check" "$fixture/plugin-check"
  RUNNER_ORDER_LOG="$order" run_artifact_verify "$fixture" >/dev/null || return 1
  test "$(cat "$order")" = "$(printf 'activation\nplugin-check')"
}

make_test_local_runner_fixture() {
  local fixture=$1
  local fake_bin="$fixture/bin"
  mkdir -p "$fixture/artifact" "$fixture/wp-content/plugins/plugin-check" "$fake_bin"
  printf 'artifact\n' > "$fixture/artifact/grand-media.php"
  printf 'cli bootstrap\n' > "$fixture/wp-content/plugins/plugin-check/cli.php"
  printf '%s' '#!/usr/bin/env bash
set -u
printf "%s\n" "$*" >> "$FAKE_WP_TEST_LOG"
case "$*" in
  "option get home") printf "%s\n" "${FAKE_WP_HOME:-https://test.local}" ;;
  "option get siteurl") printf "%s\n" "${FAKE_WP_SITEURL:-https://test.local}" ;;
  "eval echo WP_PLUGIN_DIR;") printf "%s\n" "$FAKE_WP_PLUGIN_DIR" ;;
  "plugin activate grand-media") printf "active\n" > "$FAKE_WP_ACTIVE_MARKER" ;;
  "plugin is-active grand-media") test -f "$FAKE_WP_ACTIVE_MARKER" ;;
  "plugin is-active plugin-check") exit 0 ;;
  "plugin get plugin-check --field=version") printf "%s\n" "${FAKE_PLUGIN_CHECK_VERSION:-2.1.0}" ;;
  plugin\ check\ *) printf "%s\n" "${FAKE_PLUGIN_CHECK_OUTPUT:-[]}"; exit "${FAKE_PLUGIN_CHECK_EXIT:-0}" ;;
  *) exit 90 ;;
esac
' > "$fake_bin/wp-test"
  chmod +x "$fake_bin/wp-test"
}

run_test_local_runner() {
  local fixture=$1
  local runner=$2
  shift 2
  PATH="$fixture/bin:$PATH" \
    FAKE_WP_TEST_LOG="$fixture/wp-test.log" \
    FAKE_WP_PLUGIN_DIR="$fixture/wp-content/plugins" \
    FAKE_WP_ACTIVE_MARKER="$fixture/grand-media.active" \
    "$runner" "$fixture/artifact" "$@"
}

test_test_local_activation_runner_installs_exact_artifact() {
  local fixture target
  fixture=$(mktemp -d)
  make_test_local_runner_fixture "$fixture" || return 1
  target="$fixture/wp-content/plugins/grand-media"
  run_test_local_runner "$fixture" "$TEST_LOCAL_ACTIVATION_RUNNER" >/dev/null || return 1
  diff -qr "$fixture/artifact" "$target" || return 1
  test -f "$fixture/grand-media.active" || return 1
  grep -Fx 'plugin activate grand-media' "$fixture/wp-test.log" >/dev/null || return 1
  grep -Fx 'plugin is-active grand-media' "$fixture/wp-test.log" >/dev/null
}

test_test_local_runners_accept_http_test_local() {
  local fixture runner
  for runner in "$TEST_LOCAL_ACTIVATION_RUNNER" "$TEST_LOCAL_PLUGIN_CHECK_RUNNER"; do
    fixture=$(mktemp -d)
    make_test_local_runner_fixture "$fixture" || return 1
    if test "$runner" = "$TEST_LOCAL_PLUGIN_CHECK_RUNNER"; then
      cp -R "$fixture/artifact" "$fixture/wp-content/plugins/grand-media"
    fi
    FAKE_WP_HOME='http://test.local' FAKE_WP_SITEURL='http://test.local' \
      run_test_local_runner "$fixture" "$runner" >/dev/null || return 1
  done
}

test_test_local_runners_reject_wrong_site_without_mutation() {
  local fixture output rc runner url_field
  for runner in "$TEST_LOCAL_ACTIVATION_RUNNER" "$TEST_LOCAL_PLUGIN_CHECK_RUNNER"; do
    for url_field in home siteurl; do
      fixture=$(mktemp -d)
      make_test_local_runner_fixture "$fixture" || return 1
      if test "$runner" = "$TEST_LOCAL_PLUGIN_CHECK_RUNNER"; then
        cp -R "$fixture/artifact" "$fixture/wp-content/plugins/grand-media"
      fi
      if test "$url_field" = home; then
        output=$(FAKE_WP_HOME='https://production.example' \
          run_test_local_runner "$fixture" "$runner" 2>&1)
      else
        output=$(FAKE_WP_SITEURL='https://production.example' \
          run_test_local_runner "$fixture" "$runner" 2>&1)
      fi
      rc=$?
      test "$rc" -ne 0 || return 1
      assert_contains "$output" 'wp-test does not target test.local' || return 1
      if test "$runner" = "$TEST_LOCAL_ACTIVATION_RUNNER"; then
        ! test -e "$fixture/wp-content/plugins/grand-media" || return 1
        ! grep -F 'plugin activate grand-media' "$fixture/wp-test.log" >/dev/null || return 1
      else
        diff -qr "$fixture/artifact" "$fixture/wp-content/plugins/grand-media" >/dev/null || return 1
        ! grep -F 'plugin check ' "$fixture/wp-test.log" >/dev/null || return 1
      fi
    done
  done
}

test_test_local_activation_runner_refuses_overwrite() {
  local fixture output rc sentinel
  fixture=$(mktemp -d)
  make_test_local_runner_fixture "$fixture" || return 1
  mkdir "$fixture/wp-content/plugins/grand-media"
  sentinel="$fixture/wp-content/plugins/grand-media/preserve.txt"
  printf 'preserve\n' > "$sentinel"
  output=$(run_test_local_runner "$fixture" "$TEST_LOCAL_ACTIVATION_RUNNER" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Grand Media is already installed on test.local' || return 1
  test "$(cat "$sentinel")" = preserve || return 1
  ! grep -F 'plugin activate grand-media' "$fixture/wp-test.log" >/dev/null
}

test_test_local_plugin_check_runner_allows_warnings() {
  local fixture output
  fixture=$(mktemp -d)
  make_test_local_runner_fixture "$fixture" || return 1
  cp -R "$fixture/artifact" "$fixture/wp-content/plugins/grand-media"
  output=$(FAKE_PLUGIN_CHECK_OUTPUT='[{"type":"WARNING","code":"example_warning"}]' \
    run_test_local_runner "$fixture" "$TEST_LOCAL_PLUGIN_CHECK_RUNNER") || return 1
  assert_contains "$output" 'Plugin Check: 0 errors, 1 warnings' || return 1
  grep -F -- '--require=' "$fixture/wp-test.log" >/dev/null || return 1
  grep -F -- '--mode=update' "$fixture/wp-test.log" >/dev/null || return 1
  grep -F -- '--format=strict-json' "$fixture/wp-test.log" >/dev/null
}

test_test_local_plugin_check_runner_allows_text_success() {
  local fixture output
  fixture=$(mktemp -d)
  make_test_local_runner_fixture "$fixture" || return 1
  cp -R "$fixture/artifact" "$fixture/wp-content/plugins/grand-media"
  output=$(FAKE_PLUGIN_CHECK_OUTPUT='Checks complete. No errors found.' \
    run_test_local_runner "$fixture" "$TEST_LOCAL_PLUGIN_CHECK_RUNNER") || return 1
  assert_contains "$output" 'Plugin Check: 0 errors, 0 warnings'
}

test_test_local_plugin_check_runner_rejects_errors() {
  local fixture output rc
  fixture=$(mktemp -d)
  make_test_local_runner_fixture "$fixture" || return 1
  cp -R "$fixture/artifact" "$fixture/wp-content/plugins/grand-media"
  output=$(FAKE_PLUGIN_CHECK_OUTPUT='[{"type":"ERROR","code":"example_error"}]' \
    run_test_local_runner "$fixture" "$TEST_LOCAL_PLUGIN_CHECK_RUNNER" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Plugin Check: 1 errors, 0 warnings'
}

test_test_local_plugin_check_runner_rejects_wrong_version() {
  local fixture output rc
  fixture=$(mktemp -d)
  make_test_local_runner_fixture "$fixture" || return 1
  cp -R "$fixture/artifact" "$fixture/wp-content/plugins/grand-media"
  output=$(FAKE_PLUGIN_CHECK_VERSION='2.0.0' \
    run_test_local_runner "$fixture" "$TEST_LOCAL_PLUGIN_CHECK_RUNNER" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Plugin Check 2.1.0 is required on test.local' || return 1
  ! grep -F 'plugin check ' "$fixture/wp-test.log" >/dev/null
}

test_test_local_plugin_check_runner_rejects_installed_mismatch() {
  local fixture output rc
  fixture=$(mktemp -d)
  make_test_local_runner_fixture "$fixture" || return 1
  cp -R "$fixture/artifact" "$fixture/wp-content/plugins/grand-media"
  printf 'different\n' > "$fixture/wp-content/plugins/grand-media/grand-media.php"
  output=$(run_test_local_runner "$fixture" "$TEST_LOCAL_PLUGIN_CHECK_RUNNER" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Installed Grand Media does not match the verified Freemius artifact' || return 1
  ! grep -F 'plugin check ' "$fixture/wp-test.log" >/dev/null
}

test_verify_rejects_broken_provenance_and_invalidates_dependents() {
  local fixture manifest mode output rc
  for mode in upload-source free-deployment free-source; do
    fixture=$(mktemp -d)
    make_artifact_fixture "$fixture" || return 1
    manifest="$fixture/release-manifest.json"
    jq '.stages.verify = "passed" | .stages.svn_prepare = "passed" |
        .verification = {deployment_id: "9001", source_zip_sha256: .source_zip_sha256, free_zip_sha256: .free_zip.sha256} |
        .svn = {sentinel: true}' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" || return 1
    case $mode in
      upload-source) jq '.freemius.source_zip_sha256 = "ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff"' "$manifest" > "$manifest.next" ;;
      free-deployment) jq '.free_zip.deployment_id = "9999"' "$manifest" > "$manifest.next" ;;
      free-source) jq '.free_zip.source_zip_sha256 = "ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff"' "$manifest" > "$manifest.next" ;;
    esac
    mv "$manifest.next" "$manifest" || return 1
    output=$(run_artifact_verify "$fixture" 2>&1)
    rc=$?
    test "$rc" -ne 0 || return 1
    jq -e '
      (.stages | has("verify") | not) and
      (.stages | has("svn_prepare") | not) and
      (.stages | has("svn_publish") | not) and
      (has("verification") | not) and
      (has("svn") | not)
    ' "$manifest" >/dev/null || return 1
  done
}

test_earlier_stages_preserve_recorded_svn_publication() {
  local fixture manifest command evidence before after output rc
  for command in verify svn-prepare; do
    for evidence in passed-stage publication-receipt; do
      fixture=$(mktemp -d)
      if test "$command" = verify; then
        make_artifact_fixture "$fixture" || return 1
      else
        make_svn_fixture "$fixture" || return 1
      fi
      manifest="$fixture/release-manifest.json"
      case $evidence in
        passed-stage)
          jq '.stages.svn_publish = "passed"' "$manifest" > "$manifest.next" || return 1
          ;;
        publication-receipt)
          jq '.svn.publication = {revision: 5001, timestamp: "2026-09-02T00:00:00Z"}' "$manifest" > "$manifest.next" || return 1
          ;;
      esac
      mv "$manifest.next" "$manifest" || return 1
      before=$(shasum -a 256 "$manifest" | awk '{print $1}') || return 1
      if test "$command" = verify; then
        output=$(run_artifact_verify "$fixture" 2>&1)
      else
        output=$(run_svn_prepare "$fixture" 2>&1)
      fi
      rc=$?
      after=$(shasum -a 256 "$manifest" | awk '{print $1}') || return 1
      test "$rc" -ne 0 || return 1
      assert_contains "$output" 'SVN publication is already recorded' || return 1
      test "$after" = "$before" || return 1
    done
  done
}

test_verify_requires_complete_freemius_identity_before_runners() {
  local fixture manifest mode expected output rc
  for mode in missing-upload-endpoint tampered-upload-endpoint missing-upload-release-mode wrong-upload-release-mode missing-release-mode wrong-release-mode missing-download-endpoint tampered-download-endpoint; do
    fixture=$(mktemp -d)
    make_artifact_fixture "$fixture" || return 1
    manifest="$fixture/release-manifest.json"
    case $mode in
      missing-upload-endpoint)
        jq 'del(.freemius.upload_endpoint_class)' "$manifest" > "$manifest.next"
        expected='Freemius upload provenance endpoint does not match Grand Media'
        ;;
      tampered-upload-endpoint)
        jq '.freemius.upload_endpoint_class = "POST /v1/products/999/tags.json"' "$manifest" > "$manifest.next"
        expected='Freemius upload provenance endpoint does not match Grand Media'
        ;;
      missing-upload-release-mode)
        jq 'del(.freemius.upload_release_mode)' "$manifest" > "$manifest.next"
        expected='Freemius upload provenance upload-time release mode is not pending'
        ;;
      wrong-upload-release-mode)
        jq '.freemius.upload_release_mode = "released"' "$manifest" > "$manifest.next"
        expected='Freemius upload provenance upload-time release mode is not pending'
        ;;
      missing-release-mode)
        jq 'del(.freemius.release_mode)' "$manifest" > "$manifest.next"
        expected='Freemius upload provenance release mode is invalid'
        ;;
      wrong-release-mode)
        jq '.freemius.release_mode = "released"' "$manifest" > "$manifest.next"
        expected='Freemius release provenance is incomplete'
        ;;
      missing-download-endpoint)
        jq 'del(.free_zip.download_endpoint_class)' "$manifest" > "$manifest.next"
        expected='Freemius download provenance endpoint does not match Grand Media'
        ;;
      tampered-download-endpoint)
        jq '.free_zip.download_endpoint_class = "GET /v1/products/20980/tags/9999.zip?is_premium=false"' "$manifest" > "$manifest.next"
        expected='Freemius download provenance endpoint does not match Grand Media'
        ;;
    esac
    mv "$manifest.next" "$manifest" || return 1
    output=$(run_artifact_verify "$fixture" 2>&1)
    rc=$?
    test "$rc" -ne 0 || return 1
    assert_contains "$output" "$expected" || return 1
    ! test -e "$fixture/artifact-activation.log" || return 1
    ! test -e "$fixture/artifact-plugin-check.log" || return 1
    ! jq -e '.stages.verify == "passed"' "$manifest" >/dev/null || return 1
  done
}

test_verify_rejects_gatekeeper_string() {
  local fixture
  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  printf '%s\n' '$wp_org_gatekeeper = true;' >> "$fixture/free/grand-media/grand-media.php"
  rebuild_artifact_zips "$fixture" || return 1
  assert_verify_failed_without_passed_stage "$fixture" 'Free artifact contains wp_org_gatekeeper'
}

test_verify_rejects_every_literal_distribution_exclusion() {
  local fixture path
  while IFS= read -r path; do
    case $path in ''|'#'*) continue ;; esac
    fixture=$(mktemp -d)
    make_artifact_fixture "$fixture" || return 1
    mkdir -p "$(dirname "$fixture/free/grand-media/$path")"
    case $path in
      .git|.github|.idea|.vscode|node_modules|.agents|.claude|.devin|.impeccable|.superpowers|graphify-out|docs|tests|bin)
        mkdir -p "$fixture/free/grand-media/$path"
        printf 'development\n' > "$fixture/free/grand-media/$path/file.txt"
        ;;
      *)
        printf 'development\n' > "$fixture/free/grand-media/$path"
        ;;
    esac
    rebuild_artifact_zips "$fixture" || return 1
    assert_verify_failed_without_passed_stage "$fixture" "Free artifact contains development path: $path" || return 1
  done < "$ROOT/.distignore"
}

test_verify_distribution_exclusions_use_root_relative_paths() {
  local fixture
  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  mkdir -p "$fixture/free/grand-media/content" "$fixture/free/grand-media/assets/nested"
  printf 'runtime data\n' > "$fixture/free/grand-media/content/.env.local"
  printf 'runtime data\n' > "$fixture/free/grand-media/assets/nested/.config.codekit3"
  rebuild_artifact_zips "$fixture" || return 1
  run_artifact_verify "$fixture" >/dev/null
}

test_verify_invalidates_stale_passed_evidence_before_failure() {
  local fixture manifest output rc
  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  run_artifact_verify "$fixture" >/dev/null || return 1
  jq -e '.stages.verify == "passed" and .verification.php_lint == "passed"' "$manifest" >/dev/null || return 1
  output=$(run_artifact_verify "$fixture" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Extraction target already exists: free-extracted' || return 1
  jq -e '
    (.stages | has("verify") | not) and
    (has("verification") | not) and
    (has("free_extracted_root") | not) and
    (has("transformation_summary") | not)
  ' "$manifest" >/dev/null
}

test_verify_binds_source_zip_size_and_hash_to_manifest() {
  local fixture manifest mode output rc
  for mode in size-mismatch hash-mismatch size-invalid hash-invalid; do
    fixture=$(mktemp -d)
    make_artifact_fixture "$fixture" || return 1
    manifest="$fixture/release-manifest.json"
    case $mode in
      size-mismatch) jq '.source_zip_bytes += 1' "$manifest" > "$manifest.next" ;;
      hash-mismatch) jq '.source_zip_sha256 = "0000000000000000000000000000000000000000000000000000000000000000"' "$manifest" > "$manifest.next" ;;
      size-invalid) jq '.source_zip_bytes = "12x"' "$manifest" > "$manifest.next" ;;
      hash-invalid) jq '.source_zip_sha256 = "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA"' "$manifest" > "$manifest.next" ;;
    esac
    mv "$manifest.next" "$manifest" || return 1
    output=$(run_artifact_verify "$fixture" 2>&1)
    rc=$?
    test "$rc" -ne 0 || return 1
    case $mode in
      size-mismatch) assert_contains "$output" 'Recorded source ZIP size does not match manifest' || return 1 ;;
      hash-mismatch) assert_contains "$output" 'Recorded source ZIP SHA-256 does not match manifest' || return 1 ;;
      size-invalid) assert_contains "$output" 'Release manifest source ZIP size is invalid' || return 1 ;;
      hash-invalid) assert_contains "$output" 'Release manifest source ZIP SHA-256 is invalid' || return 1 ;;
    esac
    ! test -e "$fixture/source-extracted" || return 1
    ! test -e "$fixture/freemius-transformations.txt" || return 1
    ! jq -e '.stages.verify == "passed"' "$manifest" >/dev/null || return 1
  done
}

test_verify_fails_closed_when_zip_type_inspection_fails() {
  local fixture fake_bin output rc
  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  fake_bin="$fixture/fail-bin"
  mkdir -p "$fake_bin"
  printf '%s' '#!/usr/bin/env bash
if test "${1:-}" = -Z && test "${2:-}" = -l; then
  exit 73
fi
exec /usr/bin/unzip "$@"
' > "$fake_bin/unzip"
  chmod +x "$fake_bin/unzip"
  output=$(PATH="$fake_bin:$PATH" run_artifact_verify "$fixture" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Unable to inspect Free ZIP entry types' || return 1
  ! jq -e '.stages.verify == "passed"' "$fixture/release-manifest.json" >/dev/null
}

test_verify_rejects_traversal_and_absolute_entries() {
  local fixture name
  for name in '../outside.php' '/absolute.php' 'C:\\absolute.php' '\\server\share\file.php' 'grand-media/nested/../escape.php'; do
    fixture=$(mktemp -d)
    make_artifact_fixture "$fixture" || return 1
    make_zip_with_named_entry "$fixture/grand-media-1.25.1-free.zip" "$name" || return 1
    refresh_artifact_manifest "$fixture" || return 1
    assert_verify_failed_without_passed_stage "$fixture" "Unsafe ZIP entry: $name" || return 1
    ! test -e "$fixture/outside.php" || return 1
  done
}

test_verify_rejects_symbolic_link_entry_before_extraction() {
  local fixture
  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  ln -s ../../outside.php "$fixture/free/grand-media/link.php" || return 1
  rm -f "$fixture/grand-media-1.25.1-free.zip"
  ( cd "$fixture/free" && /usr/bin/zip -qry "$fixture/grand-media-1.25.1-free.zip" grand-media ) || return 1
  refresh_artifact_manifest "$fixture" || return 1
  assert_verify_failed_without_passed_stage "$fixture" 'Free ZIP contains a symbolic link' || return 1
  ! test -e "$fixture/outside.php"
}

test_verify_rejects_multiple_roots_and_malformed_zip() {
  local fixture
  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  append_named_zip_entry "$fixture/grand-media-1.25.1-free.zip" 'another-plugin/file.php' || return 1
  unzip -Z1 "$fixture/grand-media-1.25.1-free.zip" | grep -q '^grand-media/' || return 1
  unzip -Z1 "$fixture/grand-media-1.25.1-free.zip" | grep -q '^another-plugin/' || return 1
  refresh_artifact_manifest "$fixture" || return 1
  assert_verify_failed_without_passed_stage "$fixture" 'Free ZIP entries must be under grand-media/' || return 1

  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  printf 'not a ZIP\n' > "$fixture/grand-media-1.25.1-free.zip"
  refresh_artifact_manifest "$fixture" || return 1
  assert_verify_failed_without_passed_stage "$fixture" 'Free ZIP is invalid'
}

test_verify_rejects_each_version_mismatch() {
  local fixture field
  for field in header constant stable; do
    fixture=$(mktemp -d)
    make_artifact_fixture "$fixture" || return 1
    case $field in
      header) sed 's/Version: 1.25.1/Version: 9.9.9/' "$fixture/free/grand-media/grand-media.php" > "$fixture/next" ;;
      constant) sed "s/\\\$version = '1.25.1'/\\\$version = '9.9.9'/" "$fixture/free/grand-media/grand-media.php" > "$fixture/next" ;;
      stable) sed 's/Stable tag: 1.25.1/Stable tag: 9.9.9/' "$fixture/free/grand-media/readme.txt" > "$fixture/next" ;;
    esac
    if test "$field" = stable; then
      mv "$fixture/next" "$fixture/free/grand-media/readme.txt"
    else
      mv "$fixture/next" "$fixture/free/grand-media/grand-media.php"
    fi
    rebuild_artifact_zips "$fixture" || return 1
    case $field in
      header) assert_verify_failed_without_passed_stage "$fixture" 'Plugin header version 9.9.9 does not match 1.25.1' || return 1 ;;
      constant) assert_verify_failed_without_passed_stage "$fixture" 'Gmedia::$version 9.9.9 does not match 1.25.1' || return 1 ;;
      stable) assert_verify_failed_without_passed_stage "$fixture" 'Stable tag 9.9.9 does not match 1.25.1' || return 1 ;;
    esac
  done
}

test_verify_rejects_php_lint_failure_before_plugin_check() {
  local fixture
  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  printf '%s\n' '<?php syntax error' > "$fixture/free/grand-media/broken.php"
  rebuild_artifact_zips "$fixture" || return 1
  assert_verify_failed_without_passed_stage "$fixture" 'Errors parsing' || return 1
  ! test -e "$fixture/artifact-plugin-check.log"
}

test_verify_requires_configured_and_passing_plugin_check() {
  local fixture output rc log
  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  output=$(env -u GRAND_MEDIA_ARTIFACT_PLUGIN_CHECK_CMD "$CLI" verify --resume "$fixture/release-manifest.json" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'artifact Plugin Check runner is not configured' || return 1
  ! jq -e '.stages.verify == "passed"' "$fixture/release-manifest.json" >/dev/null || return 1

  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  jq '.stages.verify = "passed" |
      .verification = { php_lint: "passed", plugin_check: { exit_code: 0, log: "/stale/log" } } |
      .free_extracted_root = "/stale/root" |
      .transformation_summary = "/stale/summary"' "$fixture/release-manifest.json" > "$fixture/release-manifest.next" || return 1
  mv "$fixture/release-manifest.next" "$fixture/release-manifest.json" || return 1
  printf '%s\n' '#!/usr/bin/env bash' 'printf "Plugin Check failed safely\\n"' 'exit 7' > "$fixture/plugin-check"
  chmod +x "$fixture/plugin-check"
  log="$fixture/artifact-plugin-check.log"
  output=$(run_artifact_verify "$fixture" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  test -f "$log" || return 1
  grep -Fx 'Plugin Check failed safely' "$log" >/dev/null || return 1
  jq -e --arg log "$log" '
    .verification.plugin_check.exit_code == 7 and
    .verification.plugin_check.log == $log and
    (.stages | has("verify") | not) and
    (has("free_extracted_root") | not) and
    (has("transformation_summary") | not)
  ' "$fixture/release-manifest.json" >/dev/null
}

test_verify_requires_configured_and_passing_isolated_activation() {
  local fixture output rc log
  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  output=$(GRAND_MEDIA_ARTIFACT_PLUGIN_CHECK_CMD="$fixture/plugin-check" \
    env -u GRAND_MEDIA_ARTIFACT_ACTIVATION_CMD \
    "$CLI" verify --resume "$fixture/release-manifest.json" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'artifact isolated activation runner is not configured' || return 1
  ! jq -e '.stages.verify == "passed"' "$fixture/release-manifest.json" >/dev/null || return 1

  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  printf '%s\n' '#!/usr/bin/env bash' 'printf "isolated activation failed safely\\n"' 'exit 8' > "$fixture/activate-check"
  chmod +x "$fixture/activate-check"
  log="$fixture/artifact-activation.log"
  output=$(run_artifact_verify "$fixture" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  test -f "$log" || return 1
  grep -Fx 'isolated activation failed safely' "$log" >/dev/null || return 1
  jq -e --arg log "$log" '
    .verification.activation.exit_code == 8 and
    .verification.activation.log == $log and
    (.stages | has("verify") | not)
  ' "$fixture/release-manifest.json" >/dev/null
}

test_verify_refuses_preexisting_extraction_target() {
  local fixture marker
  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  mkdir -p "$fixture/free-extracted"
  marker="$fixture/free-extracted/do-not-overwrite"
  printf 'preserve\n' > "$marker"
  assert_verify_failed_without_passed_stage "$fixture" 'Extraction target already exists: free-extracted' || return 1
  test "$(cat "$marker")" = preserve
}

make_svn_fixture() {
  local fixture=$1
  local free_root="$fixture/free-extracted/grand-media"
  local checkout="$fixture/svn-checkout"
  local free_zip="$fixture/grand-media-1.25.1-free.zip"
  local source_zip="$fixture/grand-media-1.25.1-source.zip"
  local free_size free_sha source_size source_sha
  mkdir -p "$free_root" "$checkout/.svn" "$checkout/trunk" "$checkout/tags" "$checkout/assets"
  write_artifact_metadata "$free_root" || return 1
  printf 'verified free file\n' > "$free_root/runtime.txt"
  ( cd "$fixture/free-extracted" && /usr/bin/zip -qr "$free_zip" grand-media ) || return 1
  cp "$free_zip" "$source_zip" || return 1
  free_size=$(wc -c < "$free_zip" | tr -d '[:space:]') || return 1
  free_sha=$(shasum -a 256 "$free_zip" | awk '{ print $1 }') || return 1
  source_size=$(wc -c < "$source_zip" | tr -d '[:space:]') || return 1
  source_sha=$(shasum -a 256 "$source_zip" | awk '{ print $1 }') || return 1
  printf 'old trunk file\n' > "$checkout/trunk/obsolete file.txt"
  printf 'preserve asset\n' > "$checkout/assets/banner-1544x500.png"
  jq -n \
    --arg free_root "$free_root" \
    --arg free_zip "$free_zip" \
    --arg free_sha "$free_sha" \
    --arg source_zip "$source_zip" \
    --arg source_sha "$source_sha" \
    --argjson free_size "$free_size" \
    --argjson source_size "$source_size" \
    '{
      version: "1.25.1",
      git_sha: "0123456789abcdef0123456789abcdef01234567",
      source_zip: $source_zip,
      source_zip_bytes: $source_size,
      source_zip_sha256: $source_sha,
      freemius_upload_attempt: { source_zip_sha256: $source_sha, timestamp: "2026-09-01T00:00:00Z" },
      freemius: {
        product_id: 20980,
        deployment_id: "9001",
        release_mode: "pending",
        upload_release_mode: "pending",
        source_zip_sha256: $source_sha,
        upload_endpoint_class: "POST /v1/products/20980/tags.json"
      },
      free_extracted_root: $free_root,
      free_zip: {
        path: $free_zip,
        bytes: $free_size,
        sha256: $free_sha,
        deployment_id: "9001",
        source_zip_sha256: $source_sha,
        download_endpoint_class: "GET /v1/products/20980/tags/{deployment_id}.zip?is_premium=false"
      },
      verification: {
        deployment_id: "9001",
        source_zip_sha256: $source_sha,
        free_zip_sha256: $free_sha
      },
      stages: {
        build: "passed",
        upload_pending: "passed",
        download_free: "passed",
        verify: "passed"
      }
    }' > "$fixture/release-manifest.json"
}

run_svn_prepare() (
  local fixture=$1
  shift
  export PATH="$ROOT/tests/release/fakes:$PATH"
  export FAKE_SVN_CHECKOUT="$fixture/svn-checkout"
  export FAKE_SVN_LOG="$fixture/svn.log"
  export FAKE_SVN_STATE="$fixture/svn-state"
  export FAKE_SVN_COMMIT_MARKER="$fixture/svn-commit.marker"
  printf '0' > "$fixture/svn-commit.count"
  export FAKE_SVN_COMMIT_COUNT="$fixture/svn-commit.count"
  export FAKE_SVN_MODE="${FAKE_SVN_MODE:-}"
  export FAKE_SVN_REPOSITORY_ROOT="${FAKE_SVN_REPOSITORY_ROOT:-https://plugins.svn.wordpress.org}"
  export FAKE_SVN_REVISION="${FAKE_SVN_REVISION:-4321}"
  export FAKE_SVN_REVISION_AFTER_UPDATE="${FAKE_SVN_REVISION_AFTER_UPDATE:-4322}"
  export FAKE_SVN_URL="${FAKE_SVN_URL:-https://plugins.svn.wordpress.org/grand-media}"
  export FAKE_SVN_VERSION="${FAKE_SVN_VERSION:-1.25.1}"
  export FAKE_SVN_MISSING_PATH="${FAKE_SVN_MISSING_PATH:-trunk/obsolete file.txt}"
  export FAKE_SVN_COPY_CORRUPT="${FAKE_SVN_COPY_CORRUPT:-0}"
  "$CLI" svn-prepare \
    --manifest "$fixture/release-manifest.json" \
    --checkout "$fixture/svn-checkout" "$@"
)

assert_svn_prepare_failed() {
  local fixture=$1
  local expected=$2
  local output rc
  output=$(run_svn_prepare "$fixture" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" "$expected" || return 1
  ! jq -e '.stages.svn_prepare == "passed"' "$fixture/release-manifest.json" >/dev/null || return 1
  ! test -e "$fixture/svn-commit.marker"
}

test_svn_prepare_mirrors_verified_root_and_never_commits() {
  local fixture manifest checkout free_root output svn_temp
  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  checkout="$fixture/svn-checkout"
  free_root="$fixture/free-extracted/grand-media"
  svn_temp="$fixture/svn-temp"
  mkdir -p "$svn_temp"
  printf 'preserve temp sibling\n' > "$svn_temp/do-not-remove"
  checkout=$(cd "$checkout" && pwd -P) || return 1
  free_root=$(cd "$free_root" && pwd -P) || return 1
  output=$(TMPDIR="$svn_temp" run_svn_prepare "$fixture") || return 1
  diff -qr --exclude=.svn "$free_root" "$checkout/trunk" || return 1
  diff -qr --exclude=.svn "$free_root" "$checkout/tags/1.25.1" || return 1
  test "$(cat "$checkout/assets/banner-1544x500.png")" = 'preserve asset' || return 1
  test ! -e "$checkout/trunk/obsolete file.txt" || return 1
  grep -Fx 'update .' "$fixture/svn.log" >/dev/null || return 1
  grep -Fx 'add --force trunk' "$fixture/svn.log" >/dev/null || return 1
  grep -Fx 'rm --force trunk/obsolete file.txt' "$fixture/svn.log" >/dev/null || return 1
  grep -Fx 'copy trunk tags/1.25.1' "$fixture/svn.log" >/dev/null || return 1
  grep -Fx 'diff --summarize .' "$fixture/svn.log" >/dev/null || return 1
  ! grep -F 'commit' "$fixture/svn.log" >/dev/null || return 1
  ! test -e "$fixture/svn-commit.marker" || return 1
  test "$(cat "$fixture/svn-commit.count")" = 0 || return 1
  test "$(cat "$svn_temp/do-not-remove")" = 'preserve temp sibling' || return 1
  ! find "$svn_temp" -maxdepth 1 -type d -name 'grand-media-svn-prepare.*' -print -quit | grep -q . || return 1
  jq -e \
    --arg checkout "$checkout" \
    --arg free_root "$free_root" '
      .stages.svn_prepare == "passed" and
      .svn.checkout == $checkout and
      .svn.repository_root == "https://plugins.svn.wordpress.org" and
      .svn.start_revision == "4322" and
      .svn.url == "https://plugins.svn.wordpress.org/grand-media" and
      .svn.verified_root == $free_root and
      .svn.trunk == ($checkout + "/trunk") and
      .svn.tag == ($checkout + "/tags/1.25.1") and
      .svn.deployment_id == .freemius.deployment_id and
      .svn.source_zip_sha256 == .source_zip_sha256 and
      .svn.free_zip_sha256 == .free_zip.sha256 and
      (.svn.status | contains("tags/1.25.1")) and
      (.svn.diff_summary | contains("trunk/grand-media.php"))
    ' "$manifest" >/dev/null || return 1
  assert_contains "$output" 'A       tags/1.25.1/' || return 1
  assert_contains "$output" 'M       trunk/grand-media.php' || return 1
  test "$(grep -Fc 'list https://plugins.svn.wordpress.org/grand-media/tags' "$fixture/svn.log")" -eq 2 || return 1
  awk '
    $0 == "update ." { update_line = NR }
    $0 == "info --show-item revision ." { revision_line = NR }
    $0 == "list https://plugins.svn.wordpress.org/grand-media/tags" { last_list = NR }
    $0 == "copy trunk tags/1.25.1" { copy_line = NR }
    END { exit !(update_line < revision_line && last_list + 1 == copy_line) }
  ' "$fixture/svn.log"
}

test_svn_prepare_binds_fresh_zip_to_verification_evidence() {
  local fixture manifest output rc svn_temp
  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  svn_temp="$fixture/svn-temp"
  mkdir -p "$svn_temp"
  printf 'preserve temp sibling\n' > "$svn_temp/do-not-remove"
  printf 'changed after verification\n' > "$fixture/free-extracted/grand-media/runtime.txt"
  output=$(TMPDIR="$svn_temp" run_svn_prepare "$fixture" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Recorded verified root does not match hash-bound free ZIP' || return 1
  ! test -e "$fixture/svn.log" || return 1
  test "$(cat "$svn_temp/do-not-remove")" = 'preserve temp sibling' || return 1
  ! find "$svn_temp" -maxdepth 1 -type d -name 'grand-media-svn-prepare.*' -print -quit | grep -q . || return 1
  ! jq -e '.stages.svn_prepare == "passed"' "$manifest" >/dev/null || return 1

  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  jq '.verification.free_zip_sha256 = "0000000000000000000000000000000000000000000000000000000000000000"' \
    "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" || return 1
  output=$(run_svn_prepare "$fixture" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Verified free ZIP SHA-256 does not match recorded artifact' || return 1
  ! test -e "$fixture/svn.log" || return 1
  ! jq -e '.stages.svn_prepare == "passed"' "$manifest" >/dev/null || return 1

  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  printf 'tamper\n' >> "$fixture/grand-media-1.25.1-free.zip"
  output=$(run_svn_prepare "$fixture" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Recorded free ZIP size does not match manifest' || return 1
  ! test -e "$fixture/svn.log" || return 1
  ! jq -e '.stages.svn_prepare == "passed"' "$manifest" >/dev/null
}

make_fake_mktemp() {
  local fake_bin=$1
  mkdir -p "$fake_bin"
  printf '%s' '#!/usr/bin/env bash
test "$#" -eq 2 || { printf "fake mktemp: expected -d TEMPLATE\\n" >&2; exit 64; }
test "$1" = -d || { printf "fake mktemp: expected -d TEMPLATE\\n" >&2; exit 64; }
printf "%s\\n" "$FAKE_MKTEMP_RESULT"
' > "$fake_bin/mktemp"
  chmod +x "$fake_bin/mktemp"
}

assert_faulty_mktemp_rejected() {
  local fixture=$1
  local temp_parent=$2
  local returned_path=$3
  local expected_diagnostic=${4:-Unsafe SVN preparation temporary directory}
  local fake_bin="$fixture/fake-mktemp-bin"
  local output rc
  make_fake_mktemp "$fake_bin" || return 1
  output=$(PATH="$fake_bin:$PATH" TMPDIR="$temp_parent" FAKE_MKTEMP_RESULT="$returned_path" \
    run_svn_prepare "$fixture" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" "$expected_diagnostic" || return 1
  ! test -e "$returned_path/.grand-media-svn-prepare" || return 1
  ! test -e "$fixture/svn.log" || return 1
  ! jq -e '.stages.svn_prepare == "passed"' "$fixture/release-manifest.json" >/dev/null
}

test_svn_prepare_rejects_outside_and_nonempty_fake_mktemp() {
  local fixture temp_parent returned_path
  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  temp_parent="$fixture/selected-temp"
  returned_path="$fixture/outside-temp/grand-media-svn-prepare.abc123"
  mkdir -p "$temp_parent" "$returned_path"
  printf 'outside sentinel\n' > "$returned_path/do-not-remove"
  assert_faulty_mktemp_rejected "$fixture" "$temp_parent" "$returned_path" || return 1
  test "$(cat "$returned_path/do-not-remove")" = 'outside sentinel' || return 1

  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  temp_parent="$fixture/selected-temp"
  returned_path="$temp_parent/grand-media-svn-prepare.def456"
  mkdir -p "$returned_path"
  printf 'nonempty sentinel\n' > "$returned_path/do-not-remove"
  assert_faulty_mktemp_rejected "$fixture" "$temp_parent" "$returned_path" \
    'Unsafe SVN preparation temporary directory is not empty' || return 1
  test "$(cat "$returned_path/do-not-remove")" = 'nonempty sentinel' || return 1

  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  temp_parent="$fixture/selected-temp"
  returned_path="$temp_parent/grand-media-svn-prepare.seven77"
  mkdir -p "$returned_path"
  assert_faulty_mktemp_rejected "$fixture" "$temp_parent" "$returned_path" || return 1
  test -d "$returned_path"
}

test_svn_prepare_rejects_root_and_plugin_root_fake_mktemp() {
  local fixture temp_parent repo_sha
  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  temp_parent="$fixture/selected-temp"
  mkdir -p "$temp_parent"
  assert_faulty_mktemp_rejected "$fixture" "$temp_parent" / || return 1
  test -f "$ROOT/grand-media.php" || return 1

  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  temp_parent="$fixture/selected-temp"
  mkdir -p "$temp_parent"
  test ! -e "$ROOT/.grand-media-svn-prepare" || return 1
  repo_sha=$(shasum -a 256 "$ROOT/grand-media.php" | awk '{ print $1 }') || return 1
  assert_faulty_mktemp_rejected "$fixture" "$temp_parent" "$ROOT" || return 1
  test ! -e "$ROOT/.grand-media-svn-prepare" || return 1
  test "$repo_sha" = "$(shasum -a 256 "$ROOT/grand-media.php" | awk '{ print $1 }')"
}

test_svn_prepare_temp_cleanup_on_term() {
  local fixture temp_parent ready pid temporary_root rc attempt
  fixture=$(mktemp -d)
  temp_parent="$fixture/selected-temp"
  ready="$fixture/ready"
  mkdir -p "$temp_parent"
  printf 'preserve sibling\n' > "$temp_parent/do-not-remove"
  (
    source "$ROOT/bin/release/lib.sh"
    temp_parent=$(cd "$temp_parent" && pwd -P) || exit 90
    temporary_root=$(create_svn_prepare_temp "$temp_parent") || exit 91
    trap 'svn_prepare_exit_cleanup "$?" "$temporary_root" "$temp_parent"' EXIT
    trap 'exit 143' TERM
    printf '%s\n' "$temporary_root" > "$ready"
    while :; do sleep 1; done
  ) &
  pid=$!
  for attempt in 1 2 3 4 5 6 7 8 9 10; do
    test -f "$ready" && break
    sleep 0.1
  done
  if ! test -f "$ready"; then
    wait "$pid" 2>/dev/null || true
    return 1
  fi
  temporary_root=$(cat "$ready") || return 1
  kill -TERM "$pid" || return 1
  wait "$pid"
  rc=$?
  test "$rc" -eq 143 || return 1
  ! test -e "$temporary_root" || return 1
  test "$(cat "$temp_parent/do-not-remove")" = 'preserve sibling'
}

test_svn_prepare_cleanup_rejects_tampered_marker() {
  local fixture temp_parent temporary_root
  fixture=$(mktemp -d)
  temp_parent="$fixture/selected-temp"
  mkdir -p "$temp_parent"
  temp_parent=$(cd "$temp_parent" && pwd -P) || return 1
  temporary_root=$( (
    source "$ROOT/bin/release/lib.sh"
    create_svn_prepare_temp "$temp_parent"
  ) ) || return 1
  printf 'preserve owned directory\n' > "$temporary_root/do-not-remove"
  printf 'tampered marker\n' > "$temporary_root/.grand-media-svn-prepare"
  if (
    source "$ROOT/bin/release/lib.sh"
    cleanup_svn_prepare_temp "$temporary_root" "$temp_parent"
  ); then
    return 1
  fi
  test "$(cat "$temporary_root/do-not-remove")" = 'preserve owned directory'
}

test_svn_prepare_cleanup_rejects_appended_marker_newline() {
  local fixture temp_parent temporary_root
  fixture=$(mktemp -d)
  temp_parent="$fixture/selected-temp"
  mkdir -p "$temp_parent"
  temp_parent=$(cd "$temp_parent" && pwd -P) || return 1
  temporary_root=$( (
    source "$ROOT/bin/release/lib.sh"
    create_svn_prepare_temp "$temp_parent"
  ) ) || return 1
  printf 'preserve owned directory\n' > "$temporary_root/do-not-remove"
  printf '\n' >> "$temporary_root/.grand-media-svn-prepare"
  if (
    source "$ROOT/bin/release/lib.sh"
    cleanup_svn_prepare_temp "$temporary_root" "$temp_parent"
  ); then
    return 1
  fi
  test "$(cat "$temporary_root/do-not-remove")" = 'preserve owned directory'
}

test_svn_prepare_exit_trap_reports_late_marker_tamper() {
  local fixture svn_temp output rc temporary_root
  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  svn_temp="$fixture/svn-temp"
  mkdir -p "$svn_temp"
  output=$(TMPDIR="$svn_temp" FAKE_SVN_MODE=tamper-temp-marker-late run_svn_prepare "$fixture" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  temporary_root=$(find "$svn_temp" -mindepth 1 -maxdepth 1 -type d -name 'grand-media-svn-prepare.*' -print) || return 1
  test -n "$temporary_root" && test -d "$temporary_root" || return 1
  test -f "$temporary_root/.grand-media-svn-prepare" || return 1
  ! test -e "$fixture/svn-commit.marker"
}

test_svn_prepare_requires_svn_command() {
  local fixture fake_bin command_path output rc
  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  fake_bin="$fixture/no-svn-bin"
  mkdir -p "$fake_bin"
  for command_path in bash basename dirname grep head jq mv rm sed; do
    ln -s "$(command -v "$command_path")" "$fake_bin/$command_path" || return 1
  done
  output=$(PATH="$fake_bin" /bin/bash "$CLI" svn-prepare \
    --manifest "$fixture/release-manifest.json" \
    --checkout "$fixture/svn-checkout" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Required command not found: svn' || return 1
  ! jq -e '.stages.svn_prepare == "passed"' "$fixture/release-manifest.json" >/dev/null
}

test_svn_prepare_rejects_unsafe_checkout_layouts() {
  local fixture checkout output rc mode
  for mode in filesystem-root plugin-root missing-svn missing-trunk missing-tags missing-assets symlink-trunk; do
    fixture=$(mktemp -d)
    make_svn_fixture "$fixture" || return 1
    checkout="$fixture/svn-checkout"
    case $mode in
      filesystem-root) checkout=/ ;;
      plugin-root) checkout=$ROOT ;;
      missing-svn) rmdir "$checkout/.svn" || return 1 ;;
      missing-trunk) rm -rf "$checkout/trunk" || return 1 ;;
      missing-tags) rmdir "$checkout/tags" || return 1 ;;
      missing-assets) rm -rf "$checkout/assets" || return 1 ;;
      symlink-trunk)
        rm -rf "$checkout/trunk" || return 1
        ln -s "$fixture/free-extracted/grand-media" "$checkout/trunk" || return 1
        ;;
    esac
    output=$(FAKE_SVN_CHECKOUT="$checkout" "$CLI" svn-prepare \
      --manifest "$fixture/release-manifest.json" --checkout "$checkout" 2>&1)
    rc=$?
    test "$rc" -ne 0 || return 1
    assert_contains "$output" 'SVN checkout' || return 1
    ! jq -e '.stages.svn_prepare == "passed"' "$fixture/release-manifest.json" >/dev/null || return 1
  done
}

test_svn_prepare_rejects_checkout_containing_plugin_git_root() {
  local fixture checkout sentinel repo_file_sha output rc
  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  checkout=$(cd "$ROOT/.." && pwd -P) || return 1
  sentinel="$fixture/external-sentinel"
  printf 'do not touch\n' > "$sentinel"
  repo_file_sha=$(shasum -a 256 "$ROOT/grand-media.php" | awk '{ print $1 }') || return 1
  output=$("$CLI" svn-prepare \
    --manifest "$fixture/release-manifest.json" --checkout "$checkout" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'SVN checkout must be outside the plugin Git root' || return 1
  test "$repo_file_sha" = "$(shasum -a 256 "$ROOT/grand-media.php" | awk '{ print $1 }')" || return 1
  test "$(cat "$sentinel")" = 'do not touch' || return 1
  ! test -e "$fixture/svn.log"
}

test_svn_prepare_rechecks_layout_after_update() {
  local fixture sentinel output rc
  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  sentinel="$fixture/external-sentinel"
  mkdir -p "$sentinel"
  printf 'do not touch\n' > "$sentinel/sentinel.txt"
  output=$(FAKE_SVN_MODE=swap-trunk-symlink FAKE_SVN_SWAP_TARGET="$sentinel" \
    run_svn_prepare "$fixture" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'SVN checkout requires a real top-level trunk directory' || return 1
  test "$(cat "$sentinel/sentinel.txt")" = 'do not touch' || return 1
  grep -Fx 'update .' "$fixture/svn.log" >/dev/null || return 1
  ! grep -E '^(add|rm|copy|commit) ' "$fixture/svn.log" >/dev/null || return 1
  test "$(cat "$fixture/svn-commit.count")" = 0
}

test_svn_prepare_rejects_wrong_repo_dirty_update_and_existing_tag() {
  local fixture mode expected output rc
  for mode in wrong-repo wrong-url dirty-before update-failure dirty-after existing-tag tag-after-update tag-before-copy; do
    fixture=$(mktemp -d)
    make_svn_fixture "$fixture" || return 1
    case $mode in
      wrong-repo)
        expected='SVN repository root does not match Grand Media'
        output=$(FAKE_SVN_REPOSITORY_ROOT='https://plugins.svn.wordpress.org/not-grand-media' run_svn_prepare "$fixture" 2>&1)
        ;;
      wrong-url)
        expected='SVN working-copy URL does not match Grand Media'
        output=$(FAKE_SVN_URL='https://plugins.svn.wordpress.org/grand-media/trunk' run_svn_prepare "$fixture" 2>&1)
        ;;
      dirty-before) expected='SVN checkout is not clean'; output=$(FAKE_SVN_MODE=dirty-before run_svn_prepare "$fixture" 2>&1) ;;
      update-failure) expected='SVN update failed'; output=$(FAKE_SVN_MODE=update-failure run_svn_prepare "$fixture" 2>&1) ;;
      dirty-after) expected='SVN checkout is not clean'; output=$(FAKE_SVN_MODE=dirty-after run_svn_prepare "$fixture" 2>&1) ;;
      existing-tag) expected='SVN tag 1.25.1 already exists remotely'; output=$(FAKE_SVN_MODE=existing-tag run_svn_prepare "$fixture" 2>&1) ;;
      tag-after-update) expected='SVN tag 1.25.1 already exists remotely'; output=$(FAKE_SVN_MODE=tag-after-update run_svn_prepare "$fixture" 2>&1) ;;
      tag-before-copy) expected='SVN tag 1.25.1 already exists remotely'; output=$(FAKE_SVN_MODE=tag-before-copy run_svn_prepare "$fixture" 2>&1) ;;
    esac
    rc=$?
    test "$rc" -ne 0 || return 1
    assert_contains "$output" "$expected" || return 1
    ! jq -e '.stages.svn_prepare == "passed"' "$fixture/release-manifest.json" >/dev/null || return 1
    ! test -e "$fixture/svn-commit.marker" || return 1
  done
}

test_svn_prepare_rejects_unsafe_mismatched_or_unverified_manifest() {
  local fixture manifest output rc mode
  for mode in unsafe-version mismatched-version unverified missing-root verification-deployment verification-source; do
    fixture=$(mktemp -d)
    make_svn_fixture "$fixture" || return 1
    manifest="$fixture/release-manifest.json"
    case $mode in
      unsafe-version) jq '.version = "../1.25.1"' "$manifest" > "$manifest.next" ;;
      mismatched-version) jq '.version = "1.2.6"' "$manifest" > "$manifest.next" ;;
      unverified) jq 'del(.stages.verify)' "$manifest" > "$manifest.next" ;;
      missing-root) jq '.free_extracted_root = "/definitely/missing/grand-media"' "$manifest" > "$manifest.next" ;;
      verification-deployment) jq '.verification.deployment_id = "9999"' "$manifest" > "$manifest.next" ;;
      verification-source) jq '.verification.source_zip_sha256 = "ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff"' "$manifest" > "$manifest.next" ;;
    esac
    mv "$manifest.next" "$manifest" || return 1
    output=$(run_svn_prepare "$fixture" 2>&1)
    rc=$?
    test "$rc" -ne 0 || return 1
    case $mode in
      unsafe-version) assert_contains "$output" 'Release manifest version is unsafe' || return 1 ;;
      mismatched-version) assert_contains "$output" 'Plugin header version 1.25.1 does not match 1.2.6' || return 1 ;;
      unverified) assert_contains "$output" 'Release manifest is not ready for SVN preparation' || return 1 ;;
      missing-root) assert_contains "$output" 'Verified free root does not exist' || return 1 ;;
      verification-deployment|verification-source) assert_contains "$output" 'provenance' || return 1 ;;
    esac
    ! jq -e '.stages.svn_prepare == "passed"' "$manifest" >/dev/null || return 1
  done
}

test_svn_prepare_rejects_prepared_content_mismatch() {
  local fixture fake_bin output rc
  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  output=$(FAKE_SVN_COPY_CORRUPT=1 run_svn_prepare "$fixture" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Prepared SVN tag does not match verified free root' || return 1
  ! jq -e '.stages.svn_prepare == "passed"' "$fixture/release-manifest.json" >/dev/null || return 1

  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  fake_bin="$fixture/corrupt-rsync-bin"
  mkdir -p "$fake_bin"
  printf '%s' '#!/usr/bin/env bash
/usr/bin/rsync "$@" || exit 1
destination=${!#}
printf "corrupt\\n" >> "$destination/grand-media.php"
' > "$fake_bin/rsync"
  chmod +x "$fake_bin/rsync"
  output=$(PATH="$fake_bin:$PATH" run_svn_prepare "$fixture" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Prepared SVN trunk does not match verified free root' || return 1
  ! jq -e '.stages.svn_prepare == "passed"' "$fixture/release-manifest.json" >/dev/null
}

test_svn_prepare_manifest_is_atomic_and_retry_invalidates_stale_evidence() {
  local fixture manifest fake_bin output rc
  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  fake_bin="$fixture/fail-final-manifest-bin"
  mkdir -p "$fake_bin"
  printf '%s' '#!/usr/bin/env bash
count=0
test -f "$FAKE_MV_COUNT" && count=$(cat "$FAKE_MV_COUNT")
count=$((count + 1))
printf "%s" "$count" > "$FAKE_MV_COUNT"
test "$count" -eq 2 && exit 1
exec /bin/mv "$@"
' > "$fake_bin/mv"
  chmod +x "$fake_bin/mv"
  output=$(PATH="$fake_bin:$PATH" FAKE_MV_COUNT="$fixture/mv-count" run_svn_prepare "$fixture" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  ! jq -e '.stages.svn_prepare == "passed"' "$manifest" >/dev/null || return 1
  ! find "$fixture" -name 'release-manifest.json.tmp.*' -print -quit | grep -q . || return 1
  ! test -e "$fixture/svn-commit.marker" || return 1

  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  run_svn_prepare "$fixture" >/dev/null || return 1
  jq -e '.stages.svn_prepare == "passed"' "$manifest" >/dev/null || return 1
  output=$(run_svn_prepare "$fixture" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'SVN checkout is not clean' || return 1
  jq -e '(.stages | has("svn_prepare") | not) and (has("svn") | not)' "$manifest" >/dev/null || return 1
  ! test -e "$fixture/svn-commit.marker"
}

test_safe_pipeline_stops_without_svn_commit() {
  local fixture
  fixture=$(mktemp -d)
  make_svn_fixture "$fixture" || return 1
  run_svn_prepare "$fixture" >/dev/null || return 1
  ! grep -F 'commit' "$fixture/svn.log" >/dev/null || return 1
  ! test -e "$fixture/svn-commit.marker"
	test "$(cat "$fixture/svn-commit.count")" = 0
}

test_public_safe_sequence_stops_before_both_publications() {
  local fixture manifest fake_free checkout token source_zip source_size source_sha output rc
  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  fake_free="$fixture/fake-freemius-free.zip"
  mv "$fixture/grand-media-1.25.1-free.zip" "$fake_free" || return 1
  jq 'del(
      .freemius_upload_attempt,
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
    )' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" || return 1
  token=$(freemius_test_token "$fixture")
  PATH="$ROOT/tests/release/fakes:$PATH" \
    FAKE_CURL_LOG="$fixture/curl.log" FAKE_FREE_ZIP="$fake_free" \
    FAKE_CURL_SENTINEL="$token" FAKE_CURL_UPLOAD_MARKER="$fixture/freemius-upload.marker" \
    FAKE_CURL_UPLOAD_COUNT="$fixture/freemius-upload.count" \
    FAKE_CURL_MUTATION_MARKER="$fixture/freemius-release.marker" \
    FAKE_CURL_MUTATION_COUNT="$fixture/freemius-release.count" \
    FREEMIUS_API_TOKEN="$token" \
    "$CLI" freemius-upload --manifest "$manifest" || return 1
  PATH="$ROOT/tests/release/fakes:$PATH" FAKE_CURL_MODE=download-free \
    FAKE_CURL_LOG="$fixture/curl.log" FAKE_FREE_ZIP="$fake_free" \
    FAKE_CURL_SENTINEL="$token" FREEMIUS_API_TOKEN="$token" \
    "$CLI" freemius-download --manifest "$manifest" || return 1
  GRAND_MEDIA_ARTIFACT_PLUGIN_CHECK_CMD="$fixture/plugin-check" \
    GRAND_MEDIA_ARTIFACT_ACTIVATION_CMD="$fixture/activate-check" \
    "$CLI" verify --manifest "$manifest" >/dev/null || return 1

  checkout="$fixture/svn-checkout"
  mkdir -p "$checkout/.svn" "$checkout/trunk" "$checkout/tags" "$checkout/assets"
  printf 'old trunk file\n' > "$checkout/trunk/obsolete file.txt"
  PATH="$ROOT/tests/release/fakes:$PATH" \
    FAKE_SVN_CHECKOUT="$checkout" FAKE_SVN_LOG="$fixture/svn.log" \
    FAKE_SVN_STATE="$fixture/svn-state" FAKE_SVN_COMMIT_MARKER="$fixture/svn-commit.marker" \
    FAKE_SVN_MODE='' FAKE_SVN_REPOSITORY_ROOT='https://plugins.svn.wordpress.org' \
    FAKE_SVN_REVISION=4321 FAKE_SVN_REVISION_AFTER_UPDATE=4322 \
    FAKE_SVN_URL='https://plugins.svn.wordpress.org/grand-media' FAKE_SVN_VERSION=1.25.1 \
    FAKE_SVN_MISSING_PATH='trunk/obsolete file.txt' FAKE_SVN_COPY_CORRUPT=0 \
    "$CLI" svn-prepare --manifest "$manifest" --checkout "$checkout" >/dev/null || return 1

  jq -e '
    .stages.upload_pending == "passed" and
    .stages.download_free == "passed" and
    .stages.verify == "passed" and
    .stages.svn_prepare == "passed" and
    (.stages | has("svn_publish") | not) and
    (.stages | has("freemius_release") | not)
  ' "$manifest" >/dev/null || return 1
  test "$(cat "$fixture/freemius-upload.count")" = 1 || return 1
  ! grep -F 'PUT ' "$fixture/curl.log" >/dev/null || return 1
  ! grep -F 'commit ' "$fixture/svn.log" >/dev/null || return 1
  ! test -e "$fixture/freemius-release.marker" || return 1
  ! test -e "$fixture/svn-commit.marker" || return 1

  source_zip=$(jq -r '.source_zip' "$manifest") || return 1
  printf 'source B\n' >> "$source_zip"
  source_size=$(wc -c < "$source_zip" | tr -d '[:space:]') || return 1
  source_sha=$(shasum -a 256 "$source_zip" | awk '{print $1}') || return 1
  jq --arg source_sha "$source_sha" --argjson source_size "$source_size" \
    '.source_zip_sha256 = $source_sha | .source_zip_bytes = $source_size' \
    "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" || return 1
  output=$(PATH="$ROOT/tests/release/fakes:$PATH" \
    FAKE_CURL_LOG="$fixture/curl.log" FAKE_FREE_ZIP="$fake_free" \
    FAKE_CURL_SENTINEL="$token" FAKE_CURL_UPLOAD_MARKER="$fixture/freemius-upload.marker" \
    FAKE_CURL_UPLOAD_COUNT="$fixture/freemius-upload.count" FREEMIUS_API_TOKEN="$token" \
    "$CLI" freemius-upload --manifest "$manifest" 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  test "$(cat "$fixture/freemius-upload.count")" = 1 || return 1
  output=$(FAKE_CURL_MODE=release-flow run_freemius_release "$fixture" 'release freemius 1.25.1 0123456789abcdef0123456789abcdef01234567' 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  ! test -e "$fixture/freemius-release.count" || return 1
  ! test -e "$fixture/freemius-release.marker"
}

run_svn_publish() (
  local fixture=$1
  local confirmation=${2:-}
  mkdir -p "$fixture/svn-publish-tmp" || exit 1
  export TMPDIR="$fixture/svn-publish-tmp"
  export PATH="$ROOT/tests/release/fakes:$PATH"
  export FAKE_SVN_CHECKOUT="$fixture/svn-checkout"
  export FAKE_SVN_LOG="$fixture/svn.log"
  export FAKE_SVN_STATE="$fixture/svn-state"
  export FAKE_SVN_COMMIT_MARKER="$fixture/svn-commit.marker"
  export FAKE_SVN_COMMIT_COUNT="$fixture/svn-commit.count"
  export FAKE_SVN_MODE="${FAKE_SVN_MODE:-}"
  export FAKE_SVN_REPOSITORY_ROOT="${FAKE_SVN_REPOSITORY_ROOT:-https://plugins.svn.wordpress.org}"
  export FAKE_SVN_REVISION="${FAKE_SVN_REVISION:-4321}"
  export FAKE_SVN_REVISION_AFTER_UPDATE="${FAKE_SVN_REVISION_AFTER_UPDATE:-4322}"
  export FAKE_SVN_COMMIT_REVISION="${FAKE_SVN_COMMIT_REVISION:-5001}"
  export FAKE_SVN_URL="${FAKE_SVN_URL:-https://plugins.svn.wordpress.org/grand-media}"
  export FAKE_SVN_VERSION="${FAKE_SVN_VERSION:-1.25.1}"
  export FAKE_SVN_PAUSE_AFTER_MUTATION="${FAKE_SVN_PAUSE_AFTER_MUTATION:-}"
  exec "$CLI" svn-publish --manifest "$fixture/release-manifest.json" --confirm "$confirmation"
)

prepare_publish_fixture() {
  local fixture=$1
  make_svn_fixture "$fixture" || return 1
  run_svn_prepare "$fixture" >/dev/null || return 1
}

test_public_safe_stage_cli_uses_manifest_contracts() {
  local fixture manifest expected_free
  fixture=$(mktemp -d)
  make_freemius_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  expected_free="$(cd "$fixture" && pwd -P)/grand-media-1.25.1-free.zip"
  PATH="$ROOT/tests/release/fakes:$PATH" \
    FAKE_CURL_LOG="$fixture/curl.log" FAKE_FREE_ZIP="$fixture/free.zip" \
    FAKE_CURL_UPLOAD_MARKER="$fixture/freemius-upload.marker" FAKE_CURL_UPLOAD_COUNT="$fixture/freemius-upload.count" \
    FAKE_CURL_SENTINEL="$(freemius_test_token "$fixture")" \
    FREEMIUS_API_TOKEN="$(freemius_test_token "$fixture")" \
    "$CLI" freemius-upload --manifest "$manifest" || return 1
  PATH="$ROOT/tests/release/fakes:$PATH" FAKE_CURL_MODE=download-free \
    FAKE_CURL_LOG="$fixture/curl.log" FAKE_FREE_ZIP="$fixture/free.zip" \
    FAKE_CURL_SENTINEL="$(freemius_test_token "$fixture")" \
    FREEMIUS_API_TOKEN="$(freemius_test_token "$fixture")" \
    "$CLI" freemius-download --manifest "$manifest" || return 1
  test -f "$expected_free" || return 1
  jq -e --arg path "$expected_free" '
    .stages.upload_pending == "passed" and
    .stages.download_free == "passed" and
    .free_zip.path == $path
  ' "$manifest" >/dev/null || return 1
  ! grep -F 'PUT ' "$fixture/curl.log" >/dev/null || return 1

  fixture=$(mktemp -d)
  make_artifact_fixture "$fixture" || return 1
  GRAND_MEDIA_ARTIFACT_PLUGIN_CHECK_CMD="$fixture/plugin-check" \
    GRAND_MEDIA_ARTIFACT_ACTIVATION_CMD="$fixture/activate-check" \
    "$CLI" verify --manifest "$fixture/release-manifest.json" >/dev/null || return 1
  jq -e '.stages.verify == "passed"' "$fixture/release-manifest.json" >/dev/null
}

test_protected_commands_require_exact_confirmation_without_mutation() {
  local fixture manifest before after command confirmation cross_confirmation short_confirmation wrong_version_confirmation wrong_sha_confirmation output rc
  for command in svn-publish freemius-release; do
    case $command in
      svn-publish)
        cross_confirmation='release freemius 1.25.1 0123456789abcdef0123456789abcdef01234567'
        short_confirmation='publish svn 1.25.1 0123456789abcdef0123456789abcdef0123456'
        wrong_version_confirmation='publish svn 9.9.9 0123456789abcdef0123456789abcdef01234567'
        wrong_sha_confirmation='publish svn 1.25.1 ffffffffffffffffffffffffffffffffffffffff'
        ;;
      freemius-release)
        cross_confirmation='publish svn 1.25.1 0123456789abcdef0123456789abcdef01234567'
        short_confirmation='release freemius 1.25.1 0123456789abcdef0123456789abcdef0123456'
        wrong_version_confirmation='release freemius 9.9.9 0123456789abcdef0123456789abcdef01234567'
        wrong_sha_confirmation='release freemius 1.25.1 ffffffffffffffffffffffffffffffffffffffff'
        ;;
    esac
    for confirmation in \
      '' \
      wrong \
      "$short_confirmation" \
      "$wrong_version_confirmation" \
      "$wrong_sha_confirmation" \
      "$cross_confirmation"; do
      fixture=$(mktemp -d)
      if test "$command" = svn-publish; then
        prepare_publish_fixture "$fixture" || return 1
      else
        make_release_fixture "$fixture" || return 1
      fi
      manifest="$fixture/release-manifest.json"
      before=$(shasum -a 256 "$manifest" | awk '{ print $1 }') || return 1
      if test "$command" = svn-publish; then
        output=$(run_svn_publish "$fixture" "$confirmation" 2>&1)
      else
        output=$(run_freemius_release "$fixture" "$confirmation" 2>&1)
      fi
      rc=$?
      test "$rc" -ne 0 || return 1
      assert_contains "$output" 'Exact --confirm value required' || return 1
      after=$(shasum -a 256 "$manifest" | awk '{ print $1 }') || return 1
      test "$before" = "$after" || return 1
      ! test -e "$fixture/svn-commit.marker" || return 1
      ! test -e "$fixture/freemius-release.marker" || return 1
    done
  done
}

test_svn_publish_revalidates_and_commits_once() {
  local fixture manifest confirmation message output
  fixture=$(mktemp -d)
  prepare_publish_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  confirmation='publish svn 1.25.1 0123456789abcdef0123456789abcdef01234567'
  message='Release 1.25.1'
  output=$(run_svn_publish "$fixture" "$confirmation") || return 1
  test "$(cat "$fixture/svn-commit.count")" = 1 || return 1
  test "$(cat "$fixture/svn-state/commit-locale")" = C || return 1
  grep -Fx "commit . -m $message" "$fixture/svn.log" >/dev/null || return 1
  jq -e '
    .stages.svn_publish == "passed" and
    .svn.publication.revision == 5001 and
    (.svn.publication.timestamp | test("^[0-9]{4}-[0-9]{2}-[0-9]{2}T"))
  ' "$manifest" >/dev/null || return 1
  assert_contains "$output" 'svn_revision=5001'
}

test_svn_publish_rejects_invalid_or_stale_evidence_before_commit() {
  local fixture manifest mode output rc
  for mode in unsafe-version unsafe-sha missing-stage missing-upload-stage checkout-drift trunk-drift tag-drift root-drift zip-drift status-drift diff-drift tag-race wrong-repo wrong-url revision-drift svn-provenance; do
    fixture=$(mktemp -d)
    prepare_publish_fixture "$fixture" || return 1
    manifest="$fixture/release-manifest.json"
    case $mode in
      unsafe-version) jq '.version = "../1.25.1"' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" ;;
      unsafe-sha) jq '.git_sha = "ABCDEF0123456789abcdef0123456789abcdef01"' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" ;;
      missing-stage) jq 'del(.stages.verify)' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" ;;
      missing-upload-stage) jq 'del(.stages.upload_pending)' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" ;;
      checkout-drift) jq '.svn.checkout = "/tmp/not-recorded"' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" ;;
      trunk-drift) jq '.svn.trunk = (.svn.checkout + "/wrong-trunk")' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" ;;
      tag-drift) jq '.svn.tag = (.svn.checkout + "/tags/9.9.9")' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" ;;
      root-drift) printf 'drift\n' >> "$fixture/free-extracted/grand-media/runtime.txt" ;;
      zip-drift) printf 'drift\n' >> "$fixture/grand-media-1.25.1-free.zip" ;;
      status-drift|diff-drift|tag-race) export FAKE_SVN_MODE="publish-$mode" ;;
      wrong-repo) export FAKE_SVN_REPOSITORY_ROOT='https://plugins.svn.wordpress.org/not-grand-media' ;;
      wrong-url) export FAKE_SVN_URL='https://plugins.svn.wordpress.org/grand-media/trunk' ;;
      revision-drift) export FAKE_SVN_REVISION_AFTER_UPDATE=9999 ;;
      svn-provenance) jq '.svn.deployment_id = "9999"' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" ;;
    esac
    output=$(run_svn_publish "$fixture" 'publish svn 1.25.1 0123456789abcdef0123456789abcdef01234567' 2>&1)
    rc=$?
    unset FAKE_SVN_MODE FAKE_SVN_REPOSITORY_ROOT FAKE_SVN_URL FAKE_SVN_REVISION_AFTER_UPDATE
    test "$rc" -ne 0 || return 1
    ! assert_contains "$output" 'Unknown option' || return 1
    ! test -e "$fixture/svn-commit.marker" || return 1
    ! jq -e '.stages.svn_publish == "passed"' "$manifest" >/dev/null || return 1
  done
}

test_svn_publish_warns_after_commit_if_result_or_manifest_fails() {
  local fixture fake_bin output rc mode
  for mode in bad-revision manifest-failure; do
    fixture=$(mktemp -d)
    prepare_publish_fixture "$fixture" || return 1
    if test "$mode" = bad-revision; then
      output=$(FAKE_SVN_COMMIT_REVISION=bad run_svn_publish "$fixture" 'publish svn 1.25.1 0123456789abcdef0123456789abcdef01234567' 2>&1)
    else
      fake_bin="$fixture/fail-bin"
      mkdir -p "$fake_bin"
      printf '#!/usr/bin/env bash\nexit 1\n' > "$fake_bin/mv"
      chmod +x "$fake_bin/mv"
      output=$(PATH="$fake_bin:$PATH" run_svn_publish "$fixture" 'publish svn 1.25.1 0123456789abcdef0123456789abcdef01234567' 2>&1)
    fi
    rc=$?
    test "$rc" -ne 0 || return 1
    assert_contains "$output" 'SVN commit may already have succeeded; inspect before retrying' || return 1
    test "$(cat "$fixture/svn-commit.count")" = 1 || return 1
    ! jq -e '.stages.svn_publish == "passed"' "$fixture/release-manifest.json" >/dev/null || return 1
  done
}

test_protected_publications_are_independent_in_both_orders() {
  local fixture order output
  for order in svn-first freemius-first; do
    fixture=$(mktemp -d)
    prepare_publish_fixture "$fixture" || return 1
    case $order in
      svn-first)
        run_svn_publish "$fixture" 'publish svn 1.25.1 0123456789abcdef0123456789abcdef01234567' >/dev/null || return 1
        output=$(run_freemius_release "$fixture" 'release freemius 1.25.1 0123456789abcdef0123456789abcdef01234567') || return 1
        ;;
      freemius-first)
        run_freemius_release "$fixture" 'release freemius 1.25.1 0123456789abcdef0123456789abcdef01234567' >/dev/null || return 1
        output=$(run_svn_publish "$fixture" 'publish svn 1.25.1 0123456789abcdef0123456789abcdef01234567') || return 1
        ;;
    esac
    test "$(cat "$fixture/svn-commit.count")" = 1 || return 1
    test "$(cat "$fixture/freemius-release.count")" = 1 || return 1
    jq -e '.freemius.upload_release_mode == "pending" and .freemius.release_mode == "released" and .stages.svn_publish == "passed" and .stages.freemius_release == "passed"' "$fixture/release-manifest.json" >/dev/null || return 1
    test -n "$output" || return 1
  done
}

test_svn_publish_warns_after_commit_when_cleanup_fails() {
  local fixture output rc
  fixture=$(mktemp -d)
  prepare_publish_fixture "$fixture" || return 1
  output=$(FAKE_SVN_MODE=tamper-temp-marker-late run_svn_publish "$fixture" 'publish svn 1.25.1 0123456789abcdef0123456789abcdef01234567' 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'SVN commit may already have succeeded; inspect before retrying' || return 1
  test "$(cat "$fixture/svn-commit.count")" = 1 || return 1
  ! jq -e '.stages.svn_publish == "passed"' "$fixture/release-manifest.json" >/dev/null
}

test_protected_publications_warn_after_final_output_failure() {
  local fixture command bash_env output rc
  for command in svn-publish freemius-release; do
    fixture=$(mktemp -d)
    if test "$command" = svn-publish; then
      prepare_publish_fixture "$fixture" || return 1
    else
      make_release_fixture "$fixture" || return 1
    fi
    bash_env="$fixture/fail-final-output.bash"
    printf '%s\n' 'printf() {' '  case $1 in' "    'svn_revision=%s\\n'|'freemius_deployment=%s\\nrelease_mode=released\\n') return 1 ;;" '    *) builtin printf "$@" ;;' '  esac' '}' > "$bash_env"
    export BASH_ENV="$bash_env"
    if test "$command" = svn-publish; then
      output=$(run_svn_publish "$fixture" 'publish svn 1.25.1 0123456789abcdef0123456789abcdef01234567' 2>&1)
    else
      output=$(run_freemius_release "$fixture" 'release freemius 1.25.1 0123456789abcdef0123456789abcdef01234567' 2>&1)
    fi
    rc=$?
    unset BASH_ENV
    test "$rc" -ne 0 || return 1
    assert_contains "$output" 'may already have succeeded; inspect before retrying' || return 1
    if test "$command" = svn-publish; then
      test "$(cat "$fixture/svn-commit.count")" = 1 || return 1
    else
      test "$(cat "$fixture/freemius-release.count")" = 1 || return 1
    fi
  done
}

make_release_fixture() {
  local fixture=$1
  local free_size free_sha
  make_freemius_fixture "$fixture" || return 1
  free_size=$(wc -c < "$fixture/free.zip" | tr -d '[:space:]') || return 1
  free_sha=$(shasum -a 256 "$fixture/free.zip" | awk '{print $1}') || return 1
  jq --arg free_zip "$fixture/free.zip" --arg free_sha "$free_sha" --argjson free_size "$free_size" '
      .freemius_upload_attempt = {source_zip_sha256: .source_zip_sha256, timestamp: "2026-09-01T00:00:00Z"} |
      .freemius = {
        product_id: 20980,
        deployment_id: "9001",
        release_mode: "pending",
        upload_release_mode: "pending",
        source_zip_sha256: .source_zip_sha256,
        upload_endpoint_class: "POST /v1/products/20980/tags.json"
      } |
      .free_zip = {
        path: $free_zip,
        bytes: $free_size,
        sha256: $free_sha,
        deployment_id: "9001",
        source_zip_sha256: .source_zip_sha256,
        download_endpoint_class: "GET /v1/products/20980/tags/{deployment_id}.zip?is_premium=false"
      } |
      .verification = {
        deployment_id: "9001",
        source_zip_sha256: .source_zip_sha256,
        free_zip_sha256: $free_sha
      } |
      .stages.upload_pending = "passed" |
      .stages.download_free = "passed" |
      .stages.verify = "passed"' \
    "$fixture/release-manifest.json" > "$fixture/release-manifest.next" &&
    mv "$fixture/release-manifest.next" "$fixture/release-manifest.json"
}

run_freemius_release() (
  local fixture=$1
  local confirmation=${2:-}
  export PATH="$ROOT/tests/release/fakes:$PATH"
  export FAKE_CURL_LOG="$fixture/curl.log"
  export FAKE_CURL_MODE="${FAKE_CURL_MODE:-release-flow}"
  export FAKE_CURL_SENTINEL="$(freemius_test_token "$fixture")"
  export FAKE_CURL_MUTATION_MARKER="$fixture/freemius-release.marker"
  export FAKE_CURL_MUTATION_COUNT="$fixture/freemius-release.count"
  FREEMIUS_API_TOKEN=$(freemius_test_token "$fixture")
  export FREEMIUS_API_TOKEN
  export FAKE_CURL_PAUSE_AFTER_MUTATION="${FAKE_CURL_PAUSE_AFTER_MUTATION:-}"
  exec "$CLI" freemius-release --manifest "$fixture/release-manifest.json" --confirm "$confirmation"
)

test_freemius_release_refetches_pending_and_puts_once() {
  local fixture manifest confirmation token output
  fixture=$(mktemp -d)
  make_release_fixture "$fixture" || return 1
  manifest="$fixture/release-manifest.json"
  confirmation='release freemius 1.25.1 0123456789abcdef0123456789abcdef01234567'
  token=$(freemius_test_token "$fixture")
  output=$(run_freemius_release "$fixture" "$confirmation") || return 1
  grep -Fx 'GET /v1/products/20980/tags.json?fields=id,plugin_id,version,release_mode&count=50' "$fixture/curl.log" >/dev/null || return 1
  grep -Fx 'PUT /v1/products/20980/tags/9001.json body={"release_mode":"released"}' "$fixture/curl.log" >/dev/null || return 1
  test "$(cat "$fixture/freemius-release.count")" = 1 || return 1
  jq -e '
    .freemius.product_id == 20980 and
    .freemius.deployment_id == "9001" and
    .freemius.release_mode == "released" and
    .freemius.release_list_endpoint_class == "GET /v1/products/20980/tags.json?fields=id,plugin_id,version,release_mode&count=50" and
    .freemius.release_update_endpoint_class == "PUT /v1/products/20980/tags/{deployment_id}.json" and
    (.freemius.released_at | test("^[0-9]{4}-[0-9]{2}-[0-9]{2}T")) and
    .stages.freemius_release == "passed"
  ' "$manifest" >/dev/null || return 1
  ! grep -F "$token" "$manifest" "$fixture/curl.log" || return 1
  ! assert_contains "$output" "$token"
}

test_freemius_release_rejects_pre_mutation_invalid_evidence_with_zero_put() {
  local fixture manifest mode output rc
  for mode in unsafe-version unsafe-sha unsafe-id wrong-product missing-stage stale-mode verification-source refetch-id refetch-product refetch-version refetch-mode refetch-duplicate; do
    fixture=$(mktemp -d)
    make_release_fixture "$fixture" || return 1
    manifest="$fixture/release-manifest.json"
    case $mode in
      unsafe-version) jq '.version = "../1.25.1"' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" ;;
      unsafe-sha) jq '.git_sha = "short"' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" ;;
      unsafe-id) jq '.freemius.deployment_id = "9001?bad"' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" ;;
      wrong-product) jq '.freemius.product_id = 9999' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" ;;
      missing-stage) jq 'del(.stages.verify)' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" ;;
      stale-mode) jq '.freemius.release_mode = "released"' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" ;;
      verification-source) jq '.verification.source_zip_sha256 = "ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff"' "$manifest" > "$manifest.next" && mv "$manifest.next" "$manifest" ;;
      refetch-id|refetch-product|refetch-version|refetch-mode|refetch-duplicate) export FAKE_CURL_MODE="release-$mode" ;;
    esac
    output=$(run_freemius_release "$fixture" 'release freemius 1.25.1 0123456789abcdef0123456789abcdef01234567' 2>&1)
    rc=$?
    unset FAKE_CURL_MODE
    test "$rc" -ne 0 || return 1
    ! assert_contains "$output" 'Unknown option' || return 1
    ! test -e "$fixture/freemius-release.count" || return 1
    ! test -e "$fixture/freemius-release.marker" || return 1
    ! jq -e '.stages.freemius_release == "passed"' "$manifest" >/dev/null || return 1
  done
}

test_freemius_release_post_mutation_failures_have_exactly_one_put() {
  local fixture mode output rc
  for mode in put-mismatch put-failure; do
    fixture=$(mktemp -d)
    make_release_fixture "$fixture" || return 1
    output=$(FAKE_CURL_MODE="release-$mode" run_freemius_release "$fixture" 'release freemius 1.25.1 0123456789abcdef0123456789abcdef01234567' 2>&1)
    rc=$?
    test "$rc" -ne 0 || return 1
    assert_contains "$output" 'may already have succeeded; inspect before retrying' || return 1
    test "$(cat "$fixture/freemius-release.count")" = 1 || return 1
    test -e "$fixture/freemius-release.marker" || return 1
    ! jq -e '.stages.freemius_release == "passed"' "$fixture/release-manifest.json" >/dev/null || return 1
  done
}

test_freemius_release_warns_when_manifest_fails_after_put() {
  local fixture fake_bin output rc
  fixture=$(mktemp -d)
  make_release_fixture "$fixture" || return 1
  fake_bin="$fixture/fail-bin"
  mkdir -p "$fake_bin"
  printf '#!/usr/bin/env bash\nexit 1\n' > "$fake_bin/mv"
  chmod +x "$fake_bin/mv"
  output=$(PATH="$fake_bin:$PATH" run_freemius_release "$fixture" 'release freemius 1.25.1 0123456789abcdef0123456789abcdef01234567' 2>&1)
  rc=$?
  test "$rc" -ne 0 || return 1
  assert_contains "$output" 'Freemius release may already have succeeded; inspect before retrying' || return 1
  test "$(cat "$fixture/freemius-release.count")" = 1 || return 1
  ! jq -e '.stages.freemius_release == "passed"' "$fixture/release-manifest.json" >/dev/null
}

test_publication_interruptions_warn_and_never_retry_mutations() {
  local fixture command output_file ready pid operation_pid output rc count_file attempt
  for command in svn-publish freemius-release; do
    fixture=$(mktemp -d)
    output_file="$fixture/interruption-output"
    ready="$fixture/mutation-paused"
    if test "$command" = svn-publish; then
      prepare_publish_fixture "$fixture" || return 1
      FAKE_SVN_PAUSE_AFTER_MUTATION="$ready" \
        run_svn_publish "$fixture" 'publish svn 1.25.1 0123456789abcdef0123456789abcdef01234567' \
        >"$output_file" 2>&1 &
      count_file="$fixture/svn-commit.count"
    else
      make_release_fixture "$fixture" || return 1
      FAKE_CURL_PAUSE_AFTER_MUTATION="$ready" \
        run_freemius_release "$fixture" 'release freemius 1.25.1 0123456789abcdef0123456789abcdef01234567' \
        >"$output_file" 2>&1 &
      count_file="$fixture/freemius-release.count"
    fi
    pid=$!
    for attempt in {1..500}; do
      test -e "$ready" && break
      sleep 0.01
    done
    test -e "$ready" || { wait "$pid" 2>/dev/null || true; return 1; }
    operation_pid=$(cat "$ready") || { wait "$pid" 2>/dev/null || true; return 1; }
    kill -TERM "$operation_pid" || return 1
    wait "$pid"
    rc=$?
    output=$(cat "$output_file") || return 1
    test "$rc" -ne 0 || return 1
    test "$(cat "$count_file")" = 1 || return 1
    assert_contains "$output" 'may have succeeded' || return 1
    assert_contains "$output" 'do not retry' || return 1
    assert_contains "$output" 'inspect' || return 1
  done
}

test_operator_guide_documents_safe_and_protected_workflow() {
  local guide="$ROOT/docs/release-automation.md"
  test -f "$guide" || return 1
  grep -F 'Normal sequence stops before publication' "$guide" >/dev/null || return 1
  grep -F 'Grand Media is closed on WordPress.org pending review' "$guide" >/dev/null || return 1
  grep -F 'FREEMIUS_API_TOKEN' "$guide" >/dev/null || return 1
  grep -F 'GRAND_MEDIA_ARTIFACT_ACTIVATION_CMD' "$guide" >/dev/null || return 1
  grep -F 'bin/release/runners/test-local-activate' "$guide" >/dev/null || return 1
  grep -F 'bin/release/runners/test-local-plugin-check' "$guide" >/dev/null || return 1
  grep -F '<manifest-path>' "$guide" >/dev/null || return 1
  grep -F '<svn-checkout-path>' "$guide" >/dev/null || return 1
  grep -F 'freemius-transformations.txt' "$guide" >/dev/null || return 1
  grep -F 'svn diff --summarize' "$guide" >/dev/null || return 1
  grep -F ".free_extracted_root'" "$guide" >/dev/null || return 1
  ! grep -F '.verification.verified_root' "$guide" >/dev/null || return 1
  grep -F 'separate explicit approval' "$guide" >/dev/null || return 1
  grep -F 'read -r -s' "$guide" >/dev/null || return 1
  ! grep -E '^export FREEMIUS_API_TOKEN=' "$guide" >/dev/null || return 1
  ! grep -Eq 'Bearer[[:space:]]+[A-Za-z0-9._-]{12,}|api\.freemius\.com/.+token=' "$guide"
}

run_if_selected 'metadata' 'metadata reports 1.25.1' test_metadata_reports_1_25_1
run_if_selected 'preflight' 'preflight rejects version mismatch' test_preflight_rejects_version_mismatch
run_if_selected 'preflight' 'test bypass does not skip Git worktree checks' test_bypass_does_not_skip_git_worktree_checks
run_if_selected 'preflight' 'preflight rejects git status error' test_preflight_rejects_git_status_error
run_if_selected 'preflight' 'preflight rejects configured upstream mismatch' test_preflight_rejects_configured_upstream_mismatch
run_if_selected 'build' 'build creates verified source archive and manifest' test_build_creates_verified_source_archive_and_manifest
run_if_selected 'build-tracked-export' 'build archives tracked modules without ignored local modules' test_build_archives_tracked_modules_without_ignored_local_modules
run_if_selected 'build-source-basename' 'build passes grand-media source directory to dist-archive' test_build_passes_grand_media_source_directory_to_dist_archive
run_if_selected 'build-export-ownership' 'tracked export rejects hostile mktemp and preexisting markers' test_tracked_export_rejects_hostile_mktemp_and_preexisting_markers
run_if_selected 'build' 'build rejects missing .distignore' test_build_rejects_missing_distignore
run_if_selected 'build' 'build rejects invalid source archive root' test_build_rejects_invalid_source_archive_root
run_if_selected 'build' 'build rejects excluded development files' test_build_rejects_excluded_development_files
run_if_selected 'build' 'build propagates source gate failure' test_build_propagates_source_gate_failure
run_if_selected 'build' 'build rejects non-empty work dir without matching resume manifest' test_build_rejects_non_empty_work_dir_without_matching_resume_manifest
run_if_selected 'build' 'build rejects resume manifest with wrong source identity' test_build_rejects_resume_manifest_with_wrong_source_identity
run_if_selected 'build' 'build rejects resume manifest outside work dir' test_build_rejects_resume_manifest_outside_work_dir
run_if_selected 'build' 'build rejects external resume with empty explicit work dir' test_build_rejects_external_resume_with_empty_explicit_work_dir
run_if_selected 'build' 'build rejects resume without work dir' test_build_rejects_resume_without_work_dir
run_if_selected 'build' 'build accepts matching resume in nonempty work dir' test_build_accepts_matching_resume_in_nonempty_work_dir
run_if_selected 'build' 'build rejects work dir inside source repo' test_build_rejects_work_dir_inside_source_repo
run_if_selected 'build' 'build rejects resume after completed build' test_build_rejects_resume_after_build_completed
run_if_selected 'freemius' 'Freemius uploads pending and downloads explicit free ZIP' test_freemius_uploads_pending_and_downloads_explicit_free_zip
run_if_selected 'freemius' 'Freemius rejects absent token' test_freemius_rejects_absent_token
run_if_selected 'freemius' 'Freemius rejects HTTP failure' test_freemius_rejects_http_failure
run_if_selected 'freemius' 'Freemius rejects malformed upload JSON' test_freemius_rejects_malformed_upload_json
run_if_selected 'freemius' 'Freemius rejects upload product mismatch' test_freemius_rejects_upload_product_mismatch
run_if_selected 'freemius' 'Freemius rejects numeric upload product ID as ambiguous' test_freemius_rejects_numeric_upload_product_id_as_ambiguous
run_if_selected 'freemius' 'Freemius rejects upload version mismatch' test_freemius_rejects_upload_version_mismatch
run_if_selected 'freemius' 'Freemius rejects non-pending release mode' test_freemius_rejects_non_pending_release_mode
run_if_selected 'freemius' 'Freemius rejects download without ZIP magic' test_freemius_rejects_download_without_zip_magic
run_if_selected 'freemius' 'Freemius rejects malicious upload deployment ID' test_freemius_rejects_malicious_upload_deployment_id
run_if_selected 'freemius' 'Freemius rejects malicious download deployment ID' test_freemius_rejects_malicious_download_deployment_id
run_if_selected 'freemius' 'Freemius cleans partial on size hash and move failure' test_freemius_cleans_partial_on_size_hash_and_move_failure
run_if_selected 'freemius' 'Freemius cleans manifest temp on manifest move failure' test_freemius_cleans_manifest_temp_on_manifest_move_failure
run_if_selected 'freemius' 'Freemius cleans download final when manifest move fails' test_freemius_cleans_download_final_when_manifest_move_fails
run_if_selected 'freemius' 'Freemius refuses preexisting final download path' test_freemius_refuses_preexisting_final_download_path
run_if_selected 'freemius' 'Freemius keeps response cleanup trap until removal succeeds' test_freemius_keeps_response_cleanup_trap_until_removal_succeeds
run_if_selected 'freemius' 'Freemius escapes token and disables hostile curlrc' test_freemius_escapes_token_and_disables_hostile_curlrc
run_if_selected 'freemius' 'Freemius rejects token with CR or LF' test_freemius_rejects_token_with_cr_or_lf
run_if_selected 'freemius' 'Freemius upload attempt is one shot' test_freemius_upload_attempt_is_one_shot
run_if_selected 'freemius' 'Freemius upload ambiguity preserves attempt and blocks retry' test_freemius_upload_ambiguity_preserves_attempt_and_blocks_retry
run_if_selected 'freemius' 'Freemius download is one shot and bound to upload' test_freemius_download_is_one_shot_and_bound_to_upload
run_if_selected 'freemius' 'Freemius CLI uploads and downloads' test_freemius_cli_uploads_and_downloads
run_if_selected 'verify' 'verify accepts exact free artifact and records evidence' test_verify_accepts_exact_free_artifact_and_records_evidence
run_if_selected 'verify-order' 'verify installs before Plugin Check' test_verify_installs_before_plugin_check
run_if_selected 'test-local-runners' 'test.local activation runner installs exact artifact' test_test_local_activation_runner_installs_exact_artifact
run_if_selected 'test-local-runner-guards' 'test.local runners accept http test.local' test_test_local_runners_accept_http_test_local
run_if_selected 'test-local-runner-guards' 'test.local runners reject wrong home and siteurl without mutation' test_test_local_runners_reject_wrong_site_without_mutation
run_if_selected 'test-local-runner-guards' 'test.local activation runner refuses overwrite' test_test_local_activation_runner_refuses_overwrite
run_if_selected 'test-local-runners' 'test.local Plugin Check runner allows warnings' test_test_local_plugin_check_runner_allows_warnings
run_if_selected 'test-local-runners' 'test.local Plugin Check runner allows text success' test_test_local_plugin_check_runner_allows_text_success
run_if_selected 'test-local-runners' 'test.local Plugin Check runner rejects errors' test_test_local_plugin_check_runner_rejects_errors
run_if_selected 'test-local-runners' 'test.local Plugin Check runner rejects installed mismatch' test_test_local_plugin_check_runner_rejects_installed_mismatch
run_if_selected 'test-local-runner-guards' 'test.local Plugin Check runner rejects wrong version' test_test_local_plugin_check_runner_rejects_wrong_version
run_if_selected 'verify' 'verify rejects broken provenance and invalidates dependents' test_verify_rejects_broken_provenance_and_invalidates_dependents
run_if_selected 'publication-receipts' 'earlier stages preserve recorded SVN publication' test_earlier_stages_preserve_recorded_svn_publication
run_if_selected 'verify' 'verify requires complete Freemius identity before runners' test_verify_requires_complete_freemius_identity_before_runners
run_if_selected 'verify' 'verify rejects gatekeeper string' test_verify_rejects_gatekeeper_string
run_if_selected 'verify' 'verify rejects every literal distribution exclusion' test_verify_rejects_every_literal_distribution_exclusion
run_if_selected 'verify' 'verify distribution exclusions use root-relative paths' test_verify_distribution_exclusions_use_root_relative_paths
run_if_selected 'verify' 'verify invalidates stale passed evidence before failure' test_verify_invalidates_stale_passed_evidence_before_failure
run_if_selected 'verify' 'verify binds source ZIP size and hash to manifest' test_verify_binds_source_zip_size_and_hash_to_manifest
run_if_selected 'verify' 'verify fails closed when ZIP type inspection fails' test_verify_fails_closed_when_zip_type_inspection_fails
run_if_selected 'verify' 'verify rejects traversal and absolute entries' test_verify_rejects_traversal_and_absolute_entries
run_if_selected 'verify' 'verify rejects symbolic link entry before extraction' test_verify_rejects_symbolic_link_entry_before_extraction
run_if_selected 'verify' 'verify rejects multiple roots and malformed ZIP' test_verify_rejects_multiple_roots_and_malformed_zip
run_if_selected 'verify' 'verify rejects each version mismatch' test_verify_rejects_each_version_mismatch
run_if_selected 'verify' 'verify rejects PHP lint failure before Plugin Check' test_verify_rejects_php_lint_failure_before_plugin_check
run_if_selected 'verify' 'verify requires configured and passing Plugin Check' test_verify_requires_configured_and_passing_plugin_check
run_if_selected 'verify' 'verify requires configured and passing isolated activation' test_verify_requires_configured_and_passing_isolated_activation
run_if_selected 'verify' 'verify refuses preexisting extraction target' test_verify_refuses_preexisting_extraction_target
run_if_selected 'svn-prepare' 'SVN prepare mirrors verified root and never commits' test_svn_prepare_mirrors_verified_root_and_never_commits
run_if_selected 'svn-prepare' 'SVN prepare binds fresh ZIP to verification evidence' test_svn_prepare_binds_fresh_zip_to_verification_evidence
run_if_selected 'svn-prepare' 'SVN prepare rejects outside and nonempty fake mktemp' test_svn_prepare_rejects_outside_and_nonempty_fake_mktemp
run_if_selected 'svn-prepare' 'SVN prepare rejects root and plugin root fake mktemp' test_svn_prepare_rejects_root_and_plugin_root_fake_mktemp
run_if_selected 'svn-prepare' 'SVN prepare temp cleanup runs on TERM' test_svn_prepare_temp_cleanup_on_term
run_if_selected 'svn-prepare' 'SVN prepare cleanup rejects tampered marker' test_svn_prepare_cleanup_rejects_tampered_marker
run_if_selected 'svn-prepare' 'SVN prepare cleanup rejects appended marker newline' test_svn_prepare_cleanup_rejects_appended_marker_newline
run_if_selected 'svn-prepare' 'SVN prepare EXIT trap reports late marker tamper' test_svn_prepare_exit_trap_reports_late_marker_tamper
run_if_selected 'svn-prepare' 'SVN prepare requires svn command' test_svn_prepare_requires_svn_command
run_if_selected 'svn-prepare' 'SVN prepare rejects unsafe checkout layouts' test_svn_prepare_rejects_unsafe_checkout_layouts
run_if_selected 'svn-prepare' 'SVN prepare rejects checkout containing plugin Git root' test_svn_prepare_rejects_checkout_containing_plugin_git_root
run_if_selected 'svn-prepare' 'SVN prepare rechecks layout after update' test_svn_prepare_rechecks_layout_after_update
run_if_selected 'svn-prepare' 'SVN prepare rejects wrong repo dirty update and existing tag' test_svn_prepare_rejects_wrong_repo_dirty_update_and_existing_tag
run_if_selected 'svn-prepare' 'SVN prepare rejects unsafe mismatched or unverified manifest' test_svn_prepare_rejects_unsafe_mismatched_or_unverified_manifest
run_if_selected 'svn-prepare' 'SVN prepare rejects prepared content mismatch' test_svn_prepare_rejects_prepared_content_mismatch
run_if_selected 'svn-prepare' 'SVN prepare manifest is atomic and retry invalidates stale evidence' test_svn_prepare_manifest_is_atomic_and_retry_invalidates_stale_evidence
run_if_selected 'safe-pipeline' 'safe pipeline stops without SVN commit' test_safe_pipeline_stops_without_svn_commit
run_if_selected 'safe-pipeline' 'public safe sequence stops before both publications' test_public_safe_sequence_stops_before_both_publications
run_if_selected 'svn-temp-red' 'SVN prepare rejects outside and nonempty fake mktemp' test_svn_prepare_rejects_outside_and_nonempty_fake_mktemp
run_if_selected 'publish' 'public safe-stage CLI uses manifest contracts' test_public_safe_stage_cli_uses_manifest_contracts
run_if_selected 'publish' 'protected commands require exact confirmation without mutation' test_protected_commands_require_exact_confirmation_without_mutation
run_if_selected 'publish' 'SVN publish revalidates and commits once' test_svn_publish_revalidates_and_commits_once
run_if_selected 'publish' 'SVN publish rejects invalid or stale evidence before commit' test_svn_publish_rejects_invalid_or_stale_evidence_before_commit
run_if_selected 'publish' 'SVN publish warns after commit if result or manifest fails' test_svn_publish_warns_after_commit_if_result_or_manifest_fails
run_if_selected 'publish' 'protected publications are independent in both orders' test_protected_publications_are_independent_in_both_orders
run_if_selected 'publish' 'SVN publish warns after commit when cleanup fails' test_svn_publish_warns_after_commit_when_cleanup_fails
run_if_selected 'publish' 'protected publications warn after final output failure' test_protected_publications_warn_after_final_output_failure
run_if_selected 'publish' 'Freemius release refetches pending and PUTs once' test_freemius_release_refetches_pending_and_puts_once
run_if_selected 'publish' 'Freemius release rejects pre-mutation invalid evidence with zero PUT' test_freemius_release_rejects_pre_mutation_invalid_evidence_with_zero_put
run_if_selected 'publish' 'Freemius release post-mutation failures have exactly one PUT' test_freemius_release_post_mutation_failures_have_exactly_one_put
run_if_selected 'publish' 'Freemius release warns when manifest fails after PUT' test_freemius_release_warns_when_manifest_fails_after_put
run_if_selected 'publication-interruptions' 'publication interruptions warn and never retry mutations' test_publication_interruptions_warn_and_never_retry_mutations
run_if_selected 'publish' 'operator guide documents safe and protected workflow' test_operator_guide_documents_safe_and_protected_workflow
printf '%s passed; %s failed\n' "$PASS" "$FAIL"
test "$FAIL" -eq 0
