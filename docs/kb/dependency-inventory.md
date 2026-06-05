# Dependency Inventory

Last checked: 2026-06-05

This page tracks bundled JavaScript and CSS dependencies that should be understood before any compatibility, security, or UI refresh work. It is an inventory only: no upgrades were made while creating this document.

## Rules

- Re-check current code before upgrading any dependency.
- Prefer one dependency upgrade per ticket unless a pair must move together.
- For user-facing dependencies, test at least one real gallery on the frontend.
- For admin dependencies, test the exact admin page and mode that enqueues the asset.
- Treat locally present ignored module folders as module package inventory, not committed core source, until their release ownership is clarified.
- Do not publish detailed security claims from this page without validation.

## Core And Admin Dependencies

| Dependency | Bundled version | Registered/enqueued from | Surface | Current usage | Recommendation |
| --- | --- | --- | --- | --- | --- |
| Bootstrap | 5.1.3 | `grand-media.php`, `admin/admin.php`, `inc/media-upload.php` | Admin, media popup | Base admin styles/scripts; `bootstrap.bundle` also includes Popper for Bootstrap components. | Keep short-term. Upgrade only with full admin smoke tests because many plugin screens depend on Bootstrap markup and behaviors. |
| Popper | 2.11.2 | `admin/admin.php` | Admin | Separate Popper handle for edit screens using Tempus Dominus. Bootstrap bundle also contains Popper. | Keep short-term. Review duplicate Popper loading before any Bootstrap/Tempus Dominus upgrade. |
| Selectize | 0.13.5 | `admin/admin.php`, `inc/media-upload.php` | Admin, media popup | Term/tag selectors and media upload popup fields. | Review/update after Add Media, edit media, and media popup tests exist. Replacement would be a separate UX ticket. |
| Spectrum | 1.8.0 | `admin/admin.php` | Admin | Color picker on gallery/module settings screens. | Review together with minicolors. Update only after module settings smoke tests. |
| CamanJS | 4.1.2 | `admin/admin.php` | Admin image editor | Image editor when `gmediablank=image_editor`. | High-risk legacy dependency. Keep until image editor behavior is covered, then evaluate replacement or isolation. |
| noUiSlider | 6.1.0 | `admin/admin.php`, `assets/image-editor/js/jquery.nouislider.min.js` | Admin image editor | Slider controls inside the image editor. | Keep with CamanJS for now. Any upgrade belongs with the image editor test/replacement work. |
| Tempus Dominus | 6.0.0 in WordPress registration, header says `v6.0.0-beta5.1` | `admin/admin.php` | Admin | Date/time fields on media edit and album edit screens. | Verify exact source/version before upgrade. Pair with Popper and date-field tests. |
| WaveSurfer | Admin registered as 1.1.5, frontend registered as 1.2.8; bundled file has no obvious version header | `admin/admin.php`, `grand-media.php` | Admin, frontend modules | Admin audio waveform behavior and WaveSurfer gallery modules. | Reconcile version mismatch before upgrade. Test admin audio metadata and frontend WaveSurfer module. |
| jQuery File Tree | Registered as 1.0.1, file header says 1.01 | `admin/admin.php` | Admin import | Server import folder browser. | High-priority review with import-path hardening and upload/import smoke tests. |
| jQuery Minicolors | 0.9.13 in WordPress registration; file header has no version | `admin/admin.php` | Admin | Color controls on gallery/module settings screens. | Review with Spectrum. Replacement/update should wait for module settings smoke tests. |
| plupload queue | 2.3.9 in WordPress registration; bundled wrapper file has no version header | `admin/admin.php`, `inc/media-upload.php` | Admin upload, media popup | Upload queue UI around WordPress `plupload`. | High-priority compatibility review with upload-flow work. Do not change without Add Media and popup upload tests. |
| Font Awesome | 6.1.1 | `grand-media.php` | Admin | Admin icon font used by Gmedia admin UI. | Keep short-term. Update with visual admin smoke tests. |

## Frontend And Module Dependencies

| Dependency | Bundled version | Registered/enqueued from | Surface | Current usage | Recommendation |
| --- | --- | --- | --- | --- | --- |
| Swiper | 5.3.6 | `grand-media.php`; module `dependencies` fields | Frontend modules | Used by `phototravlr`, `desire`, `photomania`, `c1slider`, and codecanyon `phototravlr` modules. | Upgrade module-by-module. Swiper major versions have API and markup changes, so do not bundle this into unrelated releases. |
| PhotoSwipe | 3.0.5 | `grand-media.php` | Frontend lightbox | Registered globally for modules that depend on the `photoswipe` handle. | Legacy lightbox. Update only after identifying exact modules and frontend gallery smoke tests. |
| Magnific Popup | 1.1.0 in `assets/mag-popup` header | Asset folder exists; codecanyon `cubik` declares `magnific-popup` dependency; no core `wp_register_script` for this handle was found | Frontend modules | Several ignored local module scripts include Magnific Popup code headers, and codecanyon `cubik` declares the handle. | Verify module packaging. Either register the handle intentionally or remove stale dependency declarations if module scripts vendor the code directly. |
| Fancybox | 1.3.4 | `grand-media.php` | Frontend lightbox | Registered as `fancybox` with `easing` dependency. | Legacy-only. Replace only after identifying affected gallery modules and creating visual regression checks. |
| WaveSurfer | Admin 1.1.5, frontend 1.2.8 | `grand-media.php`; `wavesurfer` module dependencies | Frontend audio modules | Used by `wavesurfer` and codecanyon `wavesurfer` modules. | Same action as admin WaveSurfer: reconcile versions first, then test admin and frontend audio paths. |
| jPlayer | 2.6.4 | `grand-media.php`; `jq-mplayer` module dependency | Frontend audio module | Used by `jq-mplayer`. | Keep until audio module ownership is clarified. Consider replacement only as a dedicated module modernization ticket. |
| Mousetrap | 1.5.2 | `grand-media.php`; module `dependencies` fields | Frontend modules | Keyboard controls in `phototravlr`, `desire`, `photomania`, and codecanyon `phototravlr`. | Keep short-term. Upgrade with keyboard navigation tests for those modules. |
| Velocity | 1.4.1 | `grand-media.php` | Frontend modules | Registered globally for frontend module use. | Inventory exact module usage before changing. |
| jQuery Easing | 1.3.0 | `grand-media.php` | Frontend lightbox | Dependency of Fancybox registration. | Keep with Fancybox until Fancybox is replaced or removed. |
| WordPress media scripts | WordPress core-provided | `wp-videoplayer` module dependency field | Frontend module | `wp-util`, `backbone`, and `mediaelement` handles are declared by `wp-videoplayer`. | Keep as WordPress-provided dependencies. Verify against the latest supported WordPress before module changes. |
| SWFObject / SWFAddress | No bundled JS files found in current repo scan | `green-style` module dependency field; `template/functions.php` deregisters `swfaddress` for iframe mode | Legacy/Flash module | `green-style` references Flash/SWF behavior. | Treat as legacy. Create a dedicated ticket to decide whether GreenStyle Pro remains supported or should be retired/hidden. |

## Module Dependency Handles Observed

Current local scan with ignored module folders included found these non-empty module dependency declarations:

| Handles | Modules |
| --- | --- |
| `swiper` | `c1slider` |
| `swiper,mousetrap` | `phototravlr`, `desire`, `photomania`, `codecanyon/phototravlr` |
| `wavesurfer` | `wavesurfer`, `codecanyon/wavesurfer` |
| `jplayer` | `jq-mplayer` |
| `wp-util,backbone,mediaelement` | `wp-videoplayer` |
| `magnific-popup` | `codecanyon/cubik` |
| `swfobject,swfaddress` | `green-style` |

Many module folders and module zip files are ignored by Git in this working tree. Before changing module dependencies, confirm which module packages are shipped in the GitHub, Freemius, and WordPress.org release artifacts.

## Weak Spots To Turn Into Small Tickets

- WaveSurfer is registered with different versions in admin and frontend code.
- Tempus Dominus is registered as 6.0.0, while the bundled header identifies `v6.0.0-beta5.1`.
- `magnific-popup`, `swfobject`, and `swfaddress` appear as module dependency handles but were not found as core-registered handles in this scan.
- Upload/import UI depends on old `jquery.plupload.queue` and `jqueryFileTree` wrappers.
- Image editor depends on legacy CamanJS and noUiSlider versions.
- Module package folders are locally present but ignored by Git, so release artifact ownership needs a separate inventory.

## Verification Checklist For Future Dependency Work

For each dependency upgrade or removal:

1. Confirm bundled version from both WordPress registration and asset header.
2. Identify all enqueue paths and module dependency declarations.
3. Create a small GitHub issue for one dependency or one tightly coupled group.
4. Add a smoke test note to `release-playbook.md` if the dependency affects a recurring release workflow.
5. Test the exact admin page, frontend module, or media popup that uses the dependency.
6. Keep Freemius and WordPress.org artifact differences in mind before shipping.
