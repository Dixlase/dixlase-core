# Dixlase CMS Plugin API Boundary

**Version:** dev
**Last Updated:** 2026-04-05
**Purpose:** Define the public Plugin API boundary for the AGPL license exception clause (see LICENSE)

This document defines all components that form the "Plugin API" -- the public interfaces,
services, and configurations that plugins and themes are permitted to use without triggering
AGPL copyleft obligations under the Dixlase Plugin and Theme Exception.

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
| `App\Contracts\Admin\AdminNavigationManagerInterface` | 管理画面ナビゲーション管理インターフェース |
| `App\Contracts\CspPolicyProvider` | CSP Policy Provider Interface |
| `App\Contracts\Extension\ExtensionSourceInterface` | Extension Source Provider Interface |
| `App\Contracts\FileIntegrity\FileIntegrityServiceInterface` | ファイル整合性チェックサービスの契約 |
| `App\Contracts\LegalPage\LegalPageServiceInterface` | 法務ページレジストリサービスの契約 |
| `App\Contracts\Logging\LogServiceInterface` | ログ出力サービスの契約 |
| `App\Contracts\Mail\MailServiceInterface` | メール送信サービスの契約 |
| `App\Contracts\RouteSlugProvider` | Route Slug Provider Interface |
| `App\Contracts\Theme\ThemePermissionServiceInterface` | テーマ権限管理サービスの契約 |
| `App\Contracts\TranslationResolver` | Translation Resolver Contract |
| `App\Contracts\TwoFaInterface` | 二段階認証機能を持つユーザーのインターフェース |
| `App\Contracts\TwoFa\TwoFaPasskeyServiceInterface` | Passkey（WebAuthn）認証サービスの契約 |

### 1.2 Plugin Integration Contracts

| Contract | Description |
|---|---|
| `App\Contracts\PluginIntegration\DashboardNotificationProviderInterface` | ダッシュボード通知を提供するプラグインの契約 |
| `App\Contracts\PluginIntegration\DashboardWidgetProviderInterface` | ダッシュボードウィジェットを提供するプラグインの契約 |
| `App\Contracts\PluginIntegration\LinkableInterface` | リンク可能なコンテンツの最小契約 |
| `App\Contracts\PluginIntegration\LinkableProviderInterface` | リンク可能なコンテンツを提供するプラグインの契約 |
| `App\Contracts\PluginIntegration\MenuProviderInterface` | Contract for plugins that provide navigation menus |
| `App\Contracts\PluginIntegration\PreviewProviderInterface` | Contract for plugins that provide preview data |
| `App\Contracts\PluginIntegration\PrivacyPolicyProviderInterface` | プライバシーポリシープロバイダーの契約 |

### 1.3 Plugin Capability Contracts

| Contract | Description |
|---|---|
| `App\Contracts\Plugin\ApiResourceProviderInterface` | APIリソースプロバイダーインターフェース |
| `App\Contracts\Plugin\ContentProviderCapableInterface` | コンテンツ提供機能を宣言するインターフェース |
| `App\Contracts\Plugin\EditorCapableInterface` | エディター提供機能を宣言するインターフェース |
| `App\Contracts\Plugin\MailCapableInterface` | メール送信機能を宣言するインターフェース |
| `App\Contracts\Plugin\PluginCapabilityInterface` | プラグイン機能宣言の基底インターフェース |
| `App\Contracts\Plugin\PluginPermissionServiceInterface` | プラグイン権限管理サービスの契約 |
| `App\Contracts\Plugin\SignatureVerifierInterface` | 署名検証コントラクト |

### 1.4 Repository Contracts

| Contract | Description |
|---|---|
| `App\Contracts\Repositories\ApiSettingRepositoryInterface` | API設定リポジトリインターフェース |
| `App\Contracts\Repositories\BaseSettingRepositoryInterface` | 基本設定リポジトリインターフェース |
| `App\Contracts\Repositories\FrontSettingRepositoryInterface` | フロント設定リポジトリインターフェース |
| `App\Contracts\Repositories\MediaRepositoryInterface` | メディアリポジトリインターフェース |
| `App\Contracts\Repositories\MediaSettingRepositoryInterface` | メディア設定リポジトリインターフェース |
| `App\Contracts\Repositories\PluginRepositoryInterface` | プラグインリポジトリインターフェース |
| `App\Contracts\Repositories\SecuritySettingRepositoryInterface` | セキュリティ設定リポジトリインターフェース |
| `App\Contracts\Repositories\SettingRepositoryInterface` | 設定リポジトリベースインターフェース |
| `App\Contracts\Repositories\ThemeRepositoryInterface` | テーマリポジトリインターフェース |

---

## 2. Traits for Plugin Use

| Trait | Description |
|---|---|
| `App\Traits\AdminInterfaceTrait` | 管理画面の共通インターフェース初期化トレイト |
| `App\Traits\AdminLoggedInTrait` | 管理画面ログイン後の共通初期化トレイト |
| `App\Traits\AuditableTrait` | モデルの監査ログ自動記録トレイト |
| `App\Traits\ConfigLoaderTrait` | 設定ファイルローディングユーティリティ |
| `App\Traits\CustomFilesLoaderTrait` | カスタムファイルオーバーライドローディング |
| `App\Traits\EmailVerificationTrait` | メール認証の共通ロジックを提供するTrait |
| `App\Traits\HasPermissions` | HasPermissions Trait |
| `App\Traits\LoginIdentifierCheckTrait` | ログイン識別子確認の共通トレイト |
| `App\Traits\LoginNotificationTrait` | ログイン通知の共通トレイト |
| `App\Traits\MailTestTrait` |  |
| `App\Traits\ManagesAccountTrait` | アカウント管理の共通処理 |
| `App\Traits\ManagesContentFiles` | コンテンツファイル管理トレイト |
| `App\Traits\ManagesTwoFaTrait` | 二段階認証管理の共通処理 |
| `App\Traits\PasskeyLoginTrait` | パスキーログインの共通トレイト |
| `App\Traits\PasswordResetTrait` | パスワードリセットの共通ロジックを提供するTrait |
| `App\Traits\PluginLoaderTrait` | プラグインリソースローディング機構 |
| `App\Traits\RegistersCspPolicy` | CSPポリシー登録トレイト |
| `App\Traits\ThemeLoaderTrait` | テーマリソースローディング機構 |
| `App\Traits\TranslatableTrait` | Translatable Trait |
| `App\Traits\TwoFa\TwoFaAuthenticationTrait` | 二段階認証のフロー制御機能を提供するトレイト |
| `App\Traits\VerifiesCaptcha` |  |

---

## 3. Base Controllers for Extension

| Controller | Description |
|---|---|
| `App\Http\Controllers\Admin\AdminController` | 管理画面基底コントローラー |
| `App\Http\Controllers\Admin\AdminLoggedInController` | 認証必須の管理画面コントローラー |

---

## 4. Data Transfer Objects (DTOs)

### 4.1 Action DTOs

- `App\DTO\Action\ActionResult`

### 4.2 API DTOs

- `App\DTO\Api\ApiResourceCollection`
- `App\DTO\Api\ApiResourceDTO`

### 4.3 Editor DTOs

- `App\DTO\Editor\EditorInfo`

### 4.4 Extension DTOs

- `App\DTO\Extension\ReleaseInfo`

### 4.5 File Integrity DTOs

- `App\DTO\FileIntegrity\BaselineDTO`
- `App\DTO\FileIntegrity\FileChangeDTO`
- `App\DTO\FileIntegrity\ScanResultDTO`
- `App\DTO\FileIntegrity\ScanTargetDTO`

### 4.6 Logging DTOs

- `App\DTO\Logging\LogContextDTO`
- `App\DTO\Logging\LogEntryDTO`

### 4.7 Mail DTOs

- `App\DTO\Mail\MailAttachmentDTO`
- `App\DTO\Mail\MailConfigDTO`
- `App\DTO\Mail\MailMessageDTO`
- `App\DTO\Mail\MailResultDTO`

### 4.8 Plugin Integration DTOs

- `App\DTO\PluginIntegration\DashboardNotificationDTO`
- `App\DTO\PluginIntegration\DashboardWidgetDTO`
- `App\DTO\PluginIntegration\LinkableDTO`
- `App\DTO\PluginIntegration\MenuDTO`
- `App\DTO\PluginIntegration\MenuItemDTO`
- `App\DTO\PluginIntegration\PaginatedResultDTO`
- `App\DTO\PluginIntegration\PreviewDTO`
- `App\DTO\PluginIntegration\PreviewFieldDTO`
- `App\DTO\PluginIntegration\SearchQueryDTO`

### 4.9 Plugin DTOs

- `App\DTO\Plugin\CapabilityResolutionResult`
- `App\DTO\Plugin\DeclaresVerificationResult`
- `App\DTO\Plugin\EnabledPluginRecord`
- `App\DTO\Plugin\SignatureVerificationResult`

### 4.10 RouteSlug DTOs

- `App\DTO\RouteSlug\RegisteredSlug`

---

## 5. Enums

### 5.1 System Enums

- `App\Enums\ActorType`
- `App\Enums\AppearanceMode`
- `App\Enums\AuthenticationMode`
- `App\Enums\ContentEditorType`
- `App\Enums\ContentStatus`
- `App\Enums\ContentStorageType`
- `App\Enums\Locale`
- `App\Enums\LogLevel`
- `App\Enums\LoginIdentifierMode`
- `App\Enums\Permission`

### 5.2 User/Role Enums

- `App\Enums\Gender`
- `App\Enums\MemberRole`
- `App\Enums\MemberStatus`

### 5.3 Security Enums

- `App\Enums\OperationRiskLevel`
- `App\Enums\PasskeyMode`
- `App\Enums\TwoFaMethod`

---

## 6. Blade Components

### 6.1 Form Components

`x-form-text`, `x-form-email`, `x-form-password`, `x-form-textarea`, `x-form-select`,
`x-form-checkbox`, `x-form-checkbox-group`, `x-form-toggle`, `x-form-toggle-group`,
`x-form-radio-group`, `x-form-radio-card-group`, `x-form-color`, `x-form-range`,
`x-form-label`, `x-form-error`, `x-form-help-text`, `x-form-button`, `x-form-hidden`,
`x-form-input-with-label`, `x-form-password-tools`, `x-form-required-badge`,
`x-form-content-editor`

### 6.2 UI Components

`x-ui-modal`, `x-ui-modal-vanilla`, `x-ui-notification`, `x-ui-livewire-notification`,
`x-ui-livewire-modal`, `x-ui-message`, `x-ui-flash-message`, `x-ui-status-badge`,
`x-ui-pagination`, `x-ui-pagination-controls`, `x-ui-tooltip`,
`x-ui-maintenance-banner`, `x-ui-admin-maintenance-banner`,
`x-ui-appearance-mode-selector`, `x-ui-language-switcher`, `x-ui-admin-bar`

### 6.3 Admin Components

`x-admin.save-button`, `x-admin.delete-button`, `x-admin.danger-zone`,
`x-admin.account-status`, `x-admin.right-sidebar`,
`x-admin.mode-guide-banner`, `x-admin.mode-partial-notice`, `x-admin.mode-readonly-banner`,
`x-admin.theme-preview-container`, `x-admin.theme-preview-sidebar`,
`x-admin.theme-preview-sidebar-section`

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

---

## 7. Middleware Groups for Plugins

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
- `App\Services\DatabaseCleanupService`
- `App\Services\SystemNotificationService`
- `App\Services\Auth\AuthContextRegistryService`
- `App\Services\Editor\EditorManager`
- `App\Services\LegalPageService`
- `App\Services\MailServerValidatorService`
- `App\Services\RouteSlugRegistry`

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
| `App\Helpers\ConfigHelper` | Security and session configuration retrieval |
| `App\Helpers\ComposerLocalHelper` | composer-local.json management |
| `App\Helpers\GitExcludeHelper` | .git/info/exclude file management |
| `App\Helpers\GitIgnoreHelper` | .gitignore file management |
| `App\Helpers\LocaleHelper` | Locale detection and language settings |
| `App\Helpers\LoginHelper` | Login authentication, 2FA check, and session management |
| `App\Helpers\LoginLockoutHelper` | Login lockout detection and recording |
| `App\Helpers\PluginHelper` | Plugin enablement status, paths, and route loading |
| `App\Helpers\TwoFaHelper` | Two-factor authentication code generation, sending, and settings |

### 9.7 Actors

- `App\Actors\MemberActor` — Member actor implementation for the Action framework

### 9.8 Events

- `App\Events\AuditLogCreated` — Audit log creation event (for SIEM integration and plugin hooks)
- `App\Events\SecurityAlertEvent` — Security alert event

### 9.9 Validation Rules

- `App\Rules\UniqueContentSlug` — Content slug uniqueness validation
- `App\Rules\UniqueRouteSlug` — Route slug uniqueness validation

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

### 10.4 System Models

- `App\Models\BaseSetting`
- `App\Models\SecuritySetting`
- `App\Models\FrontSetting`

---

## 11. Global Helper Functions

| Function | Description |
|---|---|
| `shortcode_parse(string $content): string` | Parse and execute shortcodes in content |

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
