# Architecture

This page is a current-code map for Gmedia Gallery. Re-check code before using it for implementation decisions.

## Entry Point

- `grand-media.php` is the plugin entry point.
- `gmg_fs()` initializes Freemius from `vendor/freemius/start.php`.
- Class `Gmedia` wires the plugin lifecycle.
- `plugins_loaded` calls `Gmedia::start_plugin()`.
- Activation/deactivation hooks call installer/deactivation paths in `config/setup.php`.

## Core Lifecycle

`Gmedia::__construct()`:

- Checks WordPress requirements.
- Loads `config.php`.
- Loads saved options and defines constants/tables.
- Loads core libraries:
  - `inc/core.php`
  - `inc/db.connect.php`
  - `inc/permalinks.php`
- Registers scripts, admin/frontend hooks, activation/deactivation, cron schedules, and plugin links.

`Gmedia::start_plugin()`:

- Loads translations and shared helpers.
- Loads shortcode handling.
- Branches into admin or frontend behavior.

## Data Model

The plugin uses custom database tables rather than only WordPress posts:

- `gmedia`
- `gmedia_meta`
- `gmedia_term`
- `gmedia_term_meta`
- `gmedia_term_relationships`
- `gmedia_log`

Key files:

- `config/setup.php`: default options, install SQL, capabilities, activation/deactivation.
- `config/update.php`: database upgrade functions.
- `inc/db.connect.php`: `GmediaDB` data access methods.
- `inc/core.php`: `GmediaCore` utility methods, upload paths, capabilities, media helpers.

## Admin Surface

Key files:

- `admin/admin.php`: admin menu, admin shell, assets, block editor assets.
- `admin/class.processor.php`: base processor and admin routing.
- `admin/processor/*.php`: page-specific processors.
- `admin/pages/**`: admin page templates.
- `admin/ajax.php`: admin and frontend AJAX handlers.

Main admin pages:

- Library: `GrandMedia`
- Add/Import Files: `GrandMedia_AddMedia`
- Tags: `GrandMedia_Tags`
- Categories: `GrandMedia_Categories`
- Albums: `GrandMedia_Albums`
- Galleries: `GrandMedia_Galleries`
- Modules: `GrandMedia_Modules`
- Settings: `GrandMedia_Settings`

## Frontend Surface

Key files:

- `inc/shortcodes.php`: `[gmedia]` and `[gm]` shortcodes.
- `inc/frontend.filters.php`: frontend metadata, rendering filters, and display helpers.
- `template/**`: full-page templates for albums, galleries, tags, categories, single items, and comments.
- `inc/widget.php`: widget support.
- `inc/post-metabox.php`: editor/post integration.
- `inc/media-upload.php`: WordPress media modal integration.

## Blocks

Block/editor integration is loaded from admin code:

- `admin/admin.php` hooks `enqueue_block_editor_assets`.
- `admin/assets/js/gmedia-block.js` is the block script.
- `admin/assets/css/gmedia-block.css` is the block style.

WordPress.org currently lists these blocks:

- Gmedia Gallery
- Gmedia Album
- Gmedia Category
- Gmedia Tag

## Modules

Gallery modules live under `module/*`.

Typical module files:

- `index.php`
- `init.php`
- `settings.php`
- optional `css/`
- optional `js/`
- optional images/assets

Shared module behavior:

- `inc/module.options.php`
- `admin/pages/modules/**`
- module dependency loading via the main plugin asset paths.

Do not refactor module skins in bulk. Treat each module as a separate compatibility surface.

## Assets And Dependencies

Bundled assets live under `assets/**` and `admin/assets/**`.

Important dependency areas:

- Bootstrap/Popper
- Selectize
- CamanJS image editor
- noUiSlider
- Tempus Dominus
- WaveSurfer
- jQuery File Tree
- minicolors
- Swiper
- PhotoSwipe
- Magnific Popup
- Fancybox
- Font Awesome
- plupload queue
- Spectrum

Dependency updates should start with an inventory issue and module-specific smoke checks.

## Freemius And Licensing

Use [Freemius And Licensing](freemius-and-licensing.md) as the source of truth.

High-level flow:

- Freemius initializes in `grand-media.php`.
- License helper functions live in `inc/functions.php`.
- License UI lives in `admin/pages/settings/tpl/license.php`.
- Settings processing lives in `admin/processor/class.processor.settings.php`.
- Module access checks use `gmedia_has_premium_license()`.

## External And Optional Integrations

- Yoast sitemap integration uses `inc/sitemap.php` when relevant.
- App/mobile service helpers live under `app/**`.
- Image EXIF handling uses bundled PEL under `inc/pel/**`.

## Verification Rule

This document is a navigation aid, not proof. Before changing a behavior, verify the exact current code path with `rg`, file reads, and local WordPress smoke tests.
