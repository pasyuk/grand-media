# Release automation operator guide

This guide stages a Grand Media release for review. It does not authorize or
perform a release. The processed Freemius free ZIP, rather than a source ZIP,
is the authoritative artifact for WordPress.org SVN preparation.

## Prerequisites

Run the safe sequence only from a clean, current `master` checkout with the
release version already present in `grand-media.php` and `readme.txt`. Choose a
new empty local work directory. The manifest path is `<manifest-path>`;
the owner-provided WordPress.org working-copy path is
`<svn-checkout-path>` and, for this release workflow, exactly:

```text
/Users/simka/Documents/WP Plugins/grand-media-build/grand-media-svn
```

Set only runtime values in the current shell. Enter the Freemius token without
putting it in a command argument or shell history:

```bash
read -r -s FREEMIUS_API_TOKEN
export FREEMIUS_API_TOKEN
version='<release-version>'
work_dir='<new-empty-local-work-directory>'
manifest="$work_dir/release-manifest.json"
git_sha="$(git rev-parse HEAD)"
```

Do not write the token to the manifest, a file, or a command line. Confirm that
`$git_sha` is the full 40-character SHA recorded by the manifest before using
either protected command.

## Safe staging sequence

Run every command in this order. The verification runners below are explicit
local test commands; they do not replace the required human review of their
logs or a browser smoke test of the installed processed artifact.

```bash
bin/grand-media-release preflight --repo . --version "$version"
bin/grand-media-release build --repo . --version "$version" --work-dir "$work_dir"
bin/grand-media-release freemius-upload --manifest "$manifest"
bin/grand-media-release freemius-download --manifest "$manifest"
GRAND_MEDIA_ARTIFACT_PLUGIN_CHECK_CMD="$PWD/bin/release/runners/test-local-plugin-check" \
GRAND_MEDIA_ARTIFACT_ACTIVATION_CMD="$PWD/bin/release/runners/test-local-activate" \
  bin/grand-media-release verify --manifest "$manifest"
bin/grand-media-release svn-prepare --manifest "$manifest" --checkout "/Users/simka/Documents/WP Plugins/grand-media-build/grand-media-svn"
```

The build binds the manifest to the full current Git SHA, revalidates that SHA
against `HEAD` and the configured upstream, and gives `wp dist-archive` a
temporary tracked Git export rather than the live checkout. `.distignore` is
applied to that export, so tracked runtime modules remain while ignored local
module trees and ZIPs cannot enter the source archive.

Normal sequence stops before publication. Immediately after it completes,
Freemius remains pending and the SVN working copy remains uncommitted. Do not
interpret that staged state as either destination having published.

## Inspect the staged result

Review the manifest JSON, the transformation summary, and the artifact gate
logs before requesting any publication approval:

```bash
jq . "$manifest"
cat "$work_dir/freemius-transformations.txt"
cat "$(jq -r '.verification.activation.log' "$manifest")"
cat "$(jq -r '.verification.plugin_check.log' "$manifest")"
svn status "/Users/simka/Documents/WP Plugins/grand-media-build/grand-media-svn"
svn diff --summarize "/Users/simka/Documents/WP Plugins/grand-media-build/grand-media-svn"
svn diff "/Users/simka/Documents/WP Plugins/grand-media-build/grand-media-svn"
```

The verified root must recursively equal both prepared SVN trees, excluding
only their `.svn` metadata:

```bash
verified_root="$(jq -r '.free_extracted_root' "$manifest")"
svn_checkout="$(jq -r '.svn.checkout' "$manifest")"
diff -qr -x .svn "$verified_root" "$svn_checkout/trunk"
diff -qr -x .svn "$verified_root" "$svn_checkout/tags/$version"
```

Review the full diffs, not only their summary. The WordPress.org `assets`
directory is not release content and must remain untouched.

## Protected publication commands

These are two independent external actions, not a combined release. Each
requires a separate explicit approval from the owner immediately before its
own execution, after the inspection above. An approval for one command does
not approve the other.

```bash
bin/grand-media-release svn-publish --manifest "$manifest" --confirm "publish svn $version $git_sha"
bin/grand-media-release freemius-release --manifest "$manifest" --confirm "release freemius $version $git_sha"
```

Ask for approval immediately before running `svn-publish`; execute it only
after that approval. Independently ask for approval immediately before running
`freemius-release`; execute it only after that approval. The order is chosen
by the owner, and both commands revalidate their recorded evidence just before
their one external mutation.

## Failure recovery and publication state

Never retry a command that reports an ambiguous post-mutation failure. Inspect
the live service first: a failed response, parsing step, manifest write, or
final output can occur after the external action succeeded. Record what the
remote Freemius deployment and WordPress.org repository actually show, then
obtain a new owner decision.

- For a pending Freemius deployment, inspect the recorded deployment in the
  Freemius dashboard/API before any cleanup. Do not create another upload just
  because the local command outcome is unclear.
- If SVN preparation has not been committed and must be abandoned, discard the
  local preparation only after confirming that no SVN revision exists; then use
  a clean checkout or recreate the checkout and stage again from the verified
  artifact.
- Published SVN tags are immutable release history. Do not overwrite or reuse
  them; fix an already published artifact with a new corrective version.
- A published SVN revision does not prove WordPress.org distribution. Inspect
  the plugin directory separately, including its review/availability state.
  Grand Media is closed on WordPress.org pending review, so that distribution
  check is particularly important.
- A successful Freemius release does not prove SVN publication, and an SVN
  revision does not prove the Freemius release. Inspect each destination
  independently after any ambiguous state.

The automated tests use fake Freemius, SVN, and local runners. They prove
local command contracts only; live-service behavior, actual Freemius
publication, SVN commit, and WordPress.org distribution remain unproven until
the owner separately authorizes and inspects those actions.
