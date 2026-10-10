# Plugin & Theme Health Scoring Rules
This document describes the two scoring systems used by Dixlase to evaluate plugin and theme safety: **Risk Scoring** and **Health Scoring**.

---

## 1. Risk Scoring (Permission-Based)

Risk scoring evaluates the declared permissions in `plugin.json` / `theme.json`. It determines the risk level displayed in scan results and attention reasons.

### Thresholds

| Total Score | Risk Level |
|-------------|------------|
| 0 – 2       | `low`      |
| 3 – 6       | `medium`   |
| 7+          | `high`     |

### Plugin Permission Scores

#### High-Risk Permissions (score >= 2)

| Permission                      | Score | Description                    |
|---------------------------------|-------|--------------------------------|
| `members.write`                 | +3    | Write member data              |
| `members.create`                | +3    | Create new members             |
| `members.delete`                | +4    | Delete members                 |
| `mail.bulk_send`                | +3    | Bulk send emails               |
| `storage.public_uploads`        | +2    | Upload to public directory     |
| `content.write_other_plugins`   | +2    | Write other plugin content     |

#### Low-Risk Permissions (score = 0)

| Permission                       | Score | Description                   |
|----------------------------------|-------|-------------------------------|
| `database.own_tables`            | 0     | Own database tables           |
| `database.core_tables_read`      | 0     | Read core database tables     |
| `database.core_tables_write`     | 1     | Write core database tables    |
| `storage.own_directory`          | 0     | Own storage directory         |
| `storage.temp_files`             | 0     | Temporary files               |
| `settings.read_core`             | 0     | Read core settings            |
| `settings.write_own`             | 0     | Write own settings            |
| `members.read`                   | 0     | Read member data              |
| `mail.send`                      | 0     | Send individual emails        |
| `content.read_other_plugins`     | 0     | Read other plugin content     |
| `system.register_shortcodes`     | 0     | Register shortcodes           |
| `system.register_middleware`     | 0     | Register middleware           |
| `system.register_commands`       | 0     | Register commands             |
| `system.register_blade_directives` | 0   | Register Blade directives     |
| `system.modify_routes`           | 0     | Modify routes                 |

#### Mismatch Penalty

| Mismatch Type          | Score per occurrence | Description                              |
|------------------------|----------------------|------------------------------------------|
| `undeclared_usage`     | +2                   | Code uses a permission not declared in JSON |
| `unused_declaration`   | 0                    | JSON declares permission not found in code  |

### Theme Permission Scores

Themes use a different set of permissions with different scores.

#### High-Risk Permissions (score >= 2)

| Permission                      | Score | Description                    |
|---------------------------------|-------|--------------------------------|
| `storage.public_uploads`        | +2    | Upload to public directory     |
| `assets.external_resources`     | +3    | Load external resources        |

#### Medium-Risk Permissions (score = 1)

| Permission                           | Score | Description                    |
|--------------------------------------|-------|--------------------------------|
| `system.register_commands`           | +1    | Register commands               |
| `system.register_blade_directives`   | +1    | Register Blade directives       |
| `system.modify_routes`              | +1    | Modify routes                   |
| `database.core_tables_write`        | +1    | Write core database tables      |

#### Low-Risk Permissions (score = 0)

| Permission                  | Score | Description                   |
|-----------------------------|-------|-------------------------------|
| `database.own_tables`       | 0     | Own database tables           |
| `database.core_tables_read` | 0     | Read core database tables     |
| `storage.own_directory`     | 0     | Own storage directory         |
| `storage.temp_files`        | 0     | Temporary files               |
| `settings.read_core`        | 0     | Read core settings            |
| `settings.write_own`        | 0     | Write own settings            |
| `assets.custom_css`         | 0     | Custom CSS                    |
| `assets.custom_js`          | 0     | Custom JS                     |
| `system.register_shortcodes`| 0     | Register shortcodes           |
| `system.register_middleware`| 0     | Register middleware            |

---

## 2. Health Scoring (0–100 Points)

Health scoring is the canonical system used for determining the overall health status of a plugin. It starts from a **base score of 100** and applies deductions based on various criteria.

Source: `PluginHealthScorer` using `PluginHealthStatus::getDeductionRules()`

### Health Status Thresholds

| Score Range | Status           | Display Label  |
|-------------|------------------|----------------|
| 90 – 100    | `Healthy`        | Good           |
| 70 – 89     | `Advisory`       | Warning        |
| 0 – 69      | `NeedsAttention` | Needs Attention|

An extension that has never been scanned is not scored: its status is `NotVerified`.

### Enable Action Determination

Source: `ExtensionEnableActionResolver`. The checks run in this order:

1. **Blocked** when the active preset requires a signature and the extension does not carry a verified signature (unsigned, invalid, expired, unknown key, verification error or pending verification all fail).
2. **Blocked** when the health status is above the maximum status the preset allows for that extension type (see the table below). Statuses rank `Healthy` < `Advisory` < `NeedsAttention` < `NotVerified`.
3. Otherwise the score decides:

| Result                       | Action                   |
|------------------------------|--------------------------|
| 90+                          | Allowed (no warning)     |
| 70 – 89                      | Warning dialog required  |
| Below 70, or a critical issue| Acknowledgement required |

| Preset              | Signature required | Max status (plugins) | Max status (themes) |
|---------------------|--------------------|----------------------|---------------------|
| Strict              | Yes                | `Healthy`            | `Healthy`           |
| Balanced (default)  | No                 | `Advisory`           | `NeedsAttention`    |
| Development         | Never              | No limit             | No limit            |
| Custom              | As configured      | As configured (default `Advisory`) | As configured (default `Advisory`) |

Under the default Balanced preset, a plugin whose status is `NeedsAttention` (score below 70 or a critical issue) or `NotVerified` is therefore Blocked, while a theme in `NeedsAttention` needs acknowledgement.

The gate runs when an extension is installed or enabled in the admin panel, when a theme is switched in the admin panel, and in the update commands (`dls:plugin:update` / `dls:theme:update`), which put the previous version back if the new one scans as Blocked.

### Deduction Rules

Rule keys are the issue types `PluginHealthScorer` emits. Themes are scored by `ThemeHealthScorer` with the same table and a subset of the checks.

#### Signature

| Rule Key                         | Deduction | Description                                  |
|----------------------------------|-----------|----------------------------------------------|
| `signature_unsigned`             | -10       | Not signed                                   |
| `signature_invalid`              | **-50**   | Signature does not verify (files changed or wrong key) — critical |
| `signature_pending_verification` | -5        | Signature present but could not be verified yet |
| `signature_unknown_key`          | -15       | Signed with a key core does not know         |
| `signature_expired`              | -20       | Signature has expired                        |
| `signature_error`                | -10       | Verification failed with an error            |

#### Permissions

| Rule Key                        | Deduction | Description                                    |
|---------------------------------|-----------|------------------------------------------------|
| `permission_undeclared_minor`   | -5        | Code uses a permission that is not declared    |
| `permission_undeclared_major`   | **-15**   | Same, for a high-risk permission (`database.core_tables_write`, `members.write`, `members.delete`, `system.modify_routes`) — critical |
| `permission_unused`             | -2        | Permission declared but not found in code (not applied to `_optional` permissions) |
| `permission_undefined`          | -10       | No `permissions` section in the manifest       |

#### Declared high-risk permissions (plugins)

| Rule Key                          | Deduction | Description                                   |
|-----------------------------------|-----------|-----------------------------------------------|
| `risk_public_uploads_own_dir`     | -2        | `storage.public_uploads` with `storage.own_directory` |
| `risk_public_uploads_no_own_dir`  | -4        | `storage.public_uploads` without `storage.own_directory` |
| `risk_members_delete`             | -4        | `members.delete` declared                     |
| `risk_mail_bulk_send`             | -3        | `mail.bulk_send` declared                     |

#### CSP (Content Security Policy)

| Rule Key                       | Deduction | Description                                    |
|--------------------------------|-----------|------------------------------------------------|
| `csp_inline_js_required`       | -10       | Requires inline JavaScript                     |
| `csp_inline_css_required`      | -5        | Requires inline CSS                            |
| `csp_violation_standard`       | -5        | Needs inline code while the CSP mode is standard |
| `csp_violation_strict`         | -15       | Needs inline code while the CSP mode is strict |

In development CSP mode no violation deduction is applied.

#### Scan Freshness

| Rule Key               | Deduction | Description                                    |
|------------------------|-----------|------------------------------------------------|
| `scan_outdated`        | -5        | Last scan is more than 30 days old             |
| `scan_not_performed`   | -10       | No scan has been performed                     |

#### Dangerous APIs

| Rule Key               | Deduction | Description                                    |
|------------------------|-----------|------------------------------------------------|
| `dangerous_api_exec`   | **-30**   | A dangerous API was detected, such as process execution (`exec`, `shell_exec`, ...) or direct `env()` access — critical |

#### Extension API Version (`requires.dixlase_api`)

| Rule Key                   | Deduction | Description                                  |
|----------------------------|-----------|----------------------------------------------|
| `missing_api_version`      | -5        | No API version constraint declared           |
| `incompatible_api_version` | -15       | Constraint does not match this core          |
| `malformed_api_constraint` | -10       | Constraint cannot be parsed                  |

#### License (plugins)

| Rule Key               | Deduction | Description                                    |
|------------------------|-----------|------------------------------------------------|
| `missing_license`      | -10       | No `license` field                             |
| `invalid_license_spdx` | -5        | Not a valid SPDX identifier                    |
| `unknown_license`      | -3        | Not in the accepted-licenses table             |
| `license_refused`      | **-25**   | On the refused list — critical                 |

These values can be overridden per deployment in `config/licensing.php` (`health_deductions`).

#### Supply-Chain Metadata (plugins)

| Rule Key                       | Deduction | Description                                                          |
|--------------------------------|-----------|----------------------------------------------------------------------|
| `missing_author_id`            | -3        | `author_id` is absent from `plugin.json`                             |
| `missing_authority_key_id`     | -3        | `authority_key_id` is absent from `plugin.json`                      |

Both fields are required for the supply-chain attack defense flow (see
[Supply-Chain Metadata for Plugin Authors](supply-chain-metadata.md) and
[Supply-Chain Defense Data Layer](../supply-chain.md)). Without them the
version-history change-flag logic cannot detect a hijacked update because
there is no canonical prior owner to compare against.

### Critical Issues

The following issues set the health status to `NeedsAttention` regardless of score:

| Critical Issue Key              | Description                        |
|---------------------------------|------------------------------------|
| `signature_invalid`             | Signature verification failed      |
| `dangerous_api_exec`            | A dangerous API was detected       |
| `permission_undeclared_major`   | Undeclared high-risk permission    |
| `license_refused`               | License is on the refused list     |

---

## 3. Scoring Flow

```
┌─────────────────────────────┐
│  Not scanned → NotVerified  │
├─────────────────────────────┤
│  Base Score: 100            │
├─────────────────────────────┤
│  1. Signature Verification  │  → Deductions applied
│  2. Permission Consistency  │  → Deductions applied
│  3. CSP Compliance          │  → Deductions applied
│  4. Dangerous API Detection │  → Deductions applied
│  5. Scan Freshness          │  → Deductions applied
│  6. Declared Risk Perms     │  → Deductions applied
│  7. Supply-Chain Metadata   │  → Deductions applied
│  8. License                 │  → Deductions applied
│  9. API Version             │  → Deductions applied
├─────────────────────────────┤
│  Final Score = max(0, total)│
│  + Critical Issue Check     │
└─────────────────────────────┘
```

## 4. Related Files

| File | Description |
|------|-------------|
| `app/Services/Plugin/PluginHealthScorer.php` | Health score calculation |
| `app/Enums/PluginHealthStatus.php` | Status thresholds and deduction rules |
| `app/Services/Extension/ExtensionEnableActionResolver.php` | Enable action (Allowed / Warning / Acknowledge / Blocked) |
| `app/Enums/ExtensionSecurityPreset.php` | Preset defaults (signature requirement, max status) |
| `app/Services/Plugin/PluginPermissionService.php` | Plugin risk scoring |
| `app/Services/Theme/ThemePermissionService.php` | Theme risk scoring |
| `app/Contracts/Plugin/PluginPermissionServiceInterface.php` | Service contract |
