# Dixlase CMS Plugin API Boundary

**Version:** dev
**Last Updated:** 2026-07-04
**Purpose:** Define the public Plugin API boundary for the AGPL license exception clause (see LICENSE)

This document defines all components that form the "Plugin API" -- the public interfaces,
services, and configurations that plugins and themes are permitted to use without triggering
AGPL copyleft obligations under the Dixlase Plugin and Theme Exception.

---

## Stability Pledge

**Frozen as of:** v0.1.0 release (see `CHANGELOG.md` for the exact release date)
**Coverage:** every Plugin API element listed in this document, for the version range `^0.1`
**Backward compatibility:** no breaking changes will be introduced within `^0.1`. Plugins and themes built against v0.1.0 will continue to work on every v0.1.x release without modification.

### Deprecation Policy

When a Plugin API element must change in a backward-incompatible way:

1. **Minor release** (e.g. v0.2.0): the change ships as an additive new symbol. The old symbol remains and is marked with PHPDoc `@deprecated`, plus a runtime warning where feasible.
2. **Next major release** (e.g. v1.0.0 after a v0.x deprecation): the deprecated symbol is removed. A migration guide is published in `CHANGELOG.md` and the replacement is described in this document.
3. Plugin and theme authors are guaranteed at least one full minor cycle to migrate.

### Announcement Channels

Additions, deprecations, and breaking changes to the Plugin API are announced through:

- **`CHANGELOG.md`** in the core repository (authoritative source)
- **GitHub Releases** of `Dixlase/Core`
- **GitHub Discussions** of `Dixlase/Core` for advance notice and migration guidance

Plugin and theme authors are encouraged to subscribe to GitHub releases of the core repository.

### Identifier Naming Conventions

Public string identifiers exposed by Dixlase (API scopes, permission keys, event names, webhook event types, audit log actions, plugin capabilities, …) follow the conventions documented in [`docs/development/naming.md`](docs/development/naming.md). Identifier names are part of the public API surface — once shipped, they cannot be safely renamed because plugins, issued API keys, stored audit rows, and external webhook integrations all hard-code them.

When introducing a new identifier of an existing kind, follow the format defined in that document. When introducing a new identifier *kind*, update the document first.

### Declaring the API Version You Target

Plugins and themes must declare which Plugin API revision they were written against in their manifest:

```json
"requires": {
    "dixlase_api": "^0.1"
}
```

This is the contract the extension agrees to follow. Core verifies this declaration on every load and (in future releases) refuses to register incompatible extensions.

- **Field:** `requires.dixlase_api` (semver constraint, e.g. `"^0.1"`)
- **Current core API version:** `0.1.0` (constant `App\Extension\ExtensionApi::CURRENT_VERSION`)
- **Supported range:** `^0.1` (constant `App\Extension\ExtensionApi::SUPPORTED_RANGE`)
- **Bump rules:** MINOR bumps (0.1 → 0.2) ship additive, non-breaking changes; MAJOR bumps (0.x → 1.0) ship breaking changes to the surface documented below.

Enforcement schedule:

| Phase | Behavior |
|---|---|
| **v0.1.0** (current) | Advisory only — missing or incompatible declarations log a warning and lower the extension's health score, but registration proceeds. |
| **v0.2.0** | Strict — extensions without a satisfying declaration are refused at load time and shown as `Incompatible` in the admin extension list. |
| **v1.0.0** | Strict + the field drives WASM PHP interpreter image selection when the in-process boundary is replaced by per-extension WASM sandboxes. |

The existing `requires.dixlase` field tracks the **whole core product** version range and is separate from `requires.dixlase_api`. Bumping core for an unrelated bug fix does not bump the API contract version; only changes to the documented Plugin API surface (this document) bump it.

For new extensions, scaffolding via `dls:make:plugin` / `dls:make:theme` includes the field automatically. For existing extensions, use `dls:plugin:update-json <Name> --add-api-version` or `dls:theme:update-json <Name> --add-api-version` to insert the declaration in place.

---

## Licensing Your Plugin or Theme

Plugins and themes that interact with Dixlase CMS **exclusively through the interfaces listed in this document** are **not** considered derivative works of Dixlase CMS. You may distribute them under any license of your choice, including proprietary licenses.

This right is granted by the **Dixlase Plugin and Theme Exception** (see the `LICENSE` file, Section "Additional permission under GNU AGPL version 3 section 7"). The exception applies when all of the following conditions are met:

1. Your plugin/theme communicates with Dixlase CMS only through the Plugin API defined in this document.
2. Your plugin/theme does not modify, replace, or monkey-patch any core source file.
3. Your plugin/theme does not bypass or replicate internal core implementations.
4. Your plugin/theme is loaded through the standard loading mechanism (`PluginLoaderTrait` / `ThemeLoaderTrait`) and resides in the `plugins/` or `themes/` directory.

If any condition is not met, your plugin/theme is subject to the full AGPL-3.0 terms.

---

## 1. Contracts / Interfaces

### 1.1 Service Contracts

| Contract | Description |
|---|---|
| `App\Contracts\Action\ActionInterface` | Contract for all CMS business operations |
| `App\Contracts\Action\Actor` | Represents the entity performing an operation |
| `App\Contracts\Admin\AdminNavigationManagerInterface` | Admin panel navigation manager interface |
| `App\Contracts\Backup\BackupServiceInterface` | Backup service interface |
| `App\Contracts\Backup\RestoreServiceInterface` | Restore service interface |
| `App\Contracts\Cookie\ConsentStateProviderInterface` | Read the current visitor&#039;s cookie consent state. |
| `App\Contracts\CspPolicyProvider` | CSP Policy Provider Interface |
| `App\Contracts\Encryption\FileEncryptionServiceInterface` | File encryption service interface |
| `App\Contracts\Extension\ExtensionSourceInterface` | Extension Source Provider Interface |
| `App\Contracts\FileIntegrity\FileIntegrityServiceInterface` | File integrity check service contract |
| `App\Contracts\I18n\LocalizedUrlProvider` | Exposes alternate-language URLs for the current request. |
| `App\Contracts\I18n\MissingTranslationHandler` | Decides what to do when a route exists but no translation is available |
| `App\Contracts\LegalPage\LegalPageServiceInterface` | Contract for legal page registry service |
| `App\Contracts\Logging\LogServiceInterface` | Log service contract |
| `App\Contracts\Mail\MailServiceInterface` | Mail service contract |
| `App\Contracts\Multilingual\SingletonTranslationResolver` | Storage operations for **singleton** translatable content. |
| `App\Contracts\Multilingual\TranslatableContentProvider` | Primary-locale value source for **singleton** translatable content. |
| `App\Contracts\Revisionable` | Each plugin/theme has its own revision table and Eloquent model, and by |
| `App\Contracts\RouteSlugProvider` | Route Slug Provider Interface |
| `App\Contracts\Security\PolicyEvaluatorInterface` | Attribute-Based Access Control (ABAC) hook for `PermissionService`. |
| `App\Contracts\Security\RiskEvaluatorInterface` | Conditional-access risk scoring hook. |
| `App\Contracts\Security\SecretProviderInterface` | Pluggable secret-store backend. |
| `App\Contracts\Signature\SignatureWaiverServiceInterface` | first-party DixlaseDevKit (not subject to the AGPL exception). Add @api and |
| `App\Contracts\Site\SiteContextInterface` | Provides the current site context for the request. |
| `App\Contracts\Theme\ThemePermissionServiceInterface` | Theme permission management service interface |
| `App\Contracts\TranslationResolver` | Translation Resolver Contract |
| `App\Contracts\TwoFaInterface` | Interface for users with two-factor authentication functionality |
| `App\Contracts\TwoFa\TwoFaPasskeyServiceInterface` | Contract for Passkey (WebAuthn) authentication service |
| `App\Contracts\Verification\FileVerificationServiceInterface` | File integrity verification service interface |

### 1.2 Licensing Contracts

| Contract | Description |
|---|---|
| `App\Contracts\Licensing\LicenseVerifierInterface` | License verification contract (reserved for marketplace Phase 2) |

### 1.3 Plugin Integration Contracts

| Contract | Description |
|---|---|
| `App\Contracts\PluginIntegration\BlockProviderInterface` | Contract for plugins/themes that provide reusable Block components |
| `App\Contracts\PluginIntegration\CaptchaFormProviderInterface` | Contract for plugins that provide CAPTCHA forms |
| `App\Contracts\PluginIntegration\DashboardNotificationProviderInterface` | Contract for plugins that provide dashboard notifications |
| `App\Contracts\PluginIntegration\DashboardWidgetProviderInterface` | Contract for plugins that provide dashboard widgets |
| `App\Contracts\PluginIntegration\DeployProtectionRegistryInterface` | Aggregated view of every &quot;protect from cross-environment sync |
| `App\Contracts\PluginIntegration\LinkableInterface` | Minimal contract for linkable content |
| `App\Contracts\PluginIntegration\LinkableProviderInterface` | Contract for plugins that provide linkable content |
| `App\Contracts\PluginIntegration\MenuProviderInterface` | Contract for plugins that provide navigation menus |
| `App\Contracts\PluginIntegration\PreviewProviderInterface` | Contract for plugins that provide preview data |
| `App\Contracts\PluginIntegration\PrivacyDataProviderInterface` | Contract that a plugin (or core subsystem) implements to declare which |
| `App\Contracts\PluginIntegration\PrivacyPolicyProviderInterface` | Privacy policy provider contract |
| `App\Contracts\PluginIntegration\SeoMetaProviderInterface` | Interface to provide read/write access to SEO meta information per content |

### 1.4 Plugin Capability Contracts

| Contract | Description |
|---|---|
| `App\Contracts\Plugin\ApiResourceProviderInterface` | API resource provider interface |
| `App\Contracts\Plugin\ContentProviderCapableInterface` | Interface declaring content provider capability |
| `App\Contracts\Plugin\EditorCapableInterface` | Interface declaring editor provision functionality |
| `App\Contracts\Plugin\MailCapableInterface` | Interface declaring mail sending functionality |
| `App\Contracts\Plugin\PluginCapabilityInterface` | Base interface for plugin capability declaration |
| `App\Contracts\Plugin\PluginPermissionServiceInterface` | Contract for plugin permission management service |
| `App\Contracts\Plugin\SignatureVerifierInterface` | Signature verification contract |

### 1.5 Repository Contracts

| Contract | Description |
|---|---|
| `App\Contracts\Repositories\ApiSettingRepositoryInterface` | API settings repository interface |
| `App\Contracts\Repositories\FrontSettingRepositoryInterface` | Front settings repository interface |
| `App\Contracts\Repositories\MediaRepositoryInterface` | Media repository interface |
| `App\Contracts\Repositories\MediaSettingRepositoryInterface` | Media settings repository interface |
| `App\Contracts\Repositories\PluginRepositoryInterface` | Plugin repository interface |
| `App\Contracts\Repositories\SecuritySettingRepositoryInterface` | Security settings repository interface |
| `App\Contracts\Repositories\SettingRepositoryInterface` | Settings repository base interface |
| `App\Contracts\Repositories\SiteSettingRepositoryInterface` | Site settings repository interface |
| `App\Contracts\Repositories\ThemeRepositoryInterface` | Theme repository interface |

---

## 2. Traits for Plugin Use

| Trait | Description |
|---|---|
| `App\Traits\AdminInterfaceTrait` | Trait for initializing common admin panel interface |
| `App\Traits\AdminLoggedInTrait` | Trait for common initialization after admin panel login |
| `App\Traits\AuditableTrait` | Trait for automatic audit logging of models |
| `App\Traits\ConfigLoaderTrait` | Settings file loading utility |
| `App\Traits\CustomFilesLoaderTrait` | Custom file override loading |
| `App\Traits\EmailVerificationTrait` | Trait that provides common logic for email verification |
| `App\Traits\HasPermissions` | HasPermissions Trait |
| `App\Traits\HasRevisions` | By using this trait in models that implement `App\Contracts\Revisionable`, |
| `App\Traits\LoginIdentifierCheckTrait` | Common trait for login identifier verification |
| `App\Traits\LoginNotificationTrait` | Common trait for login notifications |
| `App\Traits\MailTestTrait` |  |
| `App\Traits\ManagesAccountTrait` | Common account management logic |
| `App\Traits\ManagesContentFiles` | Content file management trait |
| `App\Traits\ManagesTwoFaTrait` | Common processing for two-factor authentication management |
| `App\Traits\PasskeyLoginTrait` | Common trait for passkey login |
| `App\Traits\PasswordResetTrait` | Trait that provides common logic for password reset |
| `App\Traits\PluginLoaderTrait` | Plugin resource loading mechanism |
| `App\Traits\RegistersCspPolicy` | CSP policy registration trait |
| `App\Traits\ThemeLoaderTrait` | Theme resource loading mechanism |
| `App\Traits\TranslatableTrait` | Translatable Trait |
| `App\Traits\TwoFa\TwoFaAuthenticationTrait` | Trait that provides two-factor authentication flow control functionality |
| `App\Traits\VerifiesCaptcha` |  |

---

## 3. Base Controllers for Extension

| Controller | Description |
|---|---|
| `App\Http\Controllers\Admin\AdminController` | Admin panel base controller |
| `App\Http\Controllers\Admin\AdminLoggedInController` | Admin panel controller that requires authentication |

---

## 4. Data Transfer Objects (DTOs)

### 4.1 Action DTOs

- `App\DTO\Action\ActionResult`

### 4.2 API DTOs

- `App\DTO\Api\ApiResourceCollection`
- `App\DTO\Api\ApiResourceDTO`

### 4.3 Audit DTOs

- `App\DTO\Audit\AuditLogPayload`

### 4.4 Backup DTOs

- `App\DTO\Backup\BackupResultDTO`
- `App\DTO\Backup\RestoreResultDTO`

### 4.5 Core DTOs

- `App\DTO\Core\CoreIntegrityResult`

### 4.6 Editor DTOs

- `App\DTO\Editor\EditorInfo`

### 4.7 Encryption DTOs

- `App\DTO\Encryption\EncryptionResultDTO`

### 4.8 Extension DTOs

- `App\DTO\Extension\CompatibilityResult`
- `App\DTO\Extension\ReleaseInfo`

### 4.9 File Integrity DTOs

- `App\DTO\FileIntegrity\BaselineDTO`
- `App\DTO\FileIntegrity\FileChangeDTO`
- `App\DTO\FileIntegrity\ScanResultDTO`
- `App\DTO\FileIntegrity\ScanTargetDTO`

### 4.10 Licensing DTOs

- `App\DTO\Licensing\LicenseVerificationResult`

### 4.11 Logging DTOs

- `App\DTO\Logging\LogContextDTO`
- `App\DTO\Logging\LogEntryDTO`

### 4.12 Mail DTOs

- `App\DTO\Mail\MailAttachmentDTO`
- `App\DTO\Mail\MailConfigDTO`
- `App\DTO\Mail\MailMessageDTO`
- `App\DTO\Mail\MailResultDTO`

### 4.13 Plugin Integration DTOs

- `App\DTO\PluginIntegration\BlockContext`
- `App\DTO\PluginIntegration\BlockDescriptor`
- `App\DTO\PluginIntegration\CaptchaFormDTO`
- `App\DTO\PluginIntegration\DashboardNotificationDTO`
- `App\DTO\PluginIntegration\DashboardWidgetDTO`
- `App\DTO\PluginIntegration\DeployProtectionSource`
- `App\DTO\PluginIntegration\LinkableDTO`
- `App\DTO\PluginIntegration\MenuDTO`
- `App\DTO\PluginIntegration\MenuItemDTO`
- `App\DTO\PluginIntegration\PaginatedResultDTO`
- `App\DTO\PluginIntegration\PreviewDTO`
- `App\DTO\PluginIntegration\PreviewFieldDTO`
- `App\DTO\PluginIntegration\SearchQueryDTO`
- `App\DTO\PluginIntegration\SeoMetaDTO`

### 4.14 Plugin Privacy DTOs

- `App\DTO\PluginPrivacy\UserDataDeletionDTO`
- `App\DTO\PluginPrivacy\UserDataExportDTO`

### 4.15 Plugin DTOs

- `App\DTO\Plugin\CapabilityResolutionResult`
- `App\DTO\Plugin\DeclaresVerificationResult`
- `App\DTO\Plugin\EnabledPluginRecord`
- `App\DTO\Plugin\SignatureVerificationResult`

### 4.16 RouteSlug DTOs

- `App\DTO\RouteSlug\RegisteredSlug`

### 4.17 Security DTOs

- `App\DTO\Security\LoginContext`
- `App\DTO\Security\RiskScore`

---

## 5. Enums

### 5.1 System Enums

- `App\Enums\AccessRiskLevel`
- `App\Enums\ActorType`
- `App\Enums\AppearanceMode`
- `App\Enums\AuthenticationMode`
- `App\Enums\ConsentCategory`
- `App\Enums\ContentEditorType`
- `App\Enums\ContentStatus`
- `App\Enums\ContentStorageType`
- `App\Enums\ExtensionCompatibilityStatus`
- `App\Enums\Locale`
- `App\Enums\LogLevel`
- `App\Enums\LoginIdentifierMode`
- `App\Enums\Permission`
- `App\Enums\PolicyDecision`
- `App\Enums\SettingScope`

### 5.2 User/Role Enums

- `App\Enums\Gender`
- `App\Enums\MemberRole`
- `App\Enums\MemberStatus`

### 5.3 Security Enums

- `App\Enums\OperationRiskLevel`
- `App\Enums\PasskeyMode`
- `App\Enums\TwoFaMethod`

### 5.4 Plugin Privacy Enums

- `App\Enums\PluginPrivacy\DeletionMode`

---

## 6. Blade Components

### 6.1 Form Components

`x-form-text`, `x-form-email`, `x-form-textarea`, `x-form-select`,
`x-form-checkbox`, `x-form-checkbox-group`, `x-form-toggle`, `x-form-toggle-group`,
`x-form-radio-group`, `x-form-radio-card-group`, `x-form-color`, `x-form-range`,
`x-form-label`, `x-form-error`, `x-form-help-text`, `x-form-button`, `x-form-hidden`,
`x-form-input-with-label`, `x-form-password-tools`, `x-form-required-badge`,
`x-form-content-editor`

### 6.2 UI Components

`x-ui-modal`, `x-ui-modal-vanilla`, `x-ui-notification`, `x-ui-livewire-notification`,
`x-ui-livewire-modal`, `x-ui-message`, `x-ui-flash-message`, `x-ui-status-badge`,
`x-ui-pagination`, `x-ui-pagination-controls`, `x-ui-tooltip`, `x-ui-datetime`,
`x-ui-maintenance-banner`, `x-ui-admin-maintenance-banner`, `x-ui-system-banner`,
`x-ui-appearance-mode-selector`, `x-ui-language-switcher`, `x-ui-admin-bar`

`x-ui-datetime` renders a UTC-stored datetime as a `<time>` element converted into the site's
`display_timezone`. Props: `:value` (Carbon/DateTimeInterface/string/int/null),
`format` (PHP date format or one of `date|datetime|full|iso`), `empty-label` (fallback string).

### 6.3 Admin Components

`x-admin.save-button`, `x-admin.delete-button`, `x-admin.danger-zone`,
`x-admin.account-status`, `x-admin.right-sidebar`,
`x-admin.mode-guide-banner`, `x-admin.mode-partial-notice`, `x-admin.mode-readonly-banner`,
`x-admin.theme-preview-container`, `x-admin.theme-preview-sidebar`,
`x-admin.theme-preview-sidebar-section`,
`x-revision.list`, `x-revision.diff`

### 6.4 Front-end Components

`x-front.button`, `x-front.card`, `x-front.breadcrumb`, `x-front.navigation`

### 6.5 Content Editor Components

`x-content-editor.tabs`, `x-content-editor.preview-tabs`,
`x-content-editor.preview-pane`, `x-content-editor.new-tab-preview`,
`x-content-editor.scroll-buttons`, `x-content-editor.storage-info`,
`x-content-editor.type-badge`

### 6.6 Security & Auth Components

`x-captcha`,
`x-security.login-attempt-limit-settings`, `x-security.login-identifier-mode-selector`,
`x-security.login-notification-selector`, `x-security.passkey-device-settings`,
`x-security.password-settings`, `x-security.session-settings`,
`x-security.two-fa-detailed-settings`, `x-security.two-fa-general-settings`,
`x-two-fa.management`, `x-two-fa.mode-selector`, `x-two-fa.individual-settings`,
`x-auth.login-form`, `x-auth.account-verification`, `x-auth.forgot-password`,
`x-auth.reset-password`, `x-auth.verification-notice`, `x-auth.login-field`,
`x-application-logo`, `x-auth-session-status`

### 6.7 Media Components

`x-media.picker`, `x-media.selector`

### 6.8 Layout Templates

Plugins/themes may extend the following Blade layouts via `@extends('layouts.{name}')`. These are stable layouts marked with `@api` and committed to backwards compatibility under the [Stability Pledge](#stability-pledge).

| Layout | Usage | Description |
|---|---|---|
| `layouts.admin` | `@extends('layouts.admin')` | Admin panel layout (sidebar, top bar, dark mode, flash messages). Used by all admin pages including plugin admin views. |

---

## 7. Middleware & API Surface for Plugins

### 7.1 API Middleware

| Group | Description |
|---|---|
| `plugin.api` | Authenticated API routes (ApiKey auth + rate limiting + logging) |
| `plugin.api.public` | Public API routes (rate limiting + logging, no auth) |

### 7.2 Web Middleware

| Group | Description |
|---|---|
| `plugin` | Basic plugin routes (session, cookies, view sharing, bindings) |
| `plugin.web` | Plugin front-end routes (with IP filtering) |
| `plugin.admin` | Plugin admin routes (auth required + IP filtering) |

### 7.3 API URL Versioning & Plugin Route Layout

REST API endpoints live under `/api/v1/...`. Plugins place their API routes in:

```
plugins/{Name}/routes/api/v1.php
```

The core auto-loader wraps the file in `Route::prefix('api/v1')->middleware(EnsurePluginActiveOnSite::class.':'.$pluginSlug)->group(...)`, so plugin authors only write the slug-relative path inside (e.g. `Route::prefix('my-plugin')->group(...)`) and the resulting URL is `/api/v1/my-plugin/...`. When the plugin is disabled on the resolved site, `App\Http\Middleware\EnsurePluginActiveOnSite` short-circuits the request with a 404 JSON envelope before the controller runs.

Reserved namespaces under `/api/v1/` — plugin routes **must not** collide with any of these:

| Path | Owner |
|---|---|
| `/api/v1/health` | Core (public liveness probe) |
| `/api/v1/resources/{type}/{slug}` | Future DixlaseApi plugin (auto-discovers `ApiResourceProviderInterface`) |
| `/api/v1/privacy/...` | Future Privacy API (built on `PrivacyDataProviderInterface`) |

The legacy `routes/api.php` (no `v1.php` filename) still loads as-is for backwards compatibility but is **deprecated**; the loader emits a `Log::warning` on every boot until plugins migrate. New plugins should use `routes/api/v1.php` from day one. Full contract: [REST API Versioning](docs/development/api-reference/versioning.md).

### 7.4 API Response Envelope

The unified `{data, meta, links}` / `{error: {code, message, details?}, meta}` envelope documented in [REST API Versioning](docs/development/api-reference/versioning.md) is implemented by the following classes. Plugin endpoints that emit JSON should extend or call these so the response shape stays in sync with core's exception handler:

| Class | Purpose |
|---|---|
| `App\Http\Resources\BaseApiResource` | Abstract base for single-resource endpoints. Subclass and implement `toArray($request)`; the base adds `meta.site_id`, `meta.timestamp`, and `links.self` automatically. |
| `App\Http\Resources\BaseApiCollection` | Abstract base for list endpoints. Same envelope plus auto-generated `meta.pagination` and `links.next` / `links.prev` when wrapping a Laravel paginator. |
| `App\Support\Api\ApiErrorResponse` | Static factory `make(code, status, message, details)` returning the unified error envelope. Also exposes `meta()` so plugin code building responses by hand can stay shape-consistent. |

Error messages in the envelope are always English (clients should map the stable `code` field to their own translation table). API error responses must never 3xx-redirect; if a plugin endpoint can be reached without authentication, document the public scope in its own README rather than emitting a redirect.

API-key authentication is handled by `App\Http\Middleware\AuthenticateApiKey` (route alias `auth.api`), which uses `ApiErrorResponse::make()` for `missing_credentials` / `invalid_credentials` / `ip_not_allowed` / `insufficient_scope` failures. Use of network-scope keys (site_id = NULL, CLI-issued via `dls:api:create-network-key`) is automatically audited at `severity = notice` under action `network_api_key_used`.

### 7.5 Direct Middleware-Group Registration

A plugin's ServiceProvider may register a middleware class directly into the global `web` middleware group instead of attaching it per route. Two methods are available:

| Method | Position | Recommended use |
|---|---|---|
| `Router::pushMiddlewareToGroup('web', $class)` | Appended to the end of the `web` group | Cross-cutting response decorators that need the full request to be resolved before they run (e.g. SEO meta-tag injection, late header rewriting). |
| `Router::prependMiddlewareToGroup('web', $class)` | Inserted at the front of the `web` group | Early-exit handlers that must run before any other `web` middleware (e.g. URL redirect lookups that short-circuit the response with a 3xx). |

Plugins that perform either of these registrations **must** declare `permissions.system.register_middleware: true` in `plugin.json`. The permission scanner detects both `pushMiddlewareToGroup(...)` and `prependMiddlewareToGroup(...)` call sites.

#### Reserved use of `prependMiddlewareToGroup('web', ...)`

`prependMiddlewareToGroup('web', ...)` is **reserved for redirect-class plugins** (i.e. plugins whose primary responsibility is to short-circuit the request with a 301/302/307/308 before any application logic runs). Other plugin categories should use `pushMiddlewareToGroup` instead.

Why this matters:

- Multiple plugins prepending to the same group results in a "last-prepend-wins" ordering that depends on plugin boot order, which itself depends on activation order in the database. There is no API to enforce a deterministic order across redirect-style plugins, so the reservation keeps the front of the chain owned by a single category of behavior.
- A non-redirect plugin that prepends will be silently re-ordered if a redirect plugin is later activated, which can lead to subtle correctness bugs (the non-redirect plugin no longer sees the request first).

If a future use case genuinely needs a deterministic priority across multiple non-redirect prependers, the path forward is to introduce a core `MiddlewareRegistry` with explicit priorities; until then this reservation is the contract.

---

## 8. Configuration Structures

### 8.1 Admin Navigation

**File:** `plugins/{Name}/config/admin/navigation.php`

```php
return [
    'menu_key' => [
        'text' => 'admin/navigation.menu_key',
        'route' => 'admin.route.name',
        'icon' => 'fas fa-fw fa-icon',
        'children' => [ /* ... */ ],
        '_insert_before' => 'other_menu_key',
        '_insert_after' => 'other_menu_key',
    ],
];
```

### 8.2 Role Permissions

**File:** `plugins/{Name}/config/admin/roles.php`

```php
return [
    'plugin_feature_name' => [
        'super_admin' => true,
        'admin' => true,
        'editor' => true,
        'author' => false,
    ],
];
```

### 8.3 Database Cleanup

**File:** `plugins/{Name}/config/admin/database-cleanup.php`

```php
return [
    'table_key' => [
        'table' => 'actual_table_name',
        'date_column' => 'created_at',
        'default_days' => 30,
        'name' => 'admin/settings/path.key',
        'description' => 'admin/settings/path.desc',
        'enabled' => true,
    ],
];
```

---

## 9. Injectable Services

**Naming conventions:** services follow these suffix conventions to make their role clear. When implementing a plugin-side service, prefer the same suffixes for consistency with the core API.

| Suffix | Role | Examples |
|---|---|---|
| `*Service` | Business logic / orchestration acting on data | `PasswordService`, `EditorManager` |
| `*Registry` | Registration, lookup, and listing of pluggable items | `PermissionRegistry`, `CspPolicyRegistry`, `RouteSlugRegistry` |
| `*Manager` | Lifecycle control (enable, disable, initialize, refresh) | `AdminNavigationManager` |
| `*Resolver` | Dependency or capability resolution at request time | `PluginServiceResolver` |

### 9.1 Plugin/Theme Services

The services marked with `@api` below can be directly injected via DI.
The remaining services are accessed via their respective interfaces (see Section 1).

- `App\Services\Plugin\DeclaresVerifier` — `@api`, direct DI
- `App\Services\Plugin\PluginPermissionService` — `@api`, direct DI
- `App\Services\Plugin\PluginServiceResolver` — `@api`, direct DI
- `App\Services\Plugin\CoreSignatureVerifier` — use `SignatureVerifierInterface` instead
- `App\Services\Theme\ThemePermissionService`
- `App\Services\PluginMigrator`
- `App\Services\PluginMigrationRepository`

### 9.2 Core Services

- `App\Services\PermissionService`
- `App\Services\PermissionRegistry`
- `App\Services\PasswordService`
- `App\Services\PageContentService`
- `App\Services\FrontPageContentService`
- `App\Services\ContentFileService`
- `App\Services\ContentPreviewService`
- `App\Services\DatabaseCleanupService`
- `App\Services\SystemNotificationService`
- `App\Services\Auth\AuthContextRegistryService`
- `App\Services\Editor\EditorManager`
- `App\Services\LegalPageService`
- `App\Services\MailServerValidatorService`
- `App\Services\RouteSlugRegistry`
- `App\Services\SystemWarningService`

### 9.3 Mail & Logging

- `App\Contracts\Mail\MailServiceInterface` (via container binding)
- `App\Contracts\Logging\LogServiceInterface` (via container binding)

### 9.4 Security Services

- `App\Services\CaptchaTestService`
- `App\Services\CaptchaFailoverService`
- `App\Services\CaptchaBypassService`
- `App\Services\FileIntegrityService`
- `App\Services\AdminLoginLockoutService`
- `App\Services\TwoFa\TwoFaService`
- `App\Services\TwoFa\TwoFaPasskeyService`
- `App\Services\TwoFa\TwoFaRecoveryCodeService`
- `App\Services\EmailAuthenticationService`

### 9.5 API & CSP Services

- `App\Services\ApiRateLimitService`
- `App\Services\Csp\CspPolicyRegistry`
- `App\Services\Csp\CspBuilder`
- `App\Services\Csp\CspNonceGenerator`

### 9.6 Helper Classes

| Helper | Description |
|---|---|
| `App\Helpers\CaptchaHelper` | CAPTCHA enablement check, widget rendering, and driver configuration |
| `App\Helpers\ConfigHelper` | Security and session configuration retrieval (incl. `getDisplayTimezone()`) |
| `App\Helpers\DateTimeHelper` | UTC→display timezone conversion and formatting (named formats, DST safe, null tolerant) |
| `App\Helpers\ComposerLocalHelper` | composer-local.json management |
| `App\Helpers\GitExcludeHelper` | .git/info/exclude file management |
| `App\Helpers\GitIgnoreHelper` | .gitignore file management |
| `App\Helpers\GlobalHelper` | Global helper functions (shortcode_parse, etc.) |
| `App\Helpers\LocaleHelper` | Locale detection and language settings |
| `App\Helpers\LoginHelper` | Login authentication, 2FA check, and session management |
| `App\Helpers\LoginLockoutHelper` | Login lockout detection and recording |
| `App\Helpers\PluginHelper` | Plugin enablement status, paths, and route loading |
| `App\Helpers\TwoFaHelper` | Two-factor authentication code generation, sending, and settings |

### 9.6.1 Multisite Services (`App\Services\Site\*`)

The forward-looking multisite layer. Plugin and theme code that writes
new per-site behaviour should depend on these services rather than on
direct Eloquent / DB queries against `site_settings` or `global_settings`.

| Service | Description |
|---|---|
| `App\Services\Site\SiteContext` | Resolves the current site for the request (concrete implementation of `SiteContextInterface`) |
| `App\Services\Site\SettingDefinition` | Value object describing a setting key (`name`, `scope`, `default`, `type`) |
| `App\Services\Site\SettingDefinitionRegistry` | Central registry of setting definitions; plugins call `register()` in their service provider |
| `App\Services\Site\SettingResolver` | Reads/writes settings via the 3-mode scope model (`Global` / `PerSite` / `Overridable`) |
| `App\Services\Site\SiteStorage` | Site-aware filesystem helper (`private()`, `public()`, `global()` rooted at the multisite tree) |
| `App\Services\Site\Exceptions\UnknownSettingException` | Raised when an unregistered key is read; ensures every key is declared up front |

In addition, `App\Models\Traits\BelongsToSite` is the per-site Eloquent
trait that adds `site()` belongsTo + a current-site Global Scope +
auto-assigns `site_id` on creation. Apply it to any plugin model whose
table has a `site_id` column.

### 9.6.2 Cache Helpers (`App\Support\Cache\*`)

Builder helpers that produce cache keys following the Dixlase naming
convention `dixlase:{scope}:{owner}:{domain}:{key}`. Use these instead of
constructing keys by hand so plugin/theme cache entries do not collide
and can be partitioned per site once multisite is visible.

| Helper | Description |
|---|---|
| `App\Support\Cache\CacheKey` | Static Builder for core / plugin / theme / tag keys, plus `site($id)` entry into the site-scoped Builder |
| `App\Support\Cache\SiteScopedCacheKey` | Fluent Builder returned by `CacheKey::site($id)`; produces composite keys partitioned by site id |

See [Cache Key Convention](docs/development/cache-key-convention.md) for
the full format reference, tag rules, and worked examples.

### 9.7 Actors

- `App\Actors\MemberActor` — Member actor implementation for the Action framework

### 9.8 Events

- `App\Events\AuditLogCreated` — Audit log creation event (for SIEM integration and plugin hooks). Delivers a frozen, versioned `App\DTO\Audit\AuditLogPayload` as `$event->payload`. Schema version: `AuditLogCreated::SCHEMA_VERSION`.
- `App\Events\SecurityAlertEvent` — Security alert event
- `App\Events\DixlaseEvents` — Constants for all core event names that plugins can listen to (see below)

**Event name constants** (`App\Events\DixlaseEvents::*`): plugins may use any of the following constants as `Event::listen()` targets. The class is part of the Plugin API; constant additions are non-breaking. Removing a constant follows the deprecation policy.

| Category | Constants |
|---|---|
| Backup lifecycle | `BACKUP_STARTED`, `BACKUP_COMPLETED`, `BACKUP_FAILED`, `BACKUP_CLEANUP_STARTED`, `BACKUP_CLEANUP_COMPLETED`, `BACKUP_RESTORE_STARTED`, `BACKUP_RESTORE_COMPLETED`, `BACKUP_RESTORE_FAILED` |
| Backup encryption | `BACKUP_ENCRYPTING`, `BACKUP_ENCRYPTED`, `BACKUP_ENCRYPTION_FAILED` |
| Backup verification | `BACKUP_VERIFYING`, `BACKUP_VERIFIED`, `BACKUP_VERIFICATION_FAILED` |
| Deploy | `DEPLOY_BEFORE`, `DEPLOY_AFTER`, `DEPLOY_FAILED`, `DEPLOY_SYNC_BEFORE`, `DEPLOY_SYNC_AFTER`, `DEPLOY_DATABASE_BEFORE`, `DEPLOY_DATABASE_AFTER` |
| Translation | `LOCALE_CHANGED`, `TRANSLATION_MODEL_RETRIEVED`, `TRANSLATION_MODEL_SAVING`, `TRANSLATION_MODEL_SAVED`, `TRANSLATION_MODEL_DELETED`, `TRANSLATION_FIELD_UPDATED`, `TRANSLATION_FIELD_DELETED` |
| Plugin lifecycle | `PLUGIN_INSTALLING`, `PLUGIN_INSTALLED`, `PLUGIN_ACTIVATING`, `PLUGIN_ACTIVATED`, `PLUGIN_DEACTIVATING`, `PLUGIN_DEACTIVATED`, `PLUGIN_UNINSTALLING`, `PLUGIN_UNINSTALLED`, `PLUGIN_UPDATING`, `PLUGIN_UPDATED` |
| Theme lifecycle | `THEME_ACTIVATING`, `THEME_ACTIVATED` |
| Cache | `CACHE_CLEARING`, `CACHE_CLEARED` |
| Maintenance | `MAINTENANCE_ENABLED`, `MAINTENANCE_DISABLED` |
| Security | `INTEGRITY_SCAN_STARTED`, `INTEGRITY_SCAN_COMPLETED`, `SECURITY_ALERT`, `BOT_DETECTED`, `LOGIN_ANOMALY_DETECTED` |
| Audit | `AUDIT_LOG_CREATED` |
| AI (reserved) | `AI_OPERATION_LOGGED` |

Helper methods: `DixlaseEvents::all()` returns every event name; `DixlaseEvents::byCategory(string $category)` filters by category prefix.

### 9.9 Validation Rules

- `App\Rules\UniqueContentSlug` — Content slug uniqueness validation
- `App\Rules\UniqueRouteSlug` — Route slug uniqueness validation

### 9.10 Facades (Convenience Accessors)

Convenience static accessors for the most common core services. Plugins/themes may use these facades instead of injecting the underlying service.

| Facade | Underlying Service | Purpose |
|---|---|---|
| `App\Facades\Audit` | `App\Services\AuditService` | Log auditable events (auth, security, content, plugin lifecycle, etc.) |
| `App\Facades\SiteContext` | `App\Services\Site\SiteContext` | Resolve the current site (`SiteContext::currentSiteId()`, `currentSite()`, `isPluginActive($slug)`) |
| `App\Facades\SiteSettings` | `App\Models\SiteSetting` | Read site / network settings via the multisite-aware resolver (`SiteSettings::get('site_name')`) |
| `App\Facades\PluginPermission` | `App\Services\Plugin\PluginPermissionService` | Verify plugin declared permissions |
| `App\Facades\Webhook` | `App\Services\WebhookDispatcher` | Dispatch webhook events from plugin code |

Facades are wired via the standard Laravel facade pattern; see each facade file for the full `@method` PHPDoc list.

---

## 10. Eloquent Models

### 10.1 User/Member Models

- `App\Models\Member`

### 10.2 Content Models

- `App\Models\FrontPage`
- `App\Models\Media`

### 10.3 Plugin/Extension Models

- `App\Models\Plugin`
- `App\Models\Theme`
- `App\Models\SitePluginActivation` — per-site plugin activation row (which plugins are active for which site)
- `App\Models\SiteThemeActivation` — per-site theme activation row

### 10.4 System Models

- `App\Models\Site` — the site itself (multisite foundation)
- `App\Models\SiteSetting` — per-site settings (was `BaseSetting`); accessed via `SettingResolver` for scope-aware routing
- `App\Models\GlobalSetting` — network-wide settings (was part of `BaseSetting` / `SecuritySetting`)
- `App\Models\SecuritySetting` — alias for security keys (table now `global_settings`)
- `App\Models\FrontSetting` — per-site front-end settings

---

## 11. Global Helper Functions

| Function | Description |
|---|---|
| `shortcode_parse(string $content): string` | Parse and execute shortcodes in content |
| `render_x_cloak_style(): string` | Render the `[x-cloak]` style block to suppress Alpine.js flicker on initial render |

---

## 11.5 Read-only Core Config Keys

Plugins/themes may read the following core config keys via Laravel's `config()` helper. These keys are part of the Plugin API stability pledge: their names and value shapes will not change incompatibly within a major version. Treat them as **read-only** — write only via the responsible core service.

### 11.5.1 Admin Settings (`config/admin.php`)

| Key | Type | Description |
|---|---|---|
| `admin.admin_url` | `string` | Admin URL prefix (e.g. `admin`). Used to build admin route paths. |
| `admin.mediaPath` | `string` | Public media path (used by URL helpers). |
| `admin.files.mediaPath` | `string` | Filesystem media path (used by storage operations). |

### 11.5.2 Security (`config/security.php`)

| Key | Type | Description |
|---|---|---|
| `security.admin_url` | `string` | Admin URL prefix (security mirror, prefer `admin.admin_url`). |

### 11.5.3 Content Security Policy (`config/csp.php`)

| Key | Type | Description |
|---|---|---|
| `csp.base.mode` | `string` | Active CSP mode (`development`, `standard`, `strict`). |
| `csp.base.admin_mode` | `string` | Admin-area CSP mode override. |
| `csp.base.modes` | `array` | Map of mode → directive set. |
| `csp.base.nonce_length` | `int` | Nonce byte length. |
| `csp.base.report_uri` | `string\|null` | CSP violation report endpoint. |
| `csp.directives` | `array` | Resolved CSP directives. |
| `csp.domains` | `array` | Allowed external domains. |

### 11.5.4 Localization (`config/language.php`)

| Key | Type | Description |
|---|---|---|
| `language.default` | `string` | Default locale (e.g. `en`). |
| `language.languages` | `array` | Supported locale list. |
| `language.translations` | `array` | Locale → display-name map. |

### 11.5.5 Regions (`config/regions.php`)

| Key | Type | Description |
|---|---|---|
| `regions.countries` | `array` | Country code → name map. |
| `regions.prefectures` | `array` | Prefecture code → name map (Japan-specific). |

### 11.5.6 Themes (`config/themes.php`)

| Key | Type | Description |
|---|---|---|
| `themes.theme_directory` | `string` | Themes directory path (relative to base). |

### 11.5.7 Extension Sources (`config/extension-sources.php`)

| Key | Type | Description |
|---|---|---|
| `extension-sources.default_author_id` | `string\|null` | Default author ID for new extension entries. |
| `extension-sources.default_authority_key_id` | `string\|null` | Default Ed25519 authority key ID. |

> **Not Plugin API:** Laravel built-in config (`app.*`, `auth.*`, `cache.*`, `queue.*`, `session.*`, `mail.*`, `database.*`, `filesystems.*`, etc.) follows Laravel's own stability — refer to Laravel documentation. Other core configs not listed here may change without notice.

---

## 12. Plugin Architecture Requirements

### 12.1 Plugin Directory Structure

```
plugins/PluginName/
├── plugin.json              # Required: metadata, permissions, declares
├── composer.json
├── app/
│   └── Providers/
│       └── PluginNameServiceProvider.php
├── config/
│   └── admin/
│       ├── navigation.php
│       ├── roles.php
│       └── database-cleanup.php
├── routes/
├── resources/
│   ├── views/
│   ├── lang/{en,ja}/
│   └── src/
├── database/
│   └── migrations/
└── tests/
```

### 12.2 Self-Containment Principle

Plugins are architecturally self-contained — all plugin files reside within `plugins/PluginName/` and **do not modify any core source files**. Specifically:

- **Config files** (`config/admin/navigation.php`, `roles.php`, etc.) are provided within the plugin directory and merged into the application by `PluginLoaderTrait` at boot time. Core config files are never overwritten.
- **Route files** (`routes/`) are auto-loaded from the plugin directory. Core route files are not modified.
- **Migrations** (`database/migrations/`) manage only plugin-owned tables. Core database schema is not altered.
- **Views and translations** (`resources/views/`, `lang/`) are namespaced under the plugin's slug and do not replace core templates.

This self-containment means that installing or removing a plugin involves only adding or deleting its directory — no core files are touched. For license purposes, providing plugin-specific config, routes, views, and migrations through this architecture **does not constitute modification of core source files**.

### 12.3 ServiceProvider Requirements

Plugins must provide a ServiceProvider that uses `PluginLoaderTrait`:

```php
namespace Plugins\PluginName\App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Traits\PluginLoaderTrait;

class PluginNameServiceProvider extends ServiceProvider
{
    use PluginLoaderTrait;

    public function register(): void { /* ... */ }
    public function boot(): void { /* ... */ }
}
```

### 12.4 plugin.json Schema

```json
{
  "name": "Plugin Name",
  "slug": "plugin-slug",
  "version": "1.0.0",
  "description": {"en": "...", "ja": "..."},
  "provider": "Plugins\\PluginName\\App\\Providers\\PluginNameServiceProvider",
  "permissions": {
    "database": {"own_tables": [], "core_tables": []},
    "storage": {"own_directory": false, "public_uploads": false, "temp_files": false},
    "settings": {"read_core": false, "write_own": false},
    "members": {"read": false, "write": false, "create": false, "delete": false},
    "mail": {"send": false, "bulk_send": false},
    "content": {"read_other_plugins": false, "write_other_plugins": false},
    "system": {
      "register_shortcodes": false,
      "register_middleware": false,
      "register_commands": false,
      "register_blade_directives": false,
      "register_blocks": false,
      "modify_routes": false
    }
  },
  "declares": {
    "configs": {"roles": false, "database_cleanup": false, "navigation": false},
    "assets": {"common": ["js/app.js", "css/style.scss"], "admin": [], "front": []},
    "contracts": [],
    "migrations": false,
    "commands": false,
    "middleware": false
  }
}
```

`permissions.system.register_blocks` is **reserved** for plugins that register implementations of `App\Contracts\PluginIntegration\BlockProviderInterface` — Block components that may be placed in the GUI editor or in theme widget areas. The block registry, `x-block` renderer component, and admin UI are not yet implemented in v0.1.0; the contract and permission slot are reserved up-front so plugin authors can target a stable surface.

---

## 12.5 theme.json Schema (Widget Area Reservation)

Themes that support pluggable Block widgets in fixed regions of the layout (sidebar, footer, etc.) declare those regions in `theme.json` under the **`widget_areas`** key. The field is **reserved** at v0.1.0 — themes may declare it now so that future Dixlase releases can render plugin-provided Blocks into those areas without theme rework.

```json
{
  "name": "My Theme",
  "slug": "my-theme",
  "widget_areas": [
    {
      "name": "sidebar",
      "label": {"en": "Sidebar", "ja": "サイドバー"},
      "description": {"en": "Right column on standard pages.", "ja": "標準ページの右カラム。"}
    },
    {
      "name": "footer_columns",
      "label": {"en": "Footer Columns", "ja": "フッターカラム"}
    }
  ]
}
```

| Key | Type | Description |
|---|---|---|
| `widget_areas[].name` | `string` | Stable identifier referenced by the `x-widget-area` component's `name` prop (snake_case recommended). |
| `widget_areas[].label` | `object` | Localized display name (`{en, ja}`). |
| `widget_areas[].description` | `object` | Optional localized description shown in admin UI. |

A theme with no widget areas (e.g. a one-page landing-page theme) may omit the field entirely or declare an empty array.

---

## 13. Translation Structure

```
plugins/PluginName/lang/
├── en/
│   ├── admin/
│   │   ├── navigation.php
│   │   └── resource/page.php
│   └── components/component-name.php
└── ja/
    ├── admin/
    │   ├── navigation.php
    │   └── resource/page.php
    └── components/component-name.php
```

---

## 14. `@api` PHPDoc Tag Convention

All classes that form the Plugin API are annotated with the `@api` PHPDoc tag:

```php
/**
 * @api プラグイン/テーマから使用可能な安定APIです
 */
```

### Guidelines for Plugin Developers
- **Safe to depend on:** Classes with `@api` tag are stable and covered by the license exception
- **Avoid depending on:** Classes without `@api` tag are internal implementation details
- **PHPStan enforcement:** DixlaseDevKit provides a PHPStan rule that warns about non-`@api` imports
- **Quick check:** Run `dls:plugin:check-api {plugin-name}` to scan your plugin for non-API imports

### Guidelines for Core Contributors
- Add `@api` to any new class intended for plugin/theme use
- Removing `@api` from an existing class is a breaking change requiring a major version bump
- Run `dls:plugin:check-api` (DixlaseDevKit) to verify API surface consistency

---

## 15. What Is NOT Part of the Plugin API

The following are **internal implementation details** and are NOT covered by the exception:

- Internal controller logic beyond base classes listed above
- Service class implementations (use contracts/interfaces instead)
- Undocumented model scope methods
- Request validation classes (Form Requests)
- Internal event handling infrastructure
- Internal job queue infrastructure (except `ShouldQueue` interface)
- Private/protected methods of any listed class
- Database schema internals not exposed through models

Plugins relying on internal components become derivative works subject to full AGPL-3.0 terms.
