#!/usr/bin/env bash
set -eu

SUITE=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/release-cli.test.sh
CHECK_ROOT=$(mktemp -d "${TMPDIR:-/tmp}/release-fixture-check.XXXXXXXXXX")
readonly CHECK_ROOT
trap '/bin/rm -rf -- "$CHECK_ROOT"' EXIT
trap 'exit 129' HUP
trap 'exit 130' INT
trap 'exit 143' TERM
FAIL=0

# The suite must remove its own files without touching the caller's TMPDIR,
# following a fixture symlink, or losing the original exit/signal status.
mkdir -p "$CHECK_ROOT/outside"
printf 'keep\n' > "$CHECK_ROOT/outside/sentinel"
for mode in success failure HUP INT TERM; do
  parent="$CHECK_ROOT/$mode"
  mkdir -p "$parent"
  set +e
  TMPDIR="$parent" bash -c '
    source "$1" fixture-lifecycle-probe || exit 99
    fixture=$(mktemp -d "$TMPDIR/probe.XXXXXXXXXX") || exit 99
    printf "temporary\n" > "$fixture/data"
    ln -s "$2/outside" "$fixture/outside" || exit 99
    printf "created\n" > "$2/ready-$3"
    export TMPDIR="$2/outside"
    case $3 in
      success) exit 0 ;;
      failure) exit 23 ;;
      *) kill -s "$3" "$$"; exit 99 ;;
    esac
  ' bash "$SUITE" "$CHECK_ROOT" "$mode" > "$CHECK_ROOT/$mode.log" 2>&1
  status=$?
  set -e
  case $mode in
    success) expected=0 ;;
    failure) expected=23 ;;
    HUP) expected=129 ;;
    INT) expected=130 ;;
    TERM) expected=143 ;;
  esac
  if test "$status" -eq "$expected" && test -f "$CHECK_ROOT/ready-$mode" \
    && test -z "$(find "$parent" -mindepth 1 -print -quit)" \
    && test "$(cat "$CHECK_ROOT/outside/sentinel")" = keep; then
    printf 'ok - fixture cleanup after %s preserves caller files and status\n' "$mode"
  else
    printf 'not ok - fixture cleanup after %s (status %s, expected %s)\n' "$mode" "$status" "$expected"
    cat "$CHECK_ROOT/$mode.log"
    FAIL=$((FAIL + 1))
  fi
done

# Use a tiny real Git checkout: copying ignored/untracked files would bloat
# fixtures, while exporting HEAD would silently discard working-tree fixes.
source_repo="$CHECK_ROOT/source"
mkdir -p "$source_repo/graphify-out" "$source_repo/module" "$CHECK_ROOT/copy"
printf '/graphify-out/\n/module/local.zip\n' > "$source_repo/.gitignore"
printf 'committed\n' > "$source_repo/tracked file.txt"
git -C "$source_repo" init -q -b master
git -C "$source_repo" add .gitignore 'tracked file.txt'
git -C "$source_repo" -c user.email=release-test@example.test -c user.name='Release Test' commit -qm 'Fixture source'
printf 'working-tree edit\n' > "$source_repo/tracked file.txt"
printf 'ignored\n' > "$source_repo/graphify-out/cache"
printf 'ignored\n' > "$source_repo/module/local.zip"
printf 'untracked\n' > "$source_repo/local-only.txt"
if TMPDIR="$CHECK_ROOT/copy" bash -c '
  source "$1" fixture-lifecycle-probe || exit 99
  ROOT=$2
  fixture=$(mktemp -d "$TMPDIR/probe.XXXXXXXXXX") || exit 99
  make_clean_fixture "$fixture" || exit 99
  test "$(cat "$fixture/repo/tracked file.txt")" = "working-tree edit" || exit 1
  test ! -e "$fixture/repo/graphify-out" || exit 1
  test ! -e "$fixture/repo/module/local.zip" || exit 1
  test ! -e "$fixture/repo/local-only.txt" || exit 1
  test -z "$(git -C "$fixture/repo" status --porcelain)"
' bash "$SUITE" "$source_repo" > "$CHECK_ROOT/copy.log" 2>&1; then
  printf 'ok - fixture copies tracked working-tree files only\n'
else
  printf 'not ok - fixture copies tracked working-tree files only\n'
  cat "$CHECK_ROOT/copy.log"
  FAIL=$((FAIL + 1))
fi

printf '%s fixture lifecycle checks failed\n' "$FAIL"
test "$FAIL" -eq 0
