# Dixlase CMS Plugin API Boundary

**Version:** dev
**Last Updated:** 2026-02-23
**Purpose:** Define the public Plugin API boundary for the AGPL license exception clause (see LICENSE)

This document defines all components that form the "Plugin API" -- the public interfaces,
services, and configurations that plugins and themes are permitted to use without triggering
AGPL copyleft obligations under the Dixlase Plugin and Theme Exception.

---

## 1. Contracts / Interfaces

### 1.1 Service Contracts

| Contract | Description |
|---|---|
| `App\Contracts\CspPolicyProvider` | CSP Policy Provider Interface |
| `App\Contracts\FileIntegrity\FileIntegrityServiceInterface` | ファイル整合性チェックサービスの契約 |
| `App\Contracts\Logging\LogServiceInterface` | ログ出力サービスの契約 |
| `App\Contracts\Mail\MailServiceInterface` | メール送信サービスの契約 |
| `App\Contracts\TranslationResolver` | Translation Resolver Contract |
| `App\Contracts\TwoFaInterface` | 二段階認証機能を持つユーザーのインターフェース |

### 1.2 Plugin Integration Contracts

| Contract | Description |
|---|---|
| `App\Contracts\PluginIntegration\LinkableInterface` | リンク可能なコンテンツの最小契約 |
| `App\Contracts\PluginIntegration\LinkableProviderInterface` | リンク可能なコンテンツを提供するプラグインの契約 |
| `App\Contracts\PluginIntegration\PrivacyPolicyProviderInterface` | プライバシーポリシープロバイダーの契約 |

### 1.3 Plugin Capability Contracts

| Contract | Description |
|---|---|
| `App\Contracts\Plugin\ApiResourceProviderInterface` | APIリソースプロバイダーインターフェース |
| `App\Contracts\Plugin\ContentProviderCapableInterface` | コンテンツ提供機能を宣言するインターフェース |
| `App\Contracts\Plugin\MailCapableInterface` | メール送信機能を宣言するインターフェース |
| `App\Contracts\Plugin\PluginCapabilityInterface` | プラグイン機能宣言の基底インターフェース |
| `App\Contracts\Plugin\SignatureVerifierInterface` | 署名検証コントラクト |

### 1.4 Repository Contracts

| Contract | Description |
|---|---|
| `App\Contracts\Repositories\ApiSettingRepositoryInterface` | API設定リポジトリインターフェース |
| `App\Contracts\Repositories\BaseSettingRepositoryInterface` | 基本設定リポジトリインターフェース |
| `App\Contracts\Repositories\FrontSettingRepositoryInterface` | フロント設定リポジトリインターフェース |
| `App\Contracts\Repositories\MediaRepositoryInterface` | メディアリポジトリインターフェース |
| `App\Contracts\Repositories\MediaSettingRepositoryInterface` | メディア設定リポジトリインターフェース |
| `App\Contracts\Repositories\SecuritySettingRepositoryInterface` | セキュリティ設定リポジトリインターフェース |
| `App\Contracts\Repositories\SettingRepositoryInterface` | 設定リポジトリベースインターフェース |

---

## 2. Traits for Plugin Use

| Trait | Description |
|---|---|
| `App\Traits\AuditableTrait` | モデルの監査ログ自動記録トレイト |
| `App\Traits\ConfigLoaderTrait` | 設定ファイルローディングユーティリティ |
| `App\Traits\CustomFilesLoaderTrait` | カスタムファイルオーバーライドローディング |
| `App\Traits\HasPermissions` | HasPermissions Trait |
| `App\Traits\PluginLoaderTrait` | プラグインリソースローディング機構 |
| `App\Traits\RegistersCspPolicy` | CSPポリシー登録トレイト |
| `App\Traits\ThemeLoaderTrait` | テーマリソースローディング機構 |
| `App\Traits\TranslatableTrait` | Translatable Trait |

---

## 3. Base Controllers for Extension

| Controller | Description |
|---|---|
| `App\Http\Controllers\Admin\AdminController` | 管理画面基底コントローラー |
| `App\Http\Controllers\Admin\AdminLoggedInController` | 認証必須の管理画面コントローラー |
| `App\Http\Controllers\Install\BaseInstallController` | Base installation controller |

---

## 4. Data Transfer Objects (DTOs)

### 4.1 API DTOs

- `App\DTO\Api\ApiResourceCollection`
- `App\DTO\Api\ApiResourceDTO`

### 4.2 File Integrity DTOs

- `App\DTO\FileIntegrity\BaselineDTO`
- `App\DTO\FileIntegrity\FileChangeDTO`
- `App\DTO\FileIntegrity\ScanResultDTO`
- `App\DTO\FileIntegrity\ScanTargetDTO`

### 4.3 Logging DTOs

- `App\DTO\Logging\LogContextDTO`
- `App\DTO\Logging\LogEntryDTO`

### 4.4 Mail DTOs

- `App\DTO\Mail\MailAttachmentDTO`
- `App\DTO\Mail\MailConfigDTO`
- `App\DTO\Mail\MailMessageDTO`
- `App\DTO\Mail\MailResultDTO`

### 4.5 Plugin Integration DTOs

- `App\DTO\PluginIntegration\LinkableDTO`
- `App\DTO\PluginIntegration\MenuItemDTO`
- `App\DTO\PluginIntegration\PaginatedResultDTO`
- `App\DTO\PluginIntegration\SearchQueryDTO`

### 4.6 Plugin DTOs

- `App\DTO\Plugin\CapabilityResolutionResult`
- `App\DTO\Plugin\DeclaresVerificationResult`
- `App\DTO\Plugin\HealthIssue`
- `App\DTO\Plugin\HealthScoreResult`
- `App\DTO\Plugin\SignatureVerificationResult`

---

## 5. Enums

### 5.1 System Enums

- `App\Enums\AdminMode`
- `App\Enums\AppEnvironment`
- `App\Enums\AppearanceMode`
- `App\Enums\AuthenticationMode`
- `App\Enums\CaptchaProvider`
- `App\Enums\ContentEditorType`
- `App\Enums\ContentStatus`
- `App\Enums\ContentStorageType`
- `App\Enums\Locale`
- `App\Enums\LogLevel`
- `App\Enums\MenuVisibility`
- `App\Enums\Permission`

### 5.2 Security Enums

- `App\Enums\CspBlocklistAction`
- `App\Enums\CspMode`
- `App\Enums\ExtensionSecurityLevel`
- `App\Enums\ExtensionSecurityPreset`
- `App\Enums\OperationRiskLevel`
- `App\Enums\PasskeyMode`
- `App\Enums\SecurityAction`
- `App\Enums\TwoFaMethod`

### 5.3 User/Role Enums

- `App\Enums\Gender`
- `App\Enums\MemberRole`
- `App\Enums\MemberStatus`

### 5.4 Plugin/Theme Enums

- `App\Enums\PluginHealthStatus`
- `App\Enums\PluginTrustLevel`
- `App\Enums\PluginVerificationStatus`

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
`x-admin.account-status`, `x-admin.settings.security-notifications`

### 6.4 Front-end Components

`x-front.button`, `x-front.card`, `x-front.breadcrumb`, `x-front.navigation`

### 6.5 Specialized Components

`x-media.picker`, `x-media.selector`, `x-extension.card`, `x-captcha`,
`x-security.captcha-settings`, `x-security.captcha-widget`,
`x-security.csp-safe-mode-banner`, `x-security.login-attempt-limit-settings`,
`x-security.login-notification-selector`, `x-security.passkey-device-settings`,
`x-security.password-settings`, `x-security.session-settings`,
`x-security.two-fa-detailed-settings`, `x-security.two-fa-general-settings`,
`x-two-fa.management`, `x-two-fa.mode-selector`, `x-two-fa.individual-settings`,
`x-auth.login-form`, `x-auth.account-verification`, `x-auth.forgot-password`,
`x-auth.reset-password`, `x-auth.verification-notice`, `x-auth.login-field`,
`x-mail-server.form`, `x-mail-server.test`, `x-mail-server.verification-error`,
`x-mail-server.verification-success`, `x-application-logo`, `x-auth-session-status`

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
- `App\Services\Plugin\PluginHealthScorer` — `@api`, direct DI
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
- `App\Services\EmailAuthenticationService`

### 9.5 API & CSP Services

- `App\Services\ApiRateLimitService`
- `App\Services\Csp\CspPolicyRegistry`
- `App\Services\Csp\CspBuilder`
- `App\Services\Csp\CspNonceGenerator`

---

## 10. Eloquent Models

### 10.1 User/Member Models

- `App\Models\Member`
- `App\Models\MemberRolePermission`
- `App\Models\MemberLoginAttempt`
- `App\Models\MemberTwoFaToken`
- `App\Models\MemberTwoFaDevice`
- `App\Models\MemberTwoFaRecoveryCode`
- `App\Models\MembersTrustedDevice`

### 10.2 Content Models

- `App\Models\FrontPage`
- `App\Models\Media`
- `App\Models\Front`

### 10.3 Plugin/Extension Models

- `App\Models\Plugin`
- `App\Models\PluginAudit`
- `App\Models\PluginMemberRolePermission`
- `App\Models\Theme`
- `App\Models\ThemeAudit`

### 10.4 System Models

- `App\Models\BaseSetting`
- `App\Models\SecuritySetting`
- `App\Models\FrontSetting`
- `App\Models\MediaSetting`
- `App\Models\ApiSetting`
- `App\Models\AuditLog`
- `App\Models\ApiRequestLog`
- `App\Models\ApiKey`
- `App\Models\LockdownStatus`
- `App\Models\LockdownHistory`

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

### 12.2 ServiceProvider Requirements

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

### 12.3 plugin.json Schema

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
- Request validation classes
- Internal event handling infrastructure
- Internal job queue infrastructure (except `ShouldQueue` interface)
- Private/protected methods of any listed class
- Database schema internals not exposed through models

Plugins relying on internal components become derivative works subject to full AGPL-3.0 terms.
