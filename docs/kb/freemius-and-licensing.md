# Freemius And Licensing

This document records the current observed license behavior in the local Gmedia Gallery codebase. It is not a customer account record and must not contain license keys, payment data, email addresses, private domains, or customer personal data.

## Current Source Files

- Freemius bootstrap: `grand-media.php`
- License helpers: `inc/functions.php`
- License settings UI: `admin/pages/settings/tpl/license.php`
- Settings processor: `admin/processor/class.processor.settings.php`
- Module buttons: `admin/pages/modules/functions.php`, `admin/pages/modules/tpl/module-item.php`

## Current Freemius Bootstrap

`grand-media.php` defines `gmg_fs()` immediately after the plugin header and initializes the Freemius SDK from `vendor/freemius/start.php`.

Observed current config:

- Product ID: `20980`
- Slug: `grand-media`
- Type: `plugin`
- Public key is present in code.
- `is_premium` is currently `false`.
- `has_premium_version` is currently `false`.
- `has_addons` is currently `false`.
- `has_paid_plans` is currently `true`.
- Menu slug is `GrandMedia`.
- Contact/support menu entries are disabled in Freemius config.
- `after_uninstall` is hooked to `gmg_fs_uninstall_cleanup()`.

Important: an old local `FREEMIUS_INTEGRATION_GUIDE.md` note described a different configuration with `is_premium => true` and `has_premium_version => true`. That note was retired because current code is the source of truth.

## License Helper Behavior

`inc/functions.php` centralizes premium access checks:

- `gmedia_has_premium_license()`
  - Checks Freemius first with `gmg_fs()->can_use_premium_code()`.
  - Falls back to legacy access when `$gmGallery->options['license_name']` is not empty.
  - Returns `false` if neither source grants access.

- `gmedia_get_license_type()`
  - Returns `freemius` when `gmg_fs()->can_use_premium_code()` is true.
  - Returns `legacy` when no Freemius access is detected and `license_name` is not empty.
  - Returns `none` otherwise.

Current priority is Freemius first, legacy second, none third.

Bundled SDK note verified on 2026-06-05:

- `vendor/freemius/includes/class-freemius.php` implements `can_use_premium_code()` as trial access or `has_features_enabled_license()`.
- `has_features_enabled_license()` checks whether the current license has enabled features; it does not obviously check expiration in the inspected SDK code.
- Context7 Freemius WordPress SDK docs emphasize `is__premium_only()` / `__premium_only` for build-time premium-code stripping, and `is_paying()` for active paid customer checks. That does not automatically mean Gmedia should switch helpers, but it makes the intended expired-license behavior a product decision that must be verified before changing access logic.

## Settings UI Behavior

`admin/pages/settings/tpl/license.php` derives:

- `$license_type = gmedia_get_license_type()`
- `$has_premium = gmedia_has_premium_license()`

Observed behavior:

- If the license type is not `freemius`, the legacy license section is shown.
- If `license_name` exists, the UI shows "Legacy License Active" and displays the license name as disabled text.
- If no legacy license exists, the UI shows a legacy activation form and a warning that new licenses are available through Freemius.
- If no premium access exists, the UI shows an "Unlock Premium Features" / "Get Gmedia Premium" section.
- Premium feature settings are wrapped in a disabled fieldset when `$has_premium` is false.
- Purchase links point to `admin.php?page=GrandMedia-pricing`.

## Legacy Activation Behavior

An old local guide said new legacy activations were blocked. Current code does not clearly match that statement.

Observed current code in `admin/processor/class.processor.settings.php`:

- When `license-key-activate` is submitted, the processor checks `gmedia_settings` nonce.
- If `purchase_key` is present, it posts to `https://codeasily.com/rest/gmedia-key.php`.
- On a successful response, it sets:
  - `license_name`
  - `purchase_key`
  - `license_key`
  - `license_key2`
- On an error response, it clears those license option fields.
- Settings reset preserves existing license fields.

Verified on 2026-06-15 with `wp-dev` and a domain-bound legacy test key for `wp-dev.loc`:

- The endpoint returned HTTP 200.
- The response matched the current processor success contract: `error.code` was 200, and `content`, `dkey`, `key`, and `key2` were present.
- No returned key values were copied into docs or GitHub.

Current support policy: legacy activation is still supported for valid legacy keys bound to the current site/domain. New purchases should still use Freemius.

## Module Access Behavior

Observed current code:

- `admin/pages/modules/functions.php` uses `gmedia_has_premium_license()` when deciding premium button behavior.
- `admin/pages/modules/tpl/module-item.php` allows free modules, premium-licensed users, or modules with a `buy` link to proceed; otherwise it points users to `GrandMedia-pricing`.

Manual verification is still required for premium modules in the admin UI.

## Expired License Policy

Decided 2026-08-23 (GitHub #16): an expired Freemius license KEEPS premium
features. Renewal is asked for, never enforced by cutting access.

Why premium stays on — the chain is entirely inside the SDK, not plugin code:

- `gmedia_has_premium_license()` calls `can_use_premium_code()`.
- `can_use_premium_code()` is `is_trial() || has_features_enabled_license()`.
- `has_features_enabled_license()` requires the license object's
  `is_features_enabled()`, which is
  `is_active() && ( ! is_block_features || ! is_expired() )`, where
  `is_active()` only means "not cancelled".

So an expired, non-cancelled license keeps features whenever the plan's
`is_block_features` flag is off. That flag lives in the Freemius dashboard, so
the access policy is configuration, not code. Verified live on 2026-08-23:
license present, not cancelled, expired, `is_block_features` off, not lifetime.

Consequences:

- Do NOT tighten the helper to `has_active_valid_license()` or
  `is_paying_or_trial()`. That would move the policy out of the Freemius
  dashboard into the release cycle and diverge from what the customer's own
  Freemius account page tells them.
- To block features on expiry, flip `is_block_features` on the plan in the
  Freemius dashboard. No plugin release needed.
- `can_use_premium_code()` is the CORRECT method here. The
  `can_use_premium_code__premium_only()` variant additionally requires
  `is_premium()`, which is `false` in this plugin's `fs_dynamic_init` config
  (single wp.org codebase, premium gated at runtime), so it would always be
  false. This resolves the previously open question about the method choice.

Renewal messaging, so expiry is visible without blocking anyone:

- Freemius already shows a sticky notice for this exact state
  (`is_expired() && has_features_enabled_license()`), repeated every 14 days on
  license sync: "Your license has expired. You can still continue using all the
  ... features, but you'll need to renew ...".
- The Settings license pane showed nothing at all, so
  `gmedia_has_expired_premium_license()` now renders an inline warning there
  with a renewal link to the Freemius account page.
- That notice deliberately does NOT repeat the Freemius wording, because both
  can appear on the Settings screen at once. It states the expiration date via
  `gmedia_get_premium_license_expiration()` — the fact the global notice omits —
  and leaves the "features keep working" reassurance to Freemius and to the
  support reply template. Keep them complementary if either is reworded.
- The expiration helper returns the raw API datetime; formatting is the
  template's job (`mysql2date` with the site's `date_format`). Keeping it
  WordPress-free is what lets the compat test call it without bootstrapping WP.
- Guard: `tests/compat/expired-license-notice.php` covers both helpers' truth
  tables, the missing-`gmg_fs()` case, and that the template still renders the
  notice with a date and an account link.

## Local Admin Verification

Verified on 2026-06-05 in the local WordPress admin at `https://wp-dev.loc/`.

Public-safe observations only:

- The site is logged into WordPress admin; no login screen appeared during checks.
- The Freemius account page is connected and shows a premium plan state that is expired.
- The account page contains sensitive license/billing data; do not copy page details into public issues or docs.
- `GrandMedia_Settings` shows the Premium Settings pane with premium controls enabled.
- `GrandMedia_Settings` does not show the no-license CTA, the legacy activation section, or the legacy-active notice in the current local state.
- `GrandMedia_Modules` loaded successfully and the sampled local module list did not show a "Get Premium" button.

Interpretation:

- Current local behavior appears to grant premium access in an expired Freemius plan state.
- Resolved on 2026-08-23 (GitHub #16): this is intended, and the mechanism is
  the Freemius plan setting, not plugin code. See "Expired License Policy".

Tooling gap:

- Direct `php -r` bootstrap of WordPress failed from the shell because the Local by WP Engine database socket is not available to the default PHP CLI connection.
- Resolved on 2026-06-15 by installing `wp-dev`, a site-specific WP-CLI wrapper that uses the Local PHP runtime and the `wp-dev.loc` Local `php.ini`.
- Use `wp-dev` for CLI reads against this local site. Generic `php` or generic `wp` commands may still miss the Local MySQL socket.

## Local WP-CLI Verification

Verified on 2026-06-15 with `wp-dev`.

Environment:

- WP-CLI: 2.12.0
- Local PHP runtime: 8.4.10
- WordPress core: 7.0
- Gmedia Gallery: active, 1.25.0

Current sanitized Gmedia option state:

- `license_name`: empty
- `purchase_key`: empty
- `license_key`: present
- `license_key2`: present
- `gmedia_get_license_type()`: `freemius`
- `gmedia_has_premium_license()`: true

Current sanitized Freemius runtime state:

- `is_registered()`: true
- `is_premium()`: false
- `has_paid_plan()`: true
- `has_premium_version()`: false
- `is_trial()`: false
- `is_paying()`: false
- `has_active_valid_license()`: false
- `has_features_enabled_license()`: true
- `can_use_premium_code()`: true

Interpretation:

- Current premium access is coming from Freemius `can_use_premium_code()`, not from legacy `license_name`.
- In this local state, `can_use_premium_code()` is true even though `is_paying()` and `has_active_valid_license()` are false.
- Do not change this behavior inside issue #7; the product decision remains tracked in GitHub #16.

## Manual License State Harness

Use `tests/manual/license-state-matrix.php` through `wp-dev eval-file` for temporary license-state checks. This harness backs up and restores these local options:

- `gmediaOptions`
- `fs_accounts`
- `fs_active_plugins`
- `fs_api_cache`
- `fs_debug_mode`
- `fs_gdpr`
- `fs_options`
- Freemius-related transients matching `fs_%`, `_transient_fs_%`, and `_site_transient_fs_%` patterns

Backup files can contain serialized account/license state. Keep them outside the repo, for example:

```bash
wp-dev eval-file tests/manual/license-state-matrix.php snapshot /private/tmp/gmedia-license-state-backup.json
```

The harness writes backup files with `0600` permissions and prints only sanitized state via:

```bash
wp-dev eval-file tests/manual/license-state-matrix.php current
```

Always restore after a temporary scenario:

```bash
wp-dev eval-file tests/manual/license-state-matrix.php restore /private/tmp/gmedia-license-state-backup.json
```

To verify that the Settings reset processor preserves legacy license fields, run this command only after taking a backup:

```bash
wp-dev eval-file tests/manual/license-state-matrix.php verify-reset-preserves-legacy
```

This command creates temporary synthetic legacy field values, submits the real `GmediaProcessor_Settings` reset path with a nonce, verifies sanitized preservation results, and must be followed by restoring the backup.

Scenarios verified on 2026-06-15:

| Scenario | Temporary setup | Verified result |
| --- | --- | --- |
| No license | Freemius-safe options deleted; legacy fields empty | `gmedia_get_license_type()` returned `none`; `gmedia_has_premium_license()` false; `can_use_premium_code()` false |
| Legacy only | Freemius-safe options deleted; fake legacy `license_name` present | `gmedia_get_license_type()` returned `legacy`; `gmedia_has_premium_license()` true; `can_use_premium_code()` false |
| Both legacy and Freemius | Freemius state restored from backup; fake legacy `license_name` present | `gmedia_get_license_type()` returned `freemius`; `gmedia_has_premium_license()` true; Freemius remained registered; `can_use_premium_code()` true |

After the matrix run, the original baseline was restored and re-checked with `current`.

Admin UI smoke on 2026-06-15:

- The current connected Freemius baseline loads `GrandMedia_Settings` successfully, shows the Premium Settings pane, and does not render legacy license inputs.
- The `both` scenario loads `GrandMedia_Settings` successfully after Freemius state is restored from the backup and fake legacy data is added. Freemius remains the effective license type, Premium Settings is visible, and legacy license inputs are not rendered.
- In this connected Local site, direct `GrandMedia_Settings` access returns the WordPress "not allowed" error when Freemius account state is removed or unregistered for no-license / legacy-only simulation, even when `fs_active_plugins` is preserved. Treat this as a local Freemius connection-state limitation, not a verified Gmedia no-license UI result.
- No-license and legacy-only UI smoke should be completed on a fresh Local site, a safely disconnected Freemius test site, or an approved Freemius disconnect workflow. Do not keep deleting additional Freemius options by guesswork.

Settings reset verification on 2026-06-15:

- `verify-reset-preserves-legacy` ran the real `GmediaProcessor_Settings` reset branch in `wp-dev`.
- The reset marker was removed, confirming the default reset path executed.
- `license_name`, `purchase_key`, `license_key`, and `license_key2` were all preserved.
- The original Local baseline was restored and re-checked with `current`.

## Support Rules

- Do not ask users to post license keys publicly.
- Do not copy license keys, account details, payment/order data, emails, or private domains into public GitHub issues.
- If a license issue needs account inspection, keep it in Gmail/support context and use GitHub only for sanitized product behavior issues.
- Do not change free/premium behavior until the Freemius and legacy access paths are verified.
- Any behavior change must be split into a dedicated issue.

## Support Drafts

### Legacy License

```text
Hi [name],

Gmedia currently supports both Freemius licensing and older legacy license keys. Valid older keys can still be activated for the site/domain they belong to.

Please do not post your license key publicly. If activation fails, email support with the account/order details and the site URL so I can check it privately.

Best,
Serhii
```

### Freemius Activation

```text
Hi [name],

New license purchases are handled through Freemius. In the plugin admin, please check the Gmedia Premium/License area and use the Freemius activation or account link there.

Please do not send license keys in a public forum thread. If this needs account-specific help, email support and I will review it there.

Best,
Serhii
```

### Lost Key Or Account-Specific Access

```text
Hi [name],

This looks account-specific, so it should stay out of the public forum. Please contact support by email and include the purchase/account details there, not publicly.

I will check whether this is a Freemius license or an older legacy license and reply with the next step.

Best,
Serhii
```

## Verification Matrix

These scenarios must be tested in WordPress admin before closing the licensing review issue:

| Scenario | Expected based on code | Status |
| --- | --- | --- |
| No license | `gmedia_get_license_type()` returns `none`; premium fieldset disabled; pricing CTA visible | Helper behavior verified with `wp-dev` harness; admin UI CTA still needs browser smoke on a fresh/disconnected Freemius site because the connected Local site returns WordPress "not allowed" when Freemius account state is removed |
| Legacy license only | `gmedia_get_license_type()` returns `legacy`; premium feature fieldset enabled | Helper behavior verified with `wp-dev` harness; admin UI still needs browser smoke on a fresh/disconnected Freemius site because the connected Local site returns WordPress "not allowed" when Freemius account state is removed |
| Freemius license only | `gmedia_get_license_type()` returns `freemius`; Freemius takes priority | Verified in local admin and WP-CLI. The connected state is an expired, non-cancelled license with `is_block_features` off, so `can_use_premium_code()` is true by design — see "Expired License Policy" (#16, resolved) |
| Expired Freemius license | Premium features stay enabled; Settings shows a renewal warning | Helper truth table + template wiring guarded by `tests/compat/expired-license-notice.php`; live state confirmed 2026-08-23. Admin UI browser smoke still pending with #19 |
| Both legacy and Freemius | `freemius` takes priority; legacy acts as fallback only if Freemius access is unavailable | Helper priority verified with `wp-dev` harness; admin UI smoke verified in the connected Local site |
| Legacy activation form | Current processor calls `codeasily.com/rest/gmedia-key.php`; valid domain-bound legacy keys are still accepted | Verified with `wp-dev` and a domain-bound test key for `wp-dev.loc`; no key values copied into docs |
| Module buttons | Premium access should be controlled by `gmedia_has_premium_license()` | Partially verified in local admin for current connected Freemius state; sampled modules did not show "Get Premium" |
| Settings reset | Existing legacy license fields should be preserved | Verified with `wp-dev` harness against the real `GmediaProcessor_Settings` reset path; original Local baseline restored afterward |

## Open Risks

- Retired local notes and current code disagreed on whether new legacy activations are blocked; verified current endpoint behavior shows valid domain-bound legacy keys are still accepted.
- Retired local notes described a Freemius config that differed from the current `grand-media.php` config.
- ~~`gmedia_has_premium_license()` uses `can_use_premium_code()`; verify this is the intended Freemius method~~ — resolved 2026-08-23: correct method, see "Expired License Policy".
- ~~Expired Freemius plan access is ambiguous~~ — resolved 2026-08-23 (#16): expired licenses intentionally keep features; policy lives in the Freemius plan's `is_block_features` flag.
- Premium/free boundary audit is intentionally deferred until these license paths are verified.
