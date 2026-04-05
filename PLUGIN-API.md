# Dixlase CMS プラグイン API 境界

**バージョン:** dev
**最終更新日:** 2026-04-05
**目的:** AGPL ライセンス例外条項のための公開プラグイン API 境界の定義（LICENSE を参照）

このドキュメントは「プラグイン API」を構成するすべてのコンポーネントを定義します。
プラグインおよびテーマが Dixlase プラグイン・テーマ例外に基づき、AGPL のコピーレフト義務を
発生させることなく使用できる公開インターフェース、サービス、設定を示します。

---

## プラグイン・テーマのライセンスについて

Dixlase CMS と**このドキュメントに記載されたインターフェースのみを通じて**連携するプラグインおよびテーマは、Dixlase CMS の派生物とは**みなされません**。プロプライエタリライセンスを含む、任意のライセンスで配布することができます。

この権利は **Dixlase プラグイン・テーマ例外条項**（`LICENSE` ファイルの「GNU AGPL バージョン3 第7条に基づく追加許可」セクションを参照）によって付与されます。例外は以下の全条件を満たす場合に適用されます:

1. プラグイン/テーマが、このドキュメントに定義された Plugin API のみを通じて Dixlase CMS と通信すること。
2. プラグイン/テーマが、コアのソースファイルを変更・置換・モンキーパッチしないこと。
3. プラグイン/テーマが、コアの内部実装を迂回・複製しないこと。
4. プラグイン/テーマが、標準の読み込み機構（`PluginLoaderTrait` / `ThemeLoaderTrait`）を通じて読み込まれ、`plugins/` または `themes/` ディレクトリに配置されていること。

いずれかの条件を満たさない場合、プラグイン/テーマは AGPL-3.0 の全条項の対象となります。

---

## 1. コントラクト / インターフェース

### 1.1 Service Contracts

| コントラクト | 説明 |
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

| コントラクト | 説明 |
|---|---|
| `App\Contracts\PluginIntegration\DashboardNotificationProviderInterface` | ダッシュボード通知を提供するプラグインの契約 |
| `App\Contracts\PluginIntegration\DashboardWidgetProviderInterface` | ダッシュボードウィジェットを提供するプラグインの契約 |
| `App\Contracts\PluginIntegration\LinkableInterface` | リンク可能なコンテンツの最小契約 |
| `App\Contracts\PluginIntegration\LinkableProviderInterface` | リンク可能なコンテンツを提供するプラグインの契約 |
| `App\Contracts\PluginIntegration\MenuProviderInterface` | Contract for plugins that provide navigation menus |
| `App\Contracts\PluginIntegration\PreviewProviderInterface` | Contract for plugins that provide preview data |
| `App\Contracts\PluginIntegration\PrivacyPolicyProviderInterface` | プライバシーポリシープロバイダーの契約 |

### 1.3 Plugin Capability Contracts

| コントラクト | 説明 |
|---|---|
| `App\Contracts\Plugin\ApiResourceProviderInterface` | APIリソースプロバイダーインターフェース |
| `App\Contracts\Plugin\ContentProviderCapableInterface` | コンテンツ提供機能を宣言するインターフェース |
| `App\Contracts\Plugin\EditorCapableInterface` | エディター提供機能を宣言するインターフェース |
| `App\Contracts\Plugin\MailCapableInterface` | メール送信機能を宣言するインターフェース |
| `App\Contracts\Plugin\PluginCapabilityInterface` | プラグイン機能宣言の基底インターフェース |
| `App\Contracts\Plugin\PluginPermissionServiceInterface` | プラグイン権限管理サービスの契約 |
| `App\Contracts\Plugin\SignatureVerifierInterface` | 署名検証コントラクト |

### 1.4 Repository Contracts

| コントラクト | 説明 |
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

## 2. プラグイン用トレイト

| トレイト | 説明 |
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

## 3. 拡張用ベースコントローラー

| コントローラー | 説明 |
|---|---|
| `App\Http\Controllers\Admin\AdminController` | 管理画面基底コントローラー |
| `App\Http\Controllers\Admin\AdminLoggedinController` | 認証必須の管理画面コントローラー |

---

## 4. データ転送オブジェクト（DTO）

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

## 5. Enum

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

## 6. Blade コンポーネント

### 6.1 フォームコンポーネント

`x-form-text`, `x-form-email`, `x-form-password`, `x-form-textarea`, `x-form-select`,
`x-form-checkbox`, `x-form-checkbox-group`, `x-form-toggle`, `x-form-toggle-group`,
`x-form-radio-group`, `x-form-radio-card-group`, `x-form-color`, `x-form-range`,
`x-form-label`, `x-form-error`, `x-form-help-text`, `x-form-button`, `x-form-hidden`,
`x-form-input-with-label`, `x-form-password-tools`, `x-form-required-badge`,
`x-form-content-editor`

### 6.2 UI コンポーネント

`x-ui-modal`, `x-ui-modal-vanilla`, `x-ui-notification`, `x-ui-livewire-notification`,
`x-ui-livewire-modal`, `x-ui-message`, `x-ui-flash-message`, `x-ui-status-badge`,
`x-ui-pagination`, `x-ui-pagination-controls`, `x-ui-tooltip`,
`x-ui-maintenance-banner`, `x-ui-admin-maintenance-banner`,
`x-ui-appearance-mode-selector`, `x-ui-language-switcher`, `x-ui-admin-bar`

### 6.3 管理画面コンポーネント

`x-admin.save-button`, `x-admin.delete-button`, `x-admin.danger-zone`,
`x-admin.account-status`, `x-admin.settings.security-notifications`

### 6.4 フロントエンドコンポーネント

`x-front.button`, `x-front.card`, `x-front.breadcrumb`, `x-front.navigation`

### 6.5 専用コンポーネント

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
`x-mail-server.verification-success`, `x-application-logo`, `x-auth-session-status`,
`x-content-editor.new-tab-preview`

---

## 7. プラグイン用ミドルウェアグループ

### 7.1 API ミドルウェア

| グループ | 説明 |
|---|---|
| `plugin.api` | 認証付き API ルート（ApiKey 認証 + レート制限 + ログ） |
| `plugin.api.public` | 公開 API ルート（レート制限 + ログ、認証なし） |

### 7.2 Web ミドルウェア

| グループ | 説明 |
|---|---|
| `plugin` | 基本プラグインルート（セッション、クッキー、ビュー共有、バインディング） |
| `plugin.web` | プラグインフロントエンドルート（IP フィルタリング付き） |
| `plugin.admin` | プラグイン管理画面ルート（認証必須 + IP フィルタリング） |

---

## 8. 設定ファイル構造

### 8.1 管理画面ナビゲーション

**ファイル:** `plugins/{Name}/config/admin/navigation.php`

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

### 8.2 ロール権限

**ファイル:** `plugins/{Name}/config/admin/roles.php`

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

### 8.3 データベースクリーンアップ

**ファイル:** `plugins/{Name}/config/admin/database-cleanup.php`

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

## 9. 注入可能なサービス

### 9.1 プラグイン/テーマサービス

以下の `@api` マーク付きサービスは DI で直接注入できます。
その他のサービスはインターフェース経由でアクセスします（セクション 1 を参照）。

- `App\Services\Plugin\DeclaresVerifier` — `@api`、直接 DI
- `App\Services\Plugin\PluginPermissionService` — `@api`、直接 DI
- `App\Services\Plugin\PluginServiceResolver` — `@api`、直接 DI
- `App\Services\Plugin\CoreSignatureVerifier` — 代わりに `SignatureVerifierInterface` を使用
- `App\Services\Theme\ThemePermissionService`
- `App\Services\PluginMigrator`
- `App\Services\PluginMigrationRepository`

### 9.2 コアサービス

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

### 9.3 メール & ログ

- `App\Contracts\Mail\MailServiceInterface`（コンテナバインディング経由）
- `App\Contracts\Logging\LogServiceInterface`（コンテナバインディング経由）

### 9.4 セキュリティサービス

- `App\Services\CaptchaTestService`
- `App\Services\CaptchaFailoverService`
- `App\Services\CaptchaBypassService`
- `App\Services\FileIntegrityService`
- `App\Services\AdminLoginLockoutService`
- `App\Services\TwoFa\TwoFaService`
- `App\Services\TwoFa\TwoFaPasskeyService`
- `App\Services\TwoFa\TwoFaRecoveryCodeService`
- `App\Services\EmailAuthenticationService`

### 9.5 API & CSP サービス

- `App\Services\ApiRateLimitService`
- `App\Services\Csp\CspPolicyRegistry`
- `App\Services\Csp\CspBuilder`
- `App\Services\Csp\CspNonceGenerator`

### 9.6 ヘルパークラス

| ヘルパー | 説明 |
|---|---|
| `App\Helpers\CaptchaHelper` | CAPTCHA 有効判定・ウィジェット描画・ドライバー設定ヘルパー |
| `App\Helpers\ConfigHelper` | セキュリティ設定・セッション設定の取得ヘルパー |
| `App\Helpers\ComposerLocalHelper` | composer-local.json の管理ヘルパー |
| `App\Helpers\GitExcludeHelper` | .git/info/exclude ファイル管理ヘルパー |
| `App\Helpers\GitIgnoreHelper` | .gitignore ファイル管理ヘルパー |
| `App\Helpers\LocaleHelper` | 言語設定・ロケール判定ヘルパー |
| `App\Helpers\LoginHelper` | ログイン認証・2FA チェック・セッション管理ヘルパー |
| `App\Helpers\LoginLockoutHelper` | ログインロックアウト判定・記録ヘルパー |
| `App\Helpers\PluginHelper` | プラグイン有効状態・パス・ルート読み込みヘルパー |
| `App\Helpers\TwoFaHelper` | 二段階認証コード生成・送信・設定取得ヘルパー |

### 9.7 アクター

- `App\Actors\MemberActor` — Action フレームワーク用のメンバーアクター実装

### 9.8 イベント

- `App\Events\AuditLogCreated` — 監査ログ作成イベント（SIEM 連携・プラグインフック用）
- `App\Events\SecurityAlertEvent` — セキュリティアラートイベント

### 9.9 バリデーションルール

- `App\Rules\UniqueContentSlug` — コンテンツスラッグの一意性バリデーション
- `App\Rules\UniqueRouteSlug` — ルートスラッグの一意性バリデーション

---

## 10. Eloquent モデル

### 10.1 ユーザー/メンバーモデル

- `App\Models\Member`

### 10.2 コンテンツモデル

- `App\Models\FrontPage`
- `App\Models\Media`

### 10.3 プラグイン/拡張モデル

- `App\Models\Plugin`
- `App\Models\Theme`

### 10.4 システムモデル

- `App\Models\BaseSetting`
- `App\Models\SecuritySetting`
- `App\Models\FrontSetting`

---

## 11. グローバルヘルパー関数

| 関数 | 説明 |
|---|---|
| `shortcode_parse(string $content): string` | コンテンツ内のショートコードを解析・実行する |

---

## 12. プラグインアーキテクチャ要件

### 12.1 プラグインディレクトリ構造

```
plugins/PluginName/
├── plugin.json              # 必須: メタデータ、権限、宣言
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

### 12.2 自己完結原則

プラグインはアーキテクチャ上自己完結しています — すべてのプラグインファイルは `plugins/PluginName/` 内に配置され、**コアのソースファイルを一切改変しません**。具体的には:

- **設定ファイル**（`config/admin/navigation.php`、`roles.php` 等）はプラグインディレクトリ内に提供され、`PluginLoaderTrait` によって起動時にアプリケーションへマージされます。コアの設定ファイルは上書きされません。
- **ルートファイル**（`routes/`）はプラグインディレクトリから自動読み込みされます。コアのルートファイルは改変されません。
- **マイグレーション**（`database/migrations/`）はプラグイン固有のテーブルのみを管理します。コアのデータベーススキーマは変更されません。
- **ビューと翻訳**（`resources/views/`、`lang/`）はプラグインの slug で名前空間化され、コアのテンプレートを置き換えません。

この自己完結性により、プラグインのインストール・アンインストールはディレクトリの追加・削除のみで完結し、コアファイルには一切触れません。ライセンス上、このアーキテクチャを通じてプラグイン固有の設定・ルート・ビュー・マイグレーションを提供することは、**コアソースファイルの改変には該当しません**。

### 12.3 ServiceProvider 要件

プラグインは `PluginLoaderTrait` を使用する ServiceProvider を提供する必要があります:

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

### 12.4 plugin.json スキーマ

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

## 13. 翻訳ファイル構造

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

## 14. `@api` PHPDoc タグ規約

プラグイン API を構成するすべてのクラスには `@api` PHPDoc タグが付与されています:

```php
/**
 * @api プラグイン/テーマから使用可能な安定APIです
 */
```

### プラグイン開発者向けガイドライン
- **依存して安全:** `@api` タグ付きクラスは安定しており、ライセンス例外の対象です
- **依存を避ける:** `@api` タグなしのクラスは内部実装の詳細です
- **PHPStan 強制:** DixlaseDevKit は非 `@api` インポートに対する PHPStan ルールを提供します
- **クイックチェック:** `dls:plugin:check-api {plugin-name}` でプラグインの非 API インポートをスキャンできます

### コアコントリビューター向けガイドライン
- プラグイン/テーマでの使用を意図した新しいクラスには `@api` を追加する
- 既存クラスから `@api` を削除する場合はメジャーバージョンアップが必要な破壊的変更です
- `dls:plugin:check-api`（DixlaseDevKit）で API サーフェスの一貫性を検証できます

---

## 15. JavaScript ランタイム API

プラグイン/テーマが `window.Dixlase` 経由で利用可能なクライアントサイド API。
コアの管理画面JSバンドルで登録され、プラグインJSから直接 `import` せずに使用できる。

### 15.1 ミックスイン (`window.Dixlase.mixins`)

| API | 説明 |
|---|---|
| `previewMixin(config)` | iframe プレビュー状態管理（デバイス切替、スケーリング） |
| `splitPaneMixin()` | スプリットペインレイアウト（ドラッグリサイズ） |
| `mergeMixins(...objects)` | getter 保持マージユーティリティ |
| `constants` | `DEVICE_PRESETS`, `MIN_PANE_WIDTH` 等の定数 |

### 15.2 ユーティリティ (`window.Dixlase`)

| API | 説明 |
|---|---|
| `newTabPreview(url, el)` | 親フォームのデータを収集し別タブでプレビュー |

---

## 16. プラグイン API に含まれないもの

以下は**内部実装の詳細**であり、例外の対象に含まれ**ません**:

- 上記に記載されたベースクラス以外の内部コントローラーロジック
- サービスクラスの実装（代わりにコントラクト/インターフェースを使用）
- ドキュメント化されていないモデルスコープメソッド
- リクエストバリデーションクラス（Form Request）
- 内部イベントハンドリング基盤
- 内部ジョブキュー基盤（`ShouldQueue` インターフェースを除く）
- リストされたクラスの private/protected メソッド
- モデルを通じて公開されていないデータベーススキーマの内部

内部コンポーネントに依存するプラグインは、完全な AGPL-3.0 条件の対象となる派生著作物となります。
