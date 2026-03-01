# Safe Mode Guide

> **[Japanese version / 日本語版](../../ja/operations/emergency/safe-mode-guide.md)**

## Overview

Dixlase provides a **multi-level safe mode system** for crash recovery. When misconfigured CSP settings, buggy plugins, or broken themes prevent normal access, safe mode allows administrators to regain control without needing SSH or CLI access.

All safe modes are **session-based** (no database writes) and require **admin authentication** to activate.

## Safe Mode Levels

| Mode | URL Parameter | Effect | Scope |
|------|--------------|--------|-------|
| **CSP** | `?safe=csp` | Disables CSP headers | All pages (session) |
| **Plugins** | `?safe=plugins` | Disables plugin routes and assets | Admin only (session) |
| **Theme** | `?safe=theme` | Disables theme, renders minimal fallback layout | Front-end only (session) |

### Backward Compatibility

`?safe=1` is treated as `?safe=csp` for backward compatibility.

### Multiple Modes

Multiple modes can be activated simultaneously with comma separation:

```
https://your-site.com/admin?safe=csp,plugins
```

---

## How to Activate

### Method 1: URL Parameter (Recommended)

Append `?safe=<mode>` to any page URL while logged in as an admin:

```
https://your-site.com/admin?safe=csp
https://your-site.com/admin?safe=plugins
https://your-site.com/?safe=theme
https://your-site.com/admin?safe=csp,plugins
```

### Method 2: CSP Auto-activation

When CSP settings are saved and the confirmation modal times out (10 seconds), the system automatically rolls back and activates CSP safe mode.

---

## Safe Mode Banners

When a safe mode is active, a colored banner appears at the top of the admin panel:

| Mode | Banner Color | Message |
|------|-------------|---------|
| CSP | Red | "CSP Safe Mode is Active" |
| Plugins | Orange | "Plugin Safe Mode is Active" |
| Theme | Purple | "Theme Safe Mode is Active" |

Each banner includes:
- A link to the relevant settings page
- A "Disable" button to deactivate that specific mode
- A "Disable All" button (when multiple modes are active)

---

## How to Deactivate

### From the Admin Panel

1. Click the "Disable" button on the safe mode banner
2. Or navigate to the relevant settings page and fix the configuration

### Programmatically (Artisan)

```bash
# Disable CSP safe mode
php artisan csp:disable-safe-mode
```

---

## Mode Details

### CSP Safe Mode (`?safe=csp`)

**When to use:** CSP settings are misconfigured and JavaScript or styles are being blocked, preventing normal use of the admin panel.

**What it does:**
- `CspBuilder` skips CSP header output entirely
- All scripts and styles load without restrictions
- A red banner warns the administrator

**Security impact:** XSS attack surface increases. Deactivate as soon as possible after fixing CSP configuration.

**Related settings:** Admin > Settings > Security > CSP Settings

### Plugin Safe Mode (`?safe=plugins`)

**When to use:** A buggy plugin causes fatal errors, broken admin pages, or other issues that prevent normal admin access.

**What it does:**
- Plugin routes (controllers under `Plugins\` namespace) redirect to the admin dashboard
- Plugin assets are not loaded (`load_active_assets()` skips plugin assets)
- Core admin pages remain fully functional

**What it does NOT do:**
- Plugin ServiceProviders have already booted (configs, languages, views are already registered). This is harmless because:
  - Registered configs/languages/views don't execute code on their own
  - Routes are blocked at the middleware level
  - Assets are blocked at Blade render time

**Related settings:** Admin > Settings > Plugins

### Theme Safe Mode (`?safe=theme`)

**When to use:** A broken theme prevents the front-end from rendering correctly.

**What it does:**
- Overrides the `themes::` view namespace to point to `resources/views/safe-theme/`
- Renders pages with a minimal HTML5 layout (core Tailwind CSS + Alpine.js only)
- Theme assets are not loaded (`load_front_assets()` skips theme assets)

**Important:** Theme safe mode only affects front-end pages. The admin panel is unaffected.

**Related settings:** Admin > Settings > Themes

---

## Architecture

### Session Keys

Each mode stores its state in the session:

| Mode | Session Key |
|------|------------|
| CSP | `safe_mode_csp` |
| Plugins | `safe_mode_plugins` |
| Theme | `safe_mode_theme` |

### Key Components

| Component | File | Role |
|-----------|------|------|
| `SafeMode` (Enum) | `app/Enums/SafeMode.php` | Mode definitions and helper methods |
| `SafeModeService` | `app/Services/SafeModeService.php` | Activation, deactivation, state queries |
| `SafeMode` (Middleware) | `app/Http/Middleware/SafeMode.php` | URL parameter detection, theme namespace override |
| `BlockPluginRoutes` | `app/Http/Middleware/BlockPluginRoutes.php` | Plugin route blocking |
| `SafeModeBanner` | `app/View/Components/Security/SafeModeBanner.php` | Banner rendering |
| `SafeModeController` | `app/Http/Controllers/Admin/SafeModeController.php` | Disable/disable-all endpoints |

### Request Flow

1. **SafeMode middleware** detects `?safe=` parameter and activates modes in session
2. **BlockPluginRoutes middleware** checks if the route controller is in `Plugins\` namespace and redirects if plugin safe mode is active
3. **CspBuilder** checks `safe_mode_csp` session and skips CSP headers if active
4. **AssetHelper** checks `safe_mode_plugins` / `safe_mode_theme` and skips assets accordingly
5. **SafeModeBanner component** reads active modes and renders banners

### Logging

All safe mode activations and deactivations are logged to the `admin_activity` channel with:
- Mode name
- User ID and name
- IP address and User-Agent
- Timestamp

---

## Cautions

- **Safe modes are temporary recovery measures.** Deactivate them as soon as the underlying issue is resolved.
- **CSP safe mode** disables XSS protection. Do not leave it active in production.
- **Plugin safe mode** only blocks routes and assets. Plugin ServiceProviders still boot (configs, translations, views are registered but harmless).
- **Theme safe mode** provides a minimal layout with no theme styling. Content is still rendered.
- Safe modes are per-session. Other logged-in administrators are not affected.
- Logging out ends the session and deactivates all safe modes.
