# Plugin Permission Framework Guidelines

## Overview

The Dixlase plugin/theme permission framework is a system that evaluates **Health** and **Trust** of extensions separately. Rather than labeling things as "dangerous," it takes a friendly approach of "informing you of the current status," balancing transparency and safety.

## Core Concepts

### Difference Between Health and Trust

| Concept | Meaning | Evaluation Target |
|---------|---------|-------------------|
| **Health** | Integrity and state of the contents | Consistency between declarations and reality, signature validity, CSP compliance |
| **Trust** | Reliability of the origin and distribution channel | Signing key issuer, distribution channel, author verification |

**Examples:**
- Trust: Official / Health: NeedsAttention - Official but suspected tampering
- Trust: Local / Health: Healthy - Self-built but integrity OK

---

## Health Status (PluginHealthStatus)

### Healthy

**Conditions:**
- Declarations (plugin.json) match reality (scan results)
- Signature is valid (or unsigned is acceptable in development mode)
- No unauthorized operations or suspicious APIs detected
- File placement follows conventions
- CSP compliant (or in development mode)

**Score:** 90-100 points

**Display example:**
```
Health Check: Healthy
No discrepancies between declared permissions and detected usage.
Signature Verification: OK
Last Scan: 2025-12-25 19:40 (scanner v1.3.0)
```

---

### Advisory

**Conditions:**
- Minor permission declaration gaps (missing or excessive)
- Slightly deviant implementation patterns detected
- Unsigned but acceptable in development mode
- Scan is outdated (old scanner_version or never scanned)
- CSP violations present (standard mode)

**Score:** 70-89 points

**Display example:**
```
Health Check: Advisory (Review Recommended)
2 minor findings detected. These do not affect functionality,
but reviewing them is recommended for transparency.

Findings:
- Missing permission declaration: content.read (src/Services/PostService.php:82)
- Unsigned: Permitted in development mode (signing recommended for production distribution)

Recommended Actions:
- Add content.read to the permissions in plugin.json, or review the scope of the affected code.
- Sign the plugin if distributing in a production environment.
```

---

### NeedsAttention

**Conditions:**
- Signature mismatch / suspected tampering
- Significant discrepancy between permissions and reality (strong operations without declaration)
- Clearly dangerous behavior detected
- CSP violations present (strict mode)

**Score:** 0-69 points, or critical flag

**Display example:**
```
Health Check: NeedsAttention (Please review before activation)
1 critical finding detected. Under the current policy,
activating this plugin in its current state is not recommended.

Findings:
- Signature Verification: Mismatch (possible tampering)

Recommended Actions:
- Re-download the ZIP from the original distribution source and reinstall.
- If under development, review the signature generation and packaging process.
```

---

### NotVerified

**Conditions:**
- Insufficient information for verification
- No permission definitions
- No signature
- Scan not performed

**Display example:**
```
Verification Status: NotVerified
This plugin lacks the information required for verification.

Missing Information:
- Scan: Not performed
- Permission Definitions: Not defined
- Signature: Unsigned

Recommended Actions:
- Run a scan
- Define permissions in plugin.json
- Add a signature
```

---

## Trust Levels (PluginTrustLevel)

| Level | Description | Production Recommended |
|-------|-------------|----------------------|
| **Official** | Distributed by Dixlase officially | Yes |
| **Verified** | Distributed by a verified publisher | Yes |
| **Partner** | Distributed by a Dixlase partner | Yes |
| **Community** | Distributed by an unverified publisher | Use caution |
| **Local** | Manually installed / local development | Use caution |

---

## Verification Status (PluginVerificationStatus)

### Signature Status

| Status | Description |
|--------|-------------|
| `signature_valid` | Signature: OK |
| `signature_unsigned` | Signature: Unsigned |
| `signature_invalid` | Signature: Mismatch (possible tampering) |
| `signature_pending` | Signature: Verification pending |

### Permission Definition Status

| Status | Description |
|--------|-------------|
| `permission_ok` | Permission Definition: OK |
| `permission_undefined` | Permission Definition: Not defined |
| `permission_mismatch` | Permission Definition: Mismatch |

### CSP Compliance Status

| Status | Description |
|--------|-------------|
| `csp_ready` | CSP Ready - Fully compliant |
| `csp_compatible` | CSP Compatible - Works with nonce |
| `csp_inline_required` | Inline JS Required - Cannot operate in strict mode |
| `csp_not_checked` | CSP Not Checked |

---

## Scoring Rules

### Starting Score: 100 points

### Deduction Rules

| Item | Deduction |
|------|-----------|
| **Signature** | |
| Unsigned | -5 |
| Signature mismatch | -50 (critical) |
| **Permissions** | |
| Minor undeclared permission | -5 |
| Major undeclared permission | -15 (critical) |
| Unused permission declaration | -2 |
| Permissions not defined | -10 |
| **CSP** | |
| CSP violation (development mode) | 0 |
| CSP violation (standard mode) | -5 |
| CSP violation (strict mode) | -15 |
| Inline JS required | -10 |
| **Scanning** | |
| Scan expired | -5 |
| Scan not performed | -10 |
| **Dangerous APIs** | |
| exec/shell_exec, etc. | -30 (critical) |
| .env reference | -20 |
| **File Placement** | |
| Reference outside plugin directory | -20 |

### Score to Health Conversion

| Score | Health Status |
|-------|--------------|
| 90-100 | Healthy |
| 70-89 | Advisory |
| 0-69 | NeedsAttention |
| Critical flag present | NeedsAttention (immediate) |

---

## Hybrid Design (plugin.json vs config Responsibilities)

Plugin configuration is separated into two layers:

### plugin.json (Manifest - Static Declarations)

Information needed before installation. Written in JSON.

| Section | Purpose |
|---------|---------|
| Metadata | name, slug, version, description, author, license |
| Dependencies | requires |
| Feature declarations | provides (admin_menu, front_routes, etc.) |
| Permission declarations | permissions (what system resources the plugin accesses) |
| Configuration declarations | declares (which config files the plugin provides) |

### config/ (Runtime Settings - PHP-based)

Controls runtime behavior. Requires PHP enum references and complex data structures.

| File | Purpose |
|------|---------|
| `admin/navigation.php` | Menu configuration |
| `admin/roles.php` | Member role defaults (references MemberRole enum) |
| `admin/database-cleanup.php` | Cleanup target tables |

**Why roles / database-cleanup remain as PHP config:**
- PHP enum references (`MemberRole::EDITOR->value`) are needed
- Complex data structures (`additional_conditions`, etc.)
- Existing loading infrastructure (`PluginLoaderTrait`, `PermissionRegistry`, `DatabaseCleanupService`) is operational

---

## Two Types of "Permissions"

| Type | Definition Location | Direction | Example |
|------|-------------------|-----------|---------|
| **Plugin permissions** | `plugin.json` permissions | Plugin -> System | "Can read member data" |
| **Member role permissions** | `config/roles.php` | Member -> Plugin | "Editors and above can manage pages" |

Do not confuse these two. Plugin permissions declare "what system resources this plugin accesses," while member role permissions control "who can use this plugin's features."

---

## plugin.json Extended Specification

### Base Format

```json
{
  "name": "Dixlase Pages",
  "package_name": "dixlase/dixlase-pages",
  "slug": "dixlase-pages",
  "version": "1.0.0",
  "description": {
    "ja": "固定ページ管理機能を提供するプラグインです。",
    "en": "This plugin provides static page management functionality."
  },
  "author": "exc-D inc.",
  "email": "office@exc-d.com",
  "url": "https://exc-d.com",
  "license": "GPL-3.0",
  "namespace": "Plugins\\DixlasePages",
  "type": "dixlase-plugin",
  "category": "content",
  "tags": ["pages", "content", "cms"],
  "requires": {
    "dixlase": ">=1.0.0",
    "php": ">=8.2"
  },
  "provides": {
    "admin_menu": true,
    "front_routes": true,
    "api_routes": false,
    "settings_page": true
  }
}
```

### permissions Section (Required)

Category-based structure with all items explicitly stated as `true`/`false`.

```json
{
  "permissions": {
    "database": {
      "own_tables": true,
      "core_tables_read": [],
      "core_tables_write": []
    },
    "storage": {
      "own_directory": true,
      "public_uploads": false,
      "temp_files": false
    },
    "settings": {
      "read_core": true,
      "write_own": true
    },
    "members": {
      "read": false,
      "write": false,
      "create": false,
      "delete": false
    },
    "mail": {
      "send": false,
      "bulk_send": false
    },
    "content": {
      "read_other_plugins": [],
      "write_other_plugins": []
    },
    "system": {
      "register_shortcodes": false,
      "register_middleware": false,
      "register_commands": false,
      "register_blade_directives": false,
      "modify_routes": false
    },
    "_optional": ["mail.send"],
    "_notes": {
      "ja": "mail.send は通知機能を有効にした場合のみ使用します。",
      "en": "mail.send is only used when notification feature is enabled."
    }
  }
}
```

#### permissions Field Descriptions

| Category | Field | Type | Description |
|----------|-------|------|-------------|
| `database` | `own_tables` | bool | Uses plugin-specific tables |
| `database` | `core_tables_read` | array | Core tables read (e.g., `["members", "settings"]`) |
| `database` | `core_tables_write` | array | Core tables written (e.g., `["members"]`) |
| `storage` | `own_directory` | bool | Uses plugin-specific storage |
| `storage` | `public_uploads` | bool | Accesses public upload area |
| `storage` | `temp_files` | bool | Uses temporary files |
| `settings` | `read_core` | bool | Reads core settings |
| `settings` | `write_own` | bool | Writes plugin settings |
| `members` | `read` | bool | Reads member information |
| `members` | `write` | bool | Writes member information |
| `members` | `create` | bool | Creates members |
| `members` | `delete` | bool | Deletes members |
| `mail` | `send` | bool | Sends email |
| `mail` | `bulk_send` | bool | Sends bulk email |
| `content` | `read_other_plugins` | array | Other plugin slugs for read access |
| `content` | `write_other_plugins` | array | Other plugin slugs for write access |
| `system` | `register_shortcodes` | bool | Registers shortcodes |
| `system` | `register_middleware` | bool | Registers middleware |
| `system` | `register_commands` | bool | Registers Artisan commands |
| `system` | `register_blade_directives` | bool | Registers Blade directives |
| `system` | `modify_routes` | bool | Modifies routes |
| - | `_optional` | array | Scanner hint: no penalty if not detected |
| - | `_notes` | object | Explanation of permission usage (bilingual `ja`/`en`) |

### declares Section (Required)

Declares the config files and structural elements the plugin provides.

```json
{
  "declares": {
    "configs": {
      "roles": true,
      "database_cleanup": false,
      "navigation": true
    },
    "contracts": [],
    "migrations": true,
    "commands": false,
    "middleware": false
  }
}
```

#### declares Field Descriptions

| Field | Type | Description |
|-------|------|-------------|
| `configs.roles` | bool | Whether it provides `config/admin/roles.php` |
| `configs.database_cleanup` | bool | Whether it provides `config/admin/database-cleanup.php` |
| `configs.navigation` | bool | Whether it provides `config/admin/navigation.php` |
| `contracts` | array | Array of Contract interfaces implemented |
| `migrations` | bool | Whether it provides migrations |
| `commands` | bool | Whether it provides Artisan commands |
| `middleware` | bool | Whether it provides middleware |

**Consistency checks:**
- Declaration present + file missing -> health deduction (-5)
- File present + declaration missing -> health deduction (-2)

### Future Extension Fields (Reference)

The following fields are planned for implementation when the marketplace launches:

```json
{
  "security": {
    "sandbox": {
      "storage_scope": "plugin",
      "storage_root": "plugins/dixlase-pages",
      "db_scope": "plugin_tables_only",
      "config_scope": "plugin_settings_only"
    },
    "capabilities": {
      "file_write": ["plugin_storage"],
      "file_read": ["plugin_storage"],
      "network_access": [],
      "exec_process": false
    }
  },

  "verification": {
    "signature": {
      "required": true,
      "file": "signature.sig",
      "algorithm": "ed25519",
      "public_key_id": "exc-d-official-2025"
    },
    "permission_policy": {
      "mode": "strict",
      "allow_undeclared": false
    }
  },

  "publisher": {
    "trust_level": "official",
    "publisher_id": "exc-d-inc",
    "website": "https://exc-d.com"
  },

  "csp": {
    "requires_inline_js": false,
    "requires_inline_css": false,
    "external_scripts": [],
    "external_styles": [
      "https://fonts.googleapis.com"
    ]
  }
}
```

#### security.sandbox

| Field | Description |
|-------|-------------|
| `storage_scope` | Storage scope (plugin/shared/global) |
| `storage_root` | Storage root path |
| `db_scope` | Database scope (plugin_tables_only/core_read/core_write) |
| `config_scope` | Config scope (plugin_settings_only/core_read/core_write) |

#### security.capabilities

| Field | Description |
|-------|-------------|
| `file_write` | Writable areas |
| `file_read` | Readable areas |
| `network_access` | External communication targets |
| `exec_process` | Whether process execution is allowed |

#### verification

| Field | Description |
|-------|-------------|
| `signature.required` | Whether a signature is required |
| `signature.file` | Signature file name |
| `signature.algorithm` | Signature algorithm |
| `permission_policy.mode` | Permission policy mode (strict/balanced/permissive) |
| `permission_policy.allow_undeclared` | Whether undeclared permissions are allowed |

#### publisher

| Field | Description |
|-------|-------------|
| `trust_level` | Trust level (official/verified/partner/community/local) |
| `publisher_id` | Publisher ID |
| `website` | Publisher website |

#### csp

| Field | Description |
|-------|-------------|
| `requires_inline_js` | Whether inline JS is required |
| `requires_inline_css` | Whether inline CSS is required |
| `external_scripts` | External script URLs |
| `external_styles` | External stylesheet URLs |

---

## Permission Categories

Scan targets corresponding to the category-based permissions structure:

| Category | Scan Target | Description |
|----------|-------------|-------------|
| `database.own_tables` | Migrations, Schema::create | Plugin-specific tables |
| `database.core_tables_read` | Core model use/references | Core table read access |
| `database.core_tables_write` | Core model save/create/update/delete | Core table write access |
| `storage.own_directory` | Storage facade usage | Plugin-specific storage |
| `storage.public_uploads` | Public disk writes | Public upload area |
| `storage.temp_files` | Temp file operations | Temporary files |
| `settings.read_core` | config() reading core settings | Core settings read |
| `settings.write_own` | Plugin settings saving | Plugin settings write |
| `members.read/write/create/delete` | Member model operations | Member-related operations |
| `mail.send` | Mail facade, Mailable | Email sending |
| `mail.bulk_send` | Batch email, queues | Bulk email sending |
| `content.read_other_plugins` | Other plugin model references | Reading other plugin data |
| `content.write_other_plugins` | Other plugin model updates | Writing other plugin data |
| `system.register_*` | ServiceProvider registrations | System extension points |
| `system.modify_routes` | Route definition changes | Route modification |

---

## Integration with Security Settings

### Preset Modes

| Mode | Signature | Permission Definition | Max Health Level | CSP |
|------|-----------|----------------------|------------------|-----|
| **Strict** | Required | Required | Healthy only | Strict mode |
| **Balanced** | Recommended | Recommended | Up to Advisory | Standard mode |
| **Development** | Not required | Not required | Up to NotVerified | Development mode |

### CSP Mode Integration

| CSP Mode | Behavior | Plugin Restrictions |
|----------|----------|-------------------|
| **Development Mode** | Log violations, do not block | None |
| **Standard Mode** | Allow only nonce-based inline | Warning shown for `requires_inline_js: true` |
| **Strict Mode** | Completely block inline | `requires_inline_js: true` cannot be activated |

---

## Admin Panel Display

### Plugin List Chips

```
[Health]   Healthy / Advisory / NeedsAttention / NotVerified
[Trust]    Official / Verified / Partner / Community / Local
[Verify]   Signature: OK / Unsigned / Mismatch
           Permission Definition: OK / Not Defined / Mismatch
           CSP: Ready / Compatible / Inline JS Required
[Scan]     Last Scan: 2025-12-25 19:40
```

### Blocking Message

```
This plugin cannot be activated under the current security settings.

The health check resulted in "NeedsAttention."
Either adjust the allowed range in security settings,
or resolve the findings and run a re-scan.

[View Details] [Go to Security Settings] [Cancel]
```

---

## Best Practices

### For Plugin Developers

1. **Define permissions in plugin.json**
   - Explicitly declare all permissions used
   - Separate conditional permissions using optional

2. **Sign your plugin**
   - Always sign when distributing for production
   - Enables tamper detection and improves trust

3. **Aim for CSP Ready**
   - Use the `@cspNonce` directive
   - Minimize inline scripts

4. **Run scans regularly**
   - Re-scan after code changes
   - Verify that permission declarations match reality

### For Site Administrators

1. **Use Balanced or higher in production**
   - Development mode is for development only

2. **Review logs regularly**
   - CSP violation logs
   - Permission audit logs

3. **Install only from trusted sources**
   - Prefer Official / Verified
   - Exercise caution with Community / Local

---

## Related Documentation

- [CSP Usage Guide](csp-usage.md)
- [Plugin Development Guide](plugin-development.md)
- [Security Settings Guide](security-settings.md)
