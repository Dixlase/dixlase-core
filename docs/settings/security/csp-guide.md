# Content Security Policy (CSP) Complete Guide

## Table of Contents

1. [Overview](#overview)
2. [CSP Mode Specifications](#csp-mode-specifications)
3. [Safe Mode](#safe-mode)
4. [Basic Usage](#basic-usage)
5. [Command Line Tools](#command-line-tools)
6. [Plugin & Theme Development](#plugin--theme-development)
7. [Dixlase Initialization Conventions](#dixlase-initialization-conventions)
8. [Troubleshooting](#troubleshooting)
9. [FAQ](#faq)

---

## Overview

Dixlase implements Content Security Policy (CSP) to mitigate the risk of XSS attacks and data leakage. CSP is a security feature that instructs the browser which resources are allowed to be loaded and executed.

### Security Policy

Dixlase adopts a phased CSP implementation:

- ✅ `unsafe-inline` is eliminated (nonce-based approach)
- ⚠️ `unsafe-eval` is permitted in a limited manner (due to Alpine.js usage)

**Why is unsafe-eval permitted?**

To use Alpine.js v3, Standard Mode permits `unsafe-eval` in a limited manner. The reasons are:

1. Leveraging Alpine.js dynamic expression evaluation capabilities
2. Ensuring compatibility with third-party plugins
3. Improving developer experience

In future versions (v2.0 and beyond), migration to Alpine.js CSP Build will be considered with the goal of completely eliminating `unsafe-eval`. New code should follow the **[Alpine.js CSP-Compatible Coding Rules](alpine-csp-coding-rules.md)**.

### Nonce-Based Approach

Dixlase uses a nonce (one-time token) approach. A unique nonce is generated per request, and only inline scripts with the correct nonce are allowed to execute.

```html
<!-- Only scripts with a nonce are executed -->
<script nonce="abc123...">
    // This script will be executed
</script>

<!-- Scripts without a nonce are blocked -->
<script>
    // This script will be blocked
</script>
```

---

## CSP Mode Specifications

Dixlase provides three CSP modes, allowing flexible adjustment of the balance between developer experience and security.

### Mode Overview

| Mode | Enforcement | Inline Execution | onclick etc. | unsafe-eval | Plugin Compatibility | Use Case |
|------|-------------|-----------------|-------------|-------------|---------------------|----------|
| **Development** | Report-Only | Allowed | Allowed | Allowed | Maximum | Development & Debugging |
| **Standard** | Enforced | Nonce helper only | Warning | Allowed (for Alpine.js) | High | Recommended for Production |
| **Strict** | Enforced | Completely forbidden | Forbidden | Forbidden | CSP Ready only | Maximum Security |

---

### 1. Development Mode

#### Purpose
Prioritizes the developer experience when developing themes/plugins. Everything works, but future issues are made visible.

#### CSP Enforcement
- **Report-Only** (records only, does not block)
- Denied domains only produce warnings (can be force-blocked via settings)

#### Allowed Scope

**Scripts**
- ✅ External scripts (`'self'` + declared domains)
- ✅ Inline execution code (`unsafe-inline`)
- ✅ Attribute events (`onclick` etc.)
- ✅ `unsafe-eval` (for Vite/HMR support)
- ✅ Raw `<script>...</script>` (without nonce)

**Styles**
- ✅ Inline CSS (`unsafe-inline`)
- ✅ External CSS

**Other**
- ✅ JSON embedding with `type="application/json"`
- ✅ `data-*` attributes
- ✅ All plugins (including those with `requires_inline_js: true`)

#### Expected Experience
- Everything works
- CSP violations are recorded in the admin panel
- Allows advance identification of "this plugin may break in Standard/Strict mode"

---

### 2. Standard Mode - Recommended for Production

#### Purpose
Default for production operation. Raises XSS resistance to a practical level without sacrificing too much compatibility.

#### CSP Enforcement
- **Enforced (blocking)**
- Also uses `Report-To/Report-URI` (block + report)

#### Allowed Scope

**Scripts**
- ✅ External scripts (`'self'` + declared domains)
- ✅ Inline execution code: **only via helpers (with nonce)**
  - `@dixScript ... @enddixScript`
  - `Dixlase::script()`
- ❌ Raw `<script>...</script>` (without nonce)
- ⚠️ Attribute events (`onclick` etc.): **warning (allowed during transition period)**
- ⚠️ `unsafe-eval`: **permitted in a limited manner for Alpine.js**
  - Required for Alpine.js v3 dynamic expression evaluation
  - Migration to Alpine.js CSP Build is being considered for future versions
- 🔄 `strict-dynamic`: optional (default OFF for compatibility)

**Styles**
- ✅ Inline CSS: via helper (nonce) or hash
- ✅ External CSS (declared domains)
- ❌ Unrestricted `unsafe-inline` is not used

**Data Passing**
- ✅ JSON embedding with `type="application/json"`
- ✅ `data-*` attributes

**iframe / object etc.**
- ✅ `object-src 'none'` (recommended)
- ✅ `base-uri 'self'` (recommended)
- ✅ `frame-ancestors`: `'none'` for admin panel (clickjacking protection)

#### Expected Experience
- Most plugins work
- Even when using inline scripts, they work safely when following the "Dixlase way"
- onclick etc. will be deprecated in the future (warnings are shown)

---

### 3. Strict Mode

> ⚠️ **Note: Strict Mode is currently not implemented**
>
> Strict Mode is being considered for implementation in a future version.
> Currently, only Development Mode and Standard Mode are available.

#### Purpose
Achieves maximum protection using only CSP Ready themes/plugins.
Eliminates the need for inline execution including in the admin panel, maximizing compatibility with reviews, audits, and tampering detection.

#### CSP Enforcement
- **Enforced (blocking)**
- Also uses `Report-To`
- Dixlase-side rules also "reject if inline execution code exists" (CMS policy)

#### Allowed Scope

**Scripts**
- ✅ External scripts only (`'self'` + declared domains)
- ✅ CSP entry point scripts (bootloader)
  - Core-controlled external JS like `bootstrap.js`
- ❌ Inline execution code (**forbidden even with nonce**: Dixlase policy)
- ❌ Attribute events (`onclick` etc.)
- ❌ `unsafe-eval`
- ✅ `strict-dynamic` is ON (assuming core controls the entry point)

**Only "non-executing" inline content is allowed**
- ✅ `<script type="application/json">` (passing configuration/initial data)
- ✅ `data-*` attributes (declarative initialization)
- ❌ Even minimal `<style>` is forbidden in principle (move to external CSS when possible)

**Additional Admin Panel Guards (Strict only)**
- ✅ `frame-ancestors 'none'`
- ✅ `form-action 'self'`
- ✅ `object-src 'none'`
- ✅ `base-uri 'none'`
- ✅ `upgrade-insecure-requests` (when possible)
- 🔮 `require-trusted-types-for 'script'` is being considered for the future

**Plugin & Theme Compatibility Rules**
- ❌ `requires_inline_js: true` → **cannot be activated**
- ❌ No external dependency declarations in plugin.json (or dynamic insertion) → **warning or blocked**
- ⚠️ Blocklist match hit → warning/block per settings

#### Expected Experience
- Incompatible plugins are rejected from the start
- In exchange, **accidents are unlikely in a CSP Ready world**
- Core provides boot conventions so "UI can be built without writing inline code"

---

### Mode Comparison Quick Reference

| Item | Development | Standard | Strict |
|------|-------------|----------|--------|
| **CSP Enforcement** | Report-Only (recommended) | Enforced | Enforced |
| **Inline JS** | Allowed | **Nonce helper only** | **Forbidden (even with nonce)** |
| **onclick etc.** | Allowed | Forbidden in principle (warnings allowed during transition) | Forbidden |
| **External JS** | Allowed | Allowed (declaration required) | Allowed (declaration required) |
| **unsafe-eval** | Allowed (when needed) | Forbidden | Forbidden |
| **strict-dynamic** | Optional | Optional (leaning toward recommended) | Recommended (assumed ON) |
| **Config Passing (JSON script)** | Allowed | Allowed | Allowed |
| **data-* Initialization** | Allowed | Allowed | Recommended |
| **Unified Across Admin Panel** | Optional | Recommended | Required |

---

## Safe Mode (CSP)

If you cannot access the admin panel due to CSP configuration issues, **CSP Safe Mode** (`?safe=csp`) can temporarily disable CSP headers.

CSP Safe Mode is part of the Dixlase multi-level safe mode system. For details including plugin and theme safe modes, see the **[Safe Mode Guide](safe-mode-guide.md)**.

### Activating CSP Safe Mode

- **Automatic:** If the confirmation modal times out (10 seconds) after saving CSP settings, the system automatically rolls back and enables safe mode
- **Manual:** Append `?safe=csp` to the URL (admin login required)

### How to Deactivate

Click the "Deactivate" button on the banner, or fix the settings from the CSP settings page.

---

## Basic Usage

### Using in Blade Templates

#### @cspNonce Directive

The simplest way to add a nonce attribute to inline scripts.

```blade
<script @cspNonce>
    // Inline script
    console.log('Hello, World!');
</script>
```

#### @cspNonceValue Directive

Outputs only the nonce value.

```blade
<script nonce="@cspNonceValue">
    // Inline script
</script>
```

#### Helper Functions

```php
// Get the nonce value
$nonce = csp_nonce();

// Get the nonce attribute (nonce="xxx" format)
$attr = csp_nonce_attr();

// Check if CSP is enabled
$enabled = csp_is_enabled();

// Get the CSP mode
$mode = csp_get_mode();
```

### Dynamically Adding Directives

Directives can be dynamically added from controllers or Blade templates.

```php
// In a controller
csp_add_script_src('https://cdn.example.com');
csp_add_style_src('https://fonts.googleapis.com');
csp_add_connect_src('https://api.example.com');

// Generic addition
csp_add_directive('frame-src', ['https://youtube.com', 'https://vimeo.com']);
```

---

## Dixlase Initialization Conventions

In Strict Mode, the core provides mechanisms to initialize UI without writing inline execution code.

### 1. Widget Registration

**HTML (Template)**
```html
<div data-dix-widget="gallery"
     data-dix-props='{"autoplay":true,"speed":400}'>
</div>
```

**JavaScript (Plugin)**
```javascript
Dixlase.widgets.register("gallery", (el, props) => {
  // el: target DOM element
  // props: settings restored from data-dix-props
  mountGallery(el, props);
});
```

### 2. Action Registration (Event Delegation)

**HTML**
```html
<button data-dix-action="contact.submit">Submit</button>
```

**JavaScript**
```javascript
Dixlase.actions.register("contact.submit", (ctx) => {
  ctx.form.requestSubmit();
});
```

### 3. Page-Specific Initialization

**HTML**
```html
<body data-dix-page="admin.dashboard">
```

**JavaScript**
```javascript
Dixlase.pages.register("admin.dashboard", () => {
  bootDashboard();
});
```

### 4. Loading Configuration Data

**JSON script approach**
```html
<script type="application/json" id="app-config">
  {"theme": "dark", "lang": "ja"}
</script>
```

```javascript
const config = Dixlase.config.load('app-config');
```

**data-* approach**
```html
<div id="widget" data-dix-config-theme="dark"></div>
```

```javascript
const theme = Dixlase.config.get(element, 'theme');
```

---

## Command Line Tools

### Checking CSP Status

Check the current CSP settings:

```bash
php artisan csp:status
```

**Example output:**
```
CSP Status
==========
Enabled: Yes
Mode: Standard (1)
Log Violations: Yes
Exclude Dev Tools: Yes
Safe Mode: No

Trusted Domains:
  - https://cdn.example.com
  - https://fonts.googleapis.com

Blocklist Categories:
  - tracking (15 domains)
  - ads (23 domains)
```

### Changing CSP Mode

Change the CSP mode from the command line:

```bash
# Switch to Development Mode
php artisan csp:mode development

# Switch to Standard Mode
php artisan csp:mode standard

# Switch to Strict Mode
php artisan csp:mode strict
```

### Enabling/Disabling CSP

```bash
# Enable CSP
php artisan csp:enable

# Disable CSP
php artisan csp:disable
```

### Managing Safe Mode

```bash
# Enable safe mode
php artisan csp:enable-safe-mode

# Disable safe mode
php artisan csp:disable-safe-mode

# Check safe mode status
php artisan csp:status
```

### Viewing CSP Violation Logs

Display CSP violation logs:

```bash
# Show latest violation logs
php artisan csp:violations

# Show latest 10 entries
php artisan csp:violations --limit=10

# Show only a specific directive
php artisan csp:violations --directive=script-src
```

### Clearing CSP Cache

Clear the CSP settings cache:

```bash
php artisan csp:clear
```

### Emergency CSP Disable

If you cannot access the admin panel, you can disable CSP directly in the `.env` file:

```env
CSP_ENABLED=false
```

Or from the command line:

```bash
php artisan csp:disable --force
```

---

## Plugin & Theme Development

### CspPolicyProvider Interface

When plugins or themes require external resources, they can implement the `CspPolicyProvider` interface to add CSP directives.

```php
<?php

namespace Plugins\MyPlugin\App\Providers;

use App\Contracts\CspPolicyProvider;
use Illuminate\Support\ServiceProvider;

class MyPluginServiceProvider extends ServiceProvider implements CspPolicyProvider
{
    public function boot(): void
    {
        // Register the plugin's CSP policy
        app(\App\Services\Csp\CspPolicyRegistry::class)
            ->registerProvider('my-plugin', $this);
    }

    public function getCspDirectives(): array
    {
        return [
            'script-src' => ['https://cdn.example.com'],
            'style-src' => ['https://fonts.googleapis.com'],
            'connect-src' => ['https://api.example.com'],
            'img-src' => ['https://images.example.com'],
        ];
    }
}
```

### CSP Declarations in plugin.json

Plugin CSP requirements can be declared in `plugin.json`:

```json
{
  "name": "DixlaseGallery",
  "csp": {
    "requires_inline_js": false,
    "requires_inline_css": false,
    "external_scripts": [
      "https://cdn.example.com/gallery.js"
    ],
    "external_styles": [
      "https://cdn.example.com/gallery.css"
    ],
    "connect_src": [
      "https://api.example.com"
    ]
  },
  "assets": {
    "scripts": [
      "resources/js/gallery.js"
    ],
    "styles": [
      "resources/css/gallery.css"
    ]
  }
}
```

### CSP Ready Checklist

- [ ] No inline execution code is used
- [ ] No attribute events such as onclick are used
- [ ] All external dependencies are declared in plugin.json
- [ ] Initialization uses `Dixlase.widgets.register()` etc.
- [ ] Configuration is passed via `data-*` or `type="application/json"`
- [ ] `requires_inline_js: false` is explicitly set

### Developing CSP Ready Plugins

#### 1. Avoid Inline Scripts

```blade
{{-- ❌ Avoid --}}
<button onclick="doSomething()">Click</button>

{{-- ✅ Recommended (Standard Mode) --}}
<button id="myButton">Click</button>
<script @cspNonce>
    document.getElementById('myButton').addEventListener('click', doSomething);
</script>

{{-- ✅ Recommended (Strict Mode) --}}
<button data-dix-action="my-plugin.do-something">Click</button>
```

#### 2. Declare External Scripts via CspPolicyProvider

```php
class MyPluginServiceProvider extends ServiceProvider implements CspPolicyProvider
{
    public function getCspDirectives(): array
    {
        return [
            'script-src' => ['https://cdn.example.com'],
            'style-src' => ['https://fonts.googleapis.com'],
        ];
    }
}
```

#### 3. Avoid Dynamic Script Generation

```javascript
// ❌ Avoid
element.innerHTML = '<script>alert("XSS")</script>';

// ✅ Recommended
const script = document.createElement('script');
script.textContent = 'console.log("Safe")';
document.body.appendChild(script);

// ✅ Safer (using Dixlase utility)
Dixlase.utils.setText(element, 'Safe text');
```

---

## Admin Panel Settings

### Security Settings

CSP can be configured from "Global Settings > Security Settings" in the admin panel.

- **Enable CSP**: Enable/disable CSP header delivery
- **CSP Mode**:
  - Development Mode: Only records violations, does not block
  - Standard Mode: Only allows nonce-tagged inline
  - Strict Mode: Completely forbids inline
- **Log Violations**: Records CSP violations to log files
- **Trusted Domains**: Domains allowed to load external resources from
- **Custom Directives**: Specify advanced settings in JSON format

### Viewing Logs

CSP violation logs can be viewed under the "CSP Violations" tab in "Global Settings > System > Logs".

---

## Troubleshooting

### Inline Scripts Are Being Blocked

1. Add the `@cspNonce` directive
2. Or move the script to an external file
3. In Strict Mode, use `Dixlase.widgets.register()` etc.

### External Resources Are Being Blocked

1. Add the relevant domain to "Trusted Domains" in the admin panel
2. Or implement `CspPolicyProvider` in the plugin
3. Declare external dependencies in plugin.json

### Issues During Development

1. Set the CSP mode to "Development Mode"
2. Check violation reports in the browser console
3. Add the necessary directives

### Temporarily Disabling CSP

#### Method 1: Use Safe Mode (Recommended)

Append `?safe=csp` to the URL to enable CSP Safe Mode. See the **[Safe Mode Guide](safe-mode-guide.md)** for details.

#### Method 2: Disable from Admin Panel

Go to "Global Settings > Security Settings > CSP Settings" and turn off "Enable CSP".

#### Method 3: Disable from Command Line

```bash
php artisan csp:disable
```

#### Method 4: Disable via .env File

```env
CSP_ENABLED=false
```

#### Method 5: Emergency Force Disable

If you cannot access the admin panel:

```bash
php artisan csp:disable --force
```

---

## FAQ

### Q1. Does allowing nonce-tagged inline scripts in Standard Mode reduce security?
**A:** Not if used correctly. A nonce is "an execution permit valid only for this response," and attackers cannot know it in advance. However, the following conditions must be met:
- Nonce is random per response
- Nonce cannot be retrieved from JS
- Dangerous APIs (innerHTML, eval, etc.) are not heavily used within inline JS

### Q2. Does Strict Mode reduce maintainability?
**A:** In the short term, the learning cost increases, but it clearly improves in the medium to long term. Reasons:
- Eliminates dependency on inline JS
- Separates logic from presentation
- No more CSP violation headaches
- Easier to support future strict-dynamic / Trusted Types

### Q3. Why are onclick etc. problematic?
**A:** From a CSP perspective, `script-src-attr 'unsafe-inline'` is required, making it an easy attack vector for XSS. Using event delegation (`data-dix-action`) avoids this problem.

### Q4. How do I make an existing plugin compatible with Strict Mode?
**A:** You can migrate with the following steps:
1. Move inline scripts to external JS files
2. Register initialization functions with `Dixlase.widgets.register()`
3. Replace onclick etc. with `data-dix-action`
4. Explicitly set `requires_inline_js: false` in plugin.json

**Implementation example: CSP compliance for authentication screens**

The Dixlase two-factor authentication screen has been externalized as a reference implementation for CSP Strict Mode compliance:

```html
<!-- Before: Inline script -->
<script @cspNonce>
document.addEventListener('DOMContentLoaded', function() {
    // 200+ lines of inline code...
});
</script>

<!-- After: Externalized script -->
@push('scripts')
<script src="{{ asset('build/assets/components/two-fa/js/email-challenge.js') }}" @cspNonce></script>
<script @cspNonce>
document.addEventListener('DOMContentLoaded', function() {
    window.initEmailChallenge({
        resendAction: '{{ $resendAction }}',
        csrfToken: '{{ csrf_token() }}',
        codeLength: {{ $codeLength }},
        translations: { /* ... */ }
    });
});
</script>
@endpush
```

**Related files:**
- `resources/src/components/two-fa/js/email-challenge.js` - Email authentication
- `resources/src/components/two-fa/js/passkey-challenge.js` - Passkey authentication
- `resources/src/components/two-fa/js/recovery-code-challenge.js` - Recovery code authentication

These files are included as build targets in `vite.config.js` and work in CSP Strict Mode.

### Q5. What is Safe Mode?
**A:** It is a recovery feature for when you cannot access the admin panel or frontend due to CSP, plugin, or theme issues. CSP Safe Mode (`?safe=csp`) temporarily disables CSP, Plugin Safe Mode (`?safe=plugins`) disables plugin routes and assets, and Theme Safe Mode (`?safe=theme`) renders the frontend with a minimal layout. See the **[Safe Mode Guide](safe-mode-guide.md)** for details.

### Q6. What happens if I don't confirm within 10 seconds after saving CSP settings?
**A:** The settings are automatically rolled back to the previous configuration and CSP Safe Mode is enabled. This prevents being locked out of the admin panel due to incorrect settings.

### Q7. Can I change CSP settings from the command line?
**A:** Yes, you can change the mode with the `php artisan csp:mode [development|standard|strict]` command. You can also toggle CSP on/off with `php artisan csp:enable` / `php artisan csp:disable`.

---

## Migration Strategy

### Phase 1: Development Mode (Current)
- All plugins work
- Record CSP violations and identify problem areas

### Phase 2: Migrate to Standard Mode
- Rewrite to helper-based inline
- Gradually remove onclick etc.
- Verify plugin compatibility

### Phase 2.5: Apply Alpine CSP-Compatible Coding Rules (Current)
- Write new code using Alpine CSP Build-compatible patterns
- `Alpine.data()`-based component design
- See **[Alpine.js CSP-Compatible Coding Rules](alpine-csp-coding-rules.md)** for details

### Phase 3: Migrate to Alpine CSP Build (Future)
- Switch to the `@alpinejs/csp` package
- Gradually rewrite existing components for CSP compatibility
- Complete elimination of `unsafe-eval`

### Phase 4: Strict Mode (Future)
- Make core and official plugins CSP Ready
- Design that relies entirely on external JS
- Achieve maximum security environment

---

## Reference Links

- [CSP Level 3 Specification](https://www.w3.org/TR/CSP3/)
- [Google CSP Evaluator](https://csp-evaluator.withgoogle.com/)
- [MDN: Content Security Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)
- [Alpine.js CSP-Compatible Coding Rules](alpine-csp-coding-rules.md)
- [Plugin Permission Infrastructure Guidelines](plugin-permission-guidelines.md)
- [Security Settings Guide](security-settings.md)
