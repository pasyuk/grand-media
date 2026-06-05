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

This needs manual/admin verification before support messaging says legacy activation is blocked or still available.

## Module Access Behavior

Observed current code:

- `admin/pages/modules/functions.php` uses `gmedia_has_premium_license()` when deciding premium button behavior.
- `admin/pages/modules/tpl/module-item.php` allows free modules, premium-licensed users, or modules with a `buy` link to proceed; otherwise it points users to `GrandMedia-pricing`.

Manual verification is still required for premium modules in the admin UI.

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

Gmedia currently supports both Freemius licensing and older legacy license data. Please do not post your license key publicly.

I will check the license behavior carefully against the current version and reply with the safest next step.

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
| No license | `gmedia_get_license_type()` returns `none`; premium fieldset disabled; pricing CTA visible | Not manually verified |
| Legacy license only | `gmedia_get_license_type()` returns `legacy`; premium feature fieldset enabled | Not manually verified |
| Freemius license only | `gmedia_get_license_type()` returns `freemius`; Freemius takes priority | Not manually verified |
| Both legacy and Freemius | `freemius` takes priority; legacy acts as fallback only if Freemius access is unavailable | Not manually verified |
| Legacy activation form | Current processor appears to still call `codeasily.com/rest/gmedia-key.php`; guide says this may be blocked | Needs verification |
| Module buttons | Premium access should be controlled by `gmedia_has_premium_license()` | Not manually verified |
| Settings reset | Existing legacy license fields should be preserved | Not manually verified |

## Open Risks

- Retired local notes and current code disagreed on whether new legacy activations are blocked.
- Retired local notes described a Freemius config that differed from the current `grand-media.php` config.
- `gmedia_has_premium_license()` uses `can_use_premium_code()`; verify this is the intended Freemius method for the current wp.org/free plugin flow.
- Premium/free boundary audit is intentionally deferred until these license paths are verified.
