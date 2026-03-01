# Admin Panel Mode Settings Specification

## Table of Contents

1. [Overview](#overview)
2. [Mode Definitions](#mode-definitions)
3. [Menu Visibility Levels (5-Classification Model)](#menu-visibility-levels-5-classification-model)
4. [Menu Classification List](#menu-classification-list)
5. [Detailed Specifications for Each Menu](#detailed-specifications-for-each-menu)
6. [Auto-Configured Values List](#auto-configured-values-list)
7. [UI/UX Rules](#uiux-rules)
8. [Implementation Architecture](#implementation-architecture)
9. [Plugin Menu Support](#plugin-menu-support)

---

## Overview

The Dixlase admin panel has two operating modes: "Simple Mode" and "Advanced Mode". In Simple Mode, security-related settings are automatically configured with safe default values, and only day-to-day operations are available. In Advanced Mode, all features are accessible.

### Design Principles

1. **Don't expose what can break** --- Settings with high risk of accidental misconfiguration are hidden in Simple Mode
2. **Always show what's happening** --- Even hidden settings are displayed as ReadOnly when their status needs to be visible
3. **Separate contexts by responsibility level** --- Advanced settings guide users to Advanced Mode

### Related Files

| Category | File Path |
|----------|-----------|
| Enum (Mode) | `app/Enums/AdminMode.php` |
| Enum (Visibility) | `app/Enums/MenuVisibility.php` |
| Helper | `app/Helpers/AdminModeHelper.php` |
| Default Settings | `config/admin/mode.php` |
| Navigation Config | `config/admin/navigation.php` |
| Sidebar View | `resources/views/admin/partials/sidebar.blade.php` |
| Mode Switching UI | `resources/views/admin/settings/base/mode.blade.php` |
| Controller | `app/Http/Controllers/Admin/Settings/Base/AdminBaseModeController.php` |

---

## Mode Definitions

| Mode | Enum Value | Default | Description |
|------|------------|---------|-------------|
| Simple Mode | `AdminMode::Simple (0)` | Yes | Provides only the features needed for day-to-day operations. Safe defaults are applied automatically |
| Advanced Mode | `AdminMode::Advanced (1)` | | Full access to all features. Intended for advanced users |

- The mode setting is stored in the `base_settings` table under the `admin_mode` key
- It is selected during installation and can be changed later from the admin panel
- In Advanced Mode, all menus are treated as `MenuVisibility::Full`

---

## Menu Visibility Levels (5-Classification Model)

| Classification | Enum Value | Sidebar Display | Page Access | Form Operations | Icon |
|----------------|------------|-----------------|-------------|-----------------|------|
| **Full** | `MenuVisibility::Full (0)` | Normal display | Allowed | All operations available | None |
| **Partial** | `MenuVisibility::Partial (1)` | Normal display | Allowed | Some fields disabled/auto-configured | None |
| **Hidden** | `MenuVisibility::Hidden (2)` | Hidden | 403 or redirect | Not allowed (optimal values applied automatically) | --- |
| **ReadOnly** | `MenuVisibility::ReadOnly (3)` | With lock icon | Allowed (read-only) | Save button hidden | `fa-lock` |
| **GuideOnly** | `MenuVisibility::GuideOnly (4)` | With directions icon | Allowed (guide UI displayed) | Only "Configure in Advanced Mode" link | `fa-directions` |

### Classification Usage Criteria

- **Full**: Used for low-risk day-to-day operations (content editing, profile, etc.)
- **Partial**: Used when basic operations are needed but detailed parameters should be auto-configured
- **Hidden**: Used for items with high risk of accidental misconfiguration, or items beginners have no reason to access
- **ReadOnly**: Used when settings cannot be changed but transparency about current values must be maintained
- **GuideOnly**: Used when users should be aware of the feature's existence but directed to Advanced Mode for configuration

---

## Menu Classification List

### Default Settings in Simple Mode

> In Advanced Mode, all items are set to **Full**.

#### Dashboard / Front Page

| Menu Key | Classification | Notes |
|----------|---------------|-------|
| `dashboard` | **Full** | Always displayed. Locked (cannot be changed) |
| `front` | **Full** | Front page management allows all operations |
| `front.index` | Full (inherited) | |
| `front.edit` | Full (inherited) | |
| `front.settings` | Full (inherited) | |

#### Media Management

| Menu Key | Classification | Notes |
|----------|---------------|-------|
| `media` | **Partial** | |
| `media.index` | **Full** | Media master allows all operations |
| `media.upload` | **Full** | Upload allows all operations |
| `media.settings` | **Partial** | MIME validation, SVG sanitization, and ZIP checks are auto-configured |

#### Profile Settings

| Menu Key | Classification | Notes |
|----------|---------------|-------|
| `profile` | **Full** | Locked (cannot be changed) |
| `profile.index` | Full (inherited) | |
| `profile.basic` | Full (inherited) | |
| `profile.password` | Full (inherited) | |
| `profile.appearance` | Full (inherited) | |
| `profile.notifications` | Full (inherited) | Operable only when global settings are set to "Use Profile Settings" (view-level control) |
| `profile.two_fa` | Full (inherited) | Same as above |
| `profile.two_fa_management` | Full (inherited) | Passkey and recovery code management |

#### Member Management

| Menu Key | Classification | Notes |
|----------|---------------|-------|
| `members` | **Partial** | |
| `members.index` | **Full** | List view allows all operations. Force logout button is hidden at the view level |
| `members.create_edit` | **Full** | Create and edit operations are available |
| `members.roles` | **Hidden** | Permission settings are exclusive to Advanced Mode |

#### Global Settings > General Settings

| Menu Key | Classification | Notes |
|----------|---------------|-------|
| `settings` | **Partial** | |
| `settings.base` | **Partial** | |
| `settings.base.index` | **Full** | Overview is fully displayed |
| `settings.base.site` | **Full** | Site name, description, OGP, etc. allow all operations |
| `settings.base.admin` | **GuideOnly** | Admin panel URL changes carry accident risk, so users are guided to "Configure in Advanced Mode" |
| `settings.base.mail` | **Partial** | Admin email address is editable. SMTP details display "Configure in Advanced Mode" |
| `settings.base.maintenance` | **Full** | Maintenance mode allows all operations |
| `settings.base.mode` | **Full** | Mode settings are always accessible |

#### Global Settings > Security Settings

| Menu Key | Classification | Notes |
|----------|---------------|-------|
| `settings.security` | **ReadOnly** | Security overview displayed as summary |
| `settings.security.index` | **ReadOnly** | |
| `settings.security.password` | **Hidden** | Auto-configured: strong policy applied |
| `settings.security.login` | **Partial** | Login notification ON/OFF/conditions are editable. Attempt limit details are auto-configured |
| `settings.security.two-fa` | **Partial** | 2FA/Passkey ON/OFF/conditions are editable. Detailed parameters are auto-configured |
| `settings.security.notifications` | **Hidden** | Error notifications are auto-configured |
| `settings.security.captcha` | **Partial** | Provider selection, API keys, and per-form toggles are editable |
| `settings.security.session` | **Hidden** | Auto-configured: default values are maintained |
| `settings.security.csp` | **Hidden** | Auto-configured: ON, standard mode, warn-only |
| `settings.security.extensions` | **ReadOnly** | Security preset display only |
| `settings.security.ip` | **Hidden** | No auto-configured values (disabled) |
| `settings.security.integrity` | **Hidden** | No auto-configured values |
| `settings.security.environment` | **Hidden** | Auto-configured: production environment, debug OFF |

#### Global Settings > Themes & Plugin Management

| Menu Key | Classification | Notes |
|----------|---------------|-------|
| `settings.themes` | **Full** | To be reconsidered after marketplace is ready |
| `settings.themes.index` | Full (inherited) | |
| `settings.themes.add` | Full (inherited) | May change to Hidden after marketplace is ready |
| `settings.plugins` | **Full** | Same as above |
| `settings.plugins.index` | Full (inherited) | |
| `settings.plugins.add` | Full (inherited) | Same as above |

#### Global Settings > System

| Menu Key | Classification | Notes |
|----------|---------------|-------|
| `settings.systems` | **Partial** | |
| `settings.systems.cache` | **Full** | Cache clearing is a day-to-day operation |
| `settings.systems.database` | **Hidden** | Hidden due to high risk of accidental data loss |
| `settings.systems.api` | **Hidden** | Exclusive to Advanced Mode |
| `settings.systems.logs` | **ReadOnly** | View only (export disabled) |
| `settings.systems.info` | **ReadOnly** | View only |

---

## Detailed Specifications for Each Menu

### Media Settings (`media.settings`) --- Partial

#### Items Editable in Simple Mode

- Allowed file types (checkbox format: images, PDF, video, etc.)
- File size limits by file type

#### Items Auto-Configured in Simple Mode

| Item | Auto-Configured Value | Rationale |
|------|----------------------|-----------|
| MIME content validation | **Enabled** | Security foundation. Prevents file spoofing |
| SVG sanitization | **Enabled** | Prevents script execution within SVG files |
| ZIP security check | **Enabled** | Detects zip bombs and malicious files |

---

### Member Master (`members.index`) --- Full (with View-Level Restrictions)

#### UI Elements Hidden in Simple Mode

| Element | Reason |
|---------|--------|
| Force logout all users button | Hidden due to high accident rate. Individual logout is allowed |
| Force logout specific user button | <!-- TODO: Determine whether to allow individual logout --> |

---

### Member Roles (`members.roles`) --- Hidden

No auto-configured values. The default permission template is maintained. Permission changes are only possible in Advanced Mode.

---

### General Settings > Admin Panel Settings (`settings.base.admin`) --- GuideOnly

When the page is accessed, the following is displayed:

- Current admin panel URL (read-only)
- SSL enforcement status (read-only)
- "To change these settings, please switch to Advanced Mode" message
- Link button to the mode switching page

---

### General Settings > Mail Settings (`settings.base.mail`) --- Partial

#### Items Editable in Simple Mode

- System administrator email address

#### Items Hidden/Guided in Simple Mode

- Mail server settings (SMTP) -> Displays "Configure in Advanced Mode"

<!-- TODO: Finalize Partial detailed specifications for mail settings -->

---

### Security > Login Settings (`settings.security.login`) --- Partial

#### Items Editable in Simple Mode

- Login notification
  - Disabled
  - Always enabled
  - Enabled only for different devices/IPs
  - Use member's profile settings

#### Items Auto-Configured in Simple Mode

| Item | Auto-Configured Value | Rationale |
|------|----------------------|-----------|
| Enable login attempt limit | **Enabled** | Foundation of brute-force protection |
| Maximum attempts | **5 attempts** | Commonly recommended value |
| IP maximum attempts | **20 attempts** | Accounts for shared IP environments |
| Time window | **15 minutes** | Commonly recommended value |
| Lockout duration | **30 minutes** | Commonly recommended value |
| Enable lockout notification | **Enabled** | Required for attack detection |

<!-- TODO: Final confirmation of auto-configured values -->

---

### Security > Two-Factor Authentication Settings (`settings.security.two-fa`) --- Partial

#### Items Editable in Simple Mode

- Two-factor authentication
  - Disabled
  - Always enabled
  - Enabled only for different devices/IPs
  - Use member's profile settings
- Passkey authentication ON/OFF

#### Items Auto-Configured in Simple Mode

| Item | Auto-Configured Value | Rationale |
|------|----------------------|-----------|
| Maximum registered devices | **5 devices** | Based on typical usage |
| Authentication expiration | **10 minutes** | Commonly recommended value |
| Authentication email resend interval | **60 seconds** | Spam prevention |
| 2FA attempt limit | **5 attempts** | Brute-force protection |
| Attempt limit time window | **15 minutes** | Commonly recommended value |
| Lockout duration | **30 minutes** | Commonly recommended value |
| 2FA lockout notification | **Enabled** | For security monitoring |
| Recovery code generation count | **10 codes** | Commonly recommended value |
| Recovery code regeneration interval | **24 hours** | Abuse prevention |

<!-- TODO: Final confirmation of auto-configured values -->

---

### Security > CAPTCHA Settings (`settings.security.captcha`) --- Partial

#### Items Editable in Simple Mode

- CAPTCHA provider selection
  - CloudFlare Turnstile
  - Google reCAPTCHA
  - Google reCAPTCHA Enterprise
- API key input (site key and secret key)
- Forms to enable CAPTCHA on (per-core/per-plugin toggles)

#### Auto Behavior in Simple Mode

- After API keys are configured, forms from newly installed plugins have CAPTCHA enabled by default

<!-- TODO: Finalize Partial detailed specifications -->

---

### Security > Password Settings (`settings.security.password`) --- Hidden

| Item | Auto-Configured Value | Rationale |
|------|----------------------|-----------|
| Minimum password length | **8 characters** | NIST-recommended minimum |
| Require uppercase letters | **Enabled** | Ensures complexity |
| Require digits | **Enabled** | Ensures complexity |
| Require symbols | **Enabled** | Ensures complexity |
| Password reset feature | **Enabled** | Ensures usability |
| Have I Been Pwned API check | **Enabled** | Prevents use of compromised passwords |

<!-- TODO: Final confirmation of auto-configured values -->

---

### Security > Error Notification Settings (`settings.security.notifications`) --- Hidden

| Item | Auto-Configured Value | Rationale |
|------|----------------------|-----------|
| Error notification feature | **Enabled** | For early detection of issues |
| Notification log level | **Critical and above** (Emergency, Alert, Critical) | Notifies only for critical issues. Error/Warning can be noise |

<!-- TODO: Final confirmation of auto-configured values -->

---

### Security > Session Settings (`settings.security.session`) --- Hidden

| Item | Auto-Configured Value | Rationale |
|------|----------------------|-----------|
| Session lifetime | **120 minutes (default value maintained)** | Based on typical usage |

<!-- TODO: Final confirmation of auto-configured values -->

---

### Security > CSP (`settings.security.csp`) --- Hidden

| Item | Auto-Configured Value | Rationale |
|------|----------------------|-----------|
| Enable CSP | **ON** | Security foundation |
| CSP mode | **Standard mode** | Development mode is unnecessary |
| Log violations | **ON** | Essential for investigating issues |
| Exclude dev tools violations | **ON** | Reduces noise from browser extensions, etc. |
| Enable blocklist matching | **ON** | For security monitoring |
| Blocklist matching mode | **Warn only** | Block mode risks blocking legitimate scripts (Google Analytics, etc.) |
| Matching category: Tracking & Ads | **ON** | Safe with warn-only mode since all categories can be enabled |
| Matching category: Malware & Phishing | **ON** | Same as above |
| Matching category: Cryptocurrency mining | **ON** | Same as above |

---

### Security > Extension Settings (`settings.security.extensions`) --- ReadOnly

The following is displayed in read-only mode:

- Current security preset name (Development Mode / Standard Mode / Strict Mode / Custom Mode)
- Current values for each setting item
- "To change these settings, please switch to Advanced Mode" message

---

### Security > IP Access Control (`settings.security.ip`) --- Hidden

No auto-configured values (remains disabled). Can only be configured in Advanced Mode to prevent self-lockout from misconfiguration.

---

### Security > File Integrity Check (`settings.security.integrity`) --- Hidden

No auto-configured values. Hidden only, as there is currently no auto-execution feature.

---

### Security > Environment Settings (`settings.security.environment`) --- Hidden

| Item | Auto-Configured Value | Rationale |
|------|----------------------|-----------|
| Operating environment | **Production** | Maintains the setting from installation |
| Debug mode | **OFF** | Prevents information leakage in production |

---

### System > Database Management (`settings.systems.database`) --- Hidden

No auto-configured values (no automatic cleanup). Can only be operated in Advanced Mode to prevent data loss.

---

### System > API Management (`settings.systems.api`) --- Hidden

No auto-configured values (API remains disabled). Can only be configured in Advanced Mode to prevent unnecessary API endpoint exposure.

---

### System > Log Management (`settings.systems.logs`) --- ReadOnly

The following is displayed in read-only mode:

- Audit log viewing
- File log viewing
- Export feature is hidden

---

### System > System Information (`settings.systems.info`) --- ReadOnly

The following is displayed in read-only mode:

- Server environment information
- PHP version
- Database information
- Dixlase version

---

## Auto-Configured Values List

Summary of auto-configured values for items set to Hidden in Simple Mode.

### Confirmed

| Menu | Item | Auto-Configured Value |
|------|------|-----------------------|
| CSP | CSP enabled | ON |
| CSP | CSP mode | Standard mode |
| CSP | Log violations | ON |
| CSP | Exclude dev tools violations | ON |
| CSP | Blocklist matching | ON (warn only) |
| CSP | Matching categories | All enabled |
| Environment Settings | Operating environment | Production |
| Environment Settings | Debug mode | OFF |
| Media Settings | MIME content validation | Enabled |
| Media Settings | SVG sanitization | Enabled |
| Media Settings | ZIP security check | Enabled |
| IP Access Control | --- | Disabled (no settings) |
| File Integrity Check | --- | No configured values |
| Database Management | --- | No automatic cleanup |
| API Management | --- | API disabled |

### Pending Confirmation (TODO)

| Menu | Item | Tentative Value | Confirmation Needed |
|------|------|----------------|---------------------|
| Password Settings | Minimum length | 8 characters | Whether sufficient for NIST compliance |
| Password Settings | Uppercase/digits/symbols required | All enabled | Whether excessive |
| Password Settings | Have I Been Pwned | Enabled | Whether external API dependency is acceptable |
| Login Settings | Maximum attempts | 5 attempts | Whether too restrictive |
| Login Settings | IP maximum attempts | 20 attempts | Whether shared IP consideration is sufficient |
| Login Settings | Time window | 15 minutes | |
| Login Settings | Lockout duration | 30 minutes | |
| Two-Factor Auth | Maximum devices | 5 devices | |
| Two-Factor Auth | Authentication expiration | 10 minutes | |
| Two-Factor Auth | Recovery code count | 10 codes | |
| Error Notifications | Notification level | Critical and above | Whether to include Error level |
| Session | Lifetime | 120 minutes | |

---

## UI/UX Rules

### Sidebar Display

| Classification | Display | Icon | Color |
|----------------|---------|------|-------|
| Full | Normal display | None | --- |
| Partial | Normal display | None | --- |
| Hidden | **Hidden** | --- | --- |
| ReadOnly | Lock icon next to menu name | `fas fa-lock` | --- |
| GuideOnly | Directions icon next to menu name | `fas fa-directions` | `text-purple-400` |

### In-Page UI

#### Partial Pages

- Editable fields are displayed as normal forms
- Auto-configured fields are either hidden or displayed in a disabled state
- Auto-configured sections display the message: "In Simple Mode, recommended values are automatically applied for these settings"
- The save button is displayed only when there are editable fields

#### ReadOnly Pages

- All fields are in a disabled state
- The save button is hidden
- A banner at the top of the page displays: "These settings are read-only. To make changes, please switch to Advanced Mode."
- Includes a link to the mode switching page

#### GuideOnly Pages

- Current setting values are displayed in read-only format
- A banner at the top of the page displays: "These settings cannot be changed in Simple Mode."
- A "Switch to Advanced Mode" button is placed in a prominent position

### Behavior When Switching Modes

- **Simple to Advanced**: All features are immediately unlocked. Auto-configured values remain as-is (until explicitly changed)
- **Advanced to Simple**: A confirmation modal is displayed. A warning is shown that Hidden items will be overwritten with auto-configured values

---

## Implementation Architecture

### Control Layers

```
+---------------------------------------------------+
| 1. Sidebar (sidebar.blade.php)                    |
|    -> Hidden: Menu hidden                         |
|    -> ReadOnly/GuideOnly: Displayed with icon     |
+---------------------------------------------------+
| 2. Middleware (CheckMenuAccess)                   |
|    -> Hidden: 403 or redirect                     |
|    -> ReadOnly/GuideOnly: GET only, POST rejected |
+---------------------------------------------------+
| 3. Controller                                     |
|    -> Partial: Merge auto-configured values       |
|    -> ReadOnly: Reject store/update actions       |
+---------------------------------------------------+
| 4. View (Blade)                                   |
|    -> Partial: Per-field disabled control          |
|    -> ReadOnly: All fields disabled + banner      |
|    -> GuideOnly: Guide UI + mode switch link      |
+---------------------------------------------------+
```

### When Auto-Configured Values Are Applied

1. **During installation**: When Simple Mode is selected, auto-configured values are saved to the database
2. **When switching from Advanced to Simple Mode**: Auto-configured values for Hidden items are overwritten in the database
3. **When saving Partial items**: Auto-configured values are merged into the submitted data before saving

---

## Plugin Menu Support

### Basic Policy

- Plugins can define default visibility levels for their menu keys in `config/admin/mode.php`
- If not defined, the default is **Full** (all operations available)
- Plugin navigation settings (`config/admin/navigation.php`) are merged into the main navigation using the existing `_insert_before` / `_insert_after` mechanism

### Plugin Configuration Example

```php
// plugins/ExamplePlugin/config/admin/mode.php
return [
    'simple_defaults' => [
        'example' => MenuVisibility::Full,
        'example.settings' => MenuVisibility::Partial,
        'example.advanced' => MenuVisibility::Hidden,
    ],
];
```

<!-- TODO: Finalize implementation details for plugin mode configuration merging -->

---

## Changelog

| Date | Content |
|------|---------|
| 2026-02-12 | Initial version. Defined the 5-classification model, menu classification list, and auto-configured values (some tentative) |
