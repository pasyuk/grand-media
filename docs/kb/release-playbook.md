# Release Playbook

Gmedia Gallery releases should be small, issue-linked, and verified before publishing.

## Cadence

- Normal cadence: maximum two plugin updates per week.
- Exceptions: confirmed security issue, fatal error on supported WordPress/PHP, data loss, or upload-blocking regression.

## Release Channel

1. Prepare and verify changes in GitHub.
2. Send the release/build through Freemius.
3. Download the Freemius-processed archive.
4. Publish the special WordPress.org version from that processed archive to SVN.

Do not publish a raw GitHub archive to WordPress.org SVN unless the release process is explicitly changed.

## Compatibility Baseline

- Target real support baseline: WordPress 6.0+ and PHP 7.4+.
- Current public headers/readme may still declare older minimums until a dedicated compatibility issue updates them.
- Do not update `Requires at least`, `Requires PHP`, or `Tested up to` by guesswork.

## Pre-Release Checklist

- GitHub issues are linked to the release changes.
- PHP syntax lint passes from the plugin root:

```bash
find . -type f -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l
```

- Local admin smoke tests pass.
- Frontend smoke tests pass for at least one existing shortcode and one Gmedia block when available.
- Upload/import smoke tests pass if upload, import, media, dependencies, or admin JavaScript were touched.
- Module smoke tests pass for every touched module.
- Freemius/license checks pass if licensing, modules, update flow, or premium access were touched.
- `readme.txt` changelog is updated.
- `changelog.txt` is updated if used for the release.
- WordPress.org metadata is checked against the actual release artifact.

## Baseline Local Verification

Use this checklist for maintenance slices before a release candidate exists. If a slice does not touch a surface, record it as "not touched" rather than testing unrelated flows.

### Environment

- Local site: `https://wp-dev.loc/`.
- Plugin admin page: `https://wp-dev.loc/wp-admin/admin.php?page=GrandMedia`.
- Plugin root: `/Users/simka/_LOCAL_/wp-dev/app/public/wp-content/plugins/grand-media`.
- Confirm PHP syntax:

```bash
find . -type f -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l
```

- Record local versions:

```bash
php -v
```

- Use `wp-dev` for WP-CLI checks against the Local by WP Engine `wp-dev.loc` site. This wrapper runs the official WP-CLI Phar through the site's Local PHP runtime and Local `php.ini`, so PHP uses the correct MySQL socket:

```bash
wp-dev --info
wp-dev core version
wp-dev plugin status grand-media
```

Current expected baseline:

```text
WP-CLI version: 2.12.0
PHP binary: /Users/simka/Library/Application Support/Local/lightning-services/php-8.4.10+0/bin/darwin-arm64/bin/php
php.ini used: /Users/simka/Library/Application Support/Local/run/OTxPEpfwF/conf/php/php.ini
WordPress core: 7.0
Gmedia Gallery: Active, 1.25.0
```

### Activation

Use one of these paths:

- Preferred:

```bash
wp-dev plugin activate grand-media
```

- Fallback: activate Gmedia Gallery in WordPress admin under Plugins.

Pass criteria:

- Plugin activates without a fatal error.
- WordPress admin remains reachable.
- Gmedia menu appears in the admin sidebar.

### Admin Pages

Open each page and check for fatal errors, blank pages, obvious PHP warnings, and browser console errors.

- Library: `https://wp-dev.loc/wp-admin/admin.php?page=GrandMedia`
- Add/Import Files: `https://wp-dev.loc/wp-admin/admin.php?page=GrandMedia_AddMedia`
- Tags: `https://wp-dev.loc/wp-admin/admin.php?page=GrandMedia_Tags`
- Categories: `https://wp-dev.loc/wp-admin/admin.php?page=GrandMedia_Categories`
- Albums: `https://wp-dev.loc/wp-admin/admin.php?page=GrandMedia_Albums`
- Galleries: `https://wp-dev.loc/wp-admin/admin.php?page=GrandMedia_Galleries`
- Modules: `https://wp-dev.loc/wp-admin/admin.php?page=GrandMedia_Modules`
- Settings: `https://wp-dev.loc/wp-admin/admin.php?page=GrandMedia_Settings`

Pass criteria:

- Page loads inside WordPress admin.
- Existing media/gallery/module data is visible where expected.
- No critical console errors block interaction.
- No visible PHP warnings/notices are shown in the admin UI.

### Upload And Import

Run this when upload, import, admin JavaScript, media handling, dependencies, or permissions are touched.

1. Open Add/Import Files.
2. Select a small JPG file.
3. Confirm the upload controls become usable.
4. Start upload.
5. Confirm the file appears in Gmedia Library.
6. If terms were touched, assign an album/category/tag during upload and verify it persists.

Pass criteria:

- Upload starts.
- Upload completes without JavaScript/network errors.
- New item appears in the Library.
- Assigned terms persist after page reload.

### Galleries

Run this when gallery query, module settings, shortcode/block insertion, or frontend output is touched.

1. Open Galleries.
2. Open or create a test gallery.
3. Confirm module selection/settings load.
4. Save without changing premium/free behavior.
5. View a page/post containing the gallery shortcode or block.

Pass criteria:

- Gallery settings load and save.
- Frontend gallery renders.
- Images open or navigate according to the selected module behavior.
- No unrelated module settings are reset.

### Frontend Shortcode

Use an existing gallery shortcode if available.

Pass criteria:

- The shortcode renders a gallery on the frontend.
- Public users can view public galleries.
- Draft/private galleries remain restricted according to current behavior.
- Browser console does not show blocking JavaScript errors.

### Block Editor

Run this when block assets, admin assets, shortcode insertion, or editor integration is touched.

1. Open a test post/page in the block editor.
2. Insert each available Gmedia block when practical:
   - Gmedia Gallery
   - Gmedia Album
   - Gmedia Category
   - Gmedia Tag
3. Save/update the post.
4. View the frontend.

Pass criteria:

- Blocks are available in the editor.
- Editor does not crash.
- Saved content renders on the frontend.

### Modules

Run this when a module, shared module option, or shared frontend dependency is touched.

Pass criteria:

- Touched module preview loads.
- A gallery using the touched module renders on frontend.
- Module-specific controls still work.
- If a premium module is involved, license behavior is unchanged unless the issue explicitly changes it.

### Licensing

Run this when Freemius, legacy license logic, module install/update behavior, premium buttons, or settings license UI are touched.

Check scenarios:

- No license.
- Legacy license option present.
- Freemius license active.
- Both legacy and Freemius license data present.

Pass criteria:

- `gmedia_has_premium_license()` result matches intended access.
- `gmedia_get_license_type()` returns the expected source.
- Premium buttons and notices match access state.
- Existing legacy access is not broken unintentionally.

For controlled local state changes, use the manual harness documented in [Freemius And Licensing](freemius-and-licensing.md):

```bash
wp-dev eval-file tests/manual/license-state-matrix.php snapshot /private/tmp/gmedia-license-state-backup.json
wp-dev eval-file tests/manual/license-state-matrix.php current
wp-dev eval-file tests/manual/license-state-matrix.php restore /private/tmp/gmedia-license-state-backup.json
```

Backup files can contain serialized account/license state. Keep them outside the repo and restore the baseline after every scenario.

### Cleanup

After local verification:

- Record exact checks performed in the issue or PR.
- Record skipped checks with a reason.
- Record local WordPress/PHP/plugin versions.
- Record any console/network errors, even if they are not part of the current slice.

## Local Tooling Notes

- Generic Homebrew PHP was verified as PHP 8.5.7 on 2026-06-15, but it does not use the Local by WP Engine MySQL socket by default.
- The `wp-dev` wrapper was installed on 2026-06-15 at `~/.local/bin/wp-dev`.
- `wp-dev` uses `~/.local/bin/wp-cli.phar`, Local site id `OTxPEpfwF`, and the `wp-dev.loc` Local PHP config at `~/Library/Application Support/Local/run/OTxPEpfwF/conf/php/php.ini`.
- Direct PHP or WP-CLI commands that touch the Local MySQL socket may need unsandboxed execution in Codex because the socket lives outside the plugin workspace.

## Rollback Notes

For every release, keep:

- Git commit SHA.
- Freemius processed archive reference.
- WordPress.org SVN revision.
- List of issues included.
- Manual smoke tests performed.
- Known risks and rollback trigger.
