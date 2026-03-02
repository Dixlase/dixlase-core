# Plugin & Theme Health Scoring Rules

> **[日本語版はこちら](../../ja/development/plugins/health-scoring-rules.md)**

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
| `database.core_tables`           | 0     | Access core database tables   |
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

#### Low-Risk Permissions (score = 0)

| Permission                  | Score | Description                   |
|-----------------------------|-------|-------------------------------|
| `database.own_tables`       | 0     | Own database tables           |
| `database.core_tables`      | 0     | Access core database tables   |
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

### Enable Action Determination

| Score Range   | Action Required          |
|---------------|--------------------------|
| 90+           | Allowed (no warning)     |
| 70 – 89       | Warning dialog required  |
| Below 70      | Acknowledgement required |
| Critical issue| Acknowledgement required |

### Deduction Rules

#### Signature

| Rule Key               | Deduction | Description                                  |
|------------------------|-----------|----------------------------------------------|
| `signature.unsigned`   | -5        | Plugin is not signed                         |
| `signature.unsigned_production` | -15 | Unsigned in production environment         |
| `signature.invalid`    | **-50**   | Signature is invalid (possible tampering)    |
| `signature.mismatch`   | **-50**   | Signature doesn't match files                |

#### Permissions

| Rule Key                        | Deduction | Description                                    |
|---------------------------------|-----------|------------------------------------------------|
| `permissions.undeclared_minor`  | -5        | Minor undeclared permission usage              |
| `permissions.undeclared_major`  | **-15**   | Major undeclared permission usage              |
| `permissions.unused`            | -2        | Permission declared but not used in code       |
| `permissions.undefined`         | -10       | No permission definition in JSON               |

#### CSP (Content Security Policy)

| Rule Key                       | Deduction | Description                                    |
|--------------------------------|-----------|------------------------------------------------|
| `csp.violation_dev`            | 0         | CSP violation (development only)               |
| `csp.violation_standard`       | -5        | CSP violation in standard mode                 |
| `csp.violation_strict`         | -15       | CSP violation in strict mode                   |
| `csp.inline_js_required`       | -10       | Requires inline JavaScript                     |
| `csp.inline_css_required`      | -5        | Requires inline CSS                            |
| `csp.external_resources`       | -3        | Uses external resources                        |

#### Scan Freshness

| Rule Key               | Deduction | Description                                    |
|------------------------|-----------|------------------------------------------------|
| `scan.outdated`        | -5        | Scan is older than 30 days                     |
| `scan.not_performed`   | -10       | No scan has been performed                     |

#### Dangerous APIs

| Rule Key                    | Deduction | Description                                |
|-----------------------------|-----------|---------------------------------------------|
| `dangerous_api.exec`        | **-30**   | Uses process execution functions (exec, shell_exec, etc.) |
| `dangerous_api.env_access`  | -20       | Direct environment variable access          |

#### File Scope

| Rule Key                | Deduction | Description                                    |
|-------------------------|-----------|------------------------------------------------|
| `file.outside_scope`    | -20       | File operations outside plugin directory       |

### Critical Issues

The following issues immediately set the health status to `NeedsAttention` regardless of score:

| Critical Issue Key              | Description                        |
|---------------------------------|------------------------------------|
| `signature_invalid`             | Signature verification failed      |
| `signature_mismatch`            | Signature doesn't match files      |
| `dangerous_api_exec`            | Uses process execution functions   |
| `permission_undeclared_major`   | Major undeclared permission usage  |

---

## 3. Scoring Flow

```
┌─────────────────────────────┐
│  Base Score: 100            │
├─────────────────────────────┤
│  1. Signature Verification  │  → Deductions applied
│  2. Permission Consistency  │  → Deductions applied
│  3. CSP Compliance          │  → Deductions applied
│  4. Dangerous API Detection │  → Deductions applied
│  5. Scan Freshness          │  → Deductions applied
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
| `app/Services/Plugin/PluginPermissionService.php` | Plugin risk scoring |
| `app/Services/Theme/ThemePermissionService.php` | Theme risk scoring |
| `app/Contracts/Plugin/PluginPermissionServiceInterface.php` | Service contract |
