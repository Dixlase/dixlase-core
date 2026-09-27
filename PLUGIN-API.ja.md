# Dixlase CMS プラグイン API 境界

> **注記 — 暫定版。** 本書は Dixlase プラグイン API 境界定義の暫定版であり、プラグイン・テーマ例外条項（`LICENSE-EXCEPTIONS` を参照）が初期リリース時点で参照可能な API 境界を持てるよう公開するものです。境界定義は今後のリリースで精緻化される可能性があり、特定のリリースに適用されるのは、そのリリースと共に配布された版です。

**バージョン:** dev
**最終更新日:** 2026-09-27
**目的:** AGPL ライセンス例外条項のための公開プラグイン API 境界の定義（`LICENSE-EXCEPTIONS` を参照）

このドキュメントは「プラグイン API」を構成するすべてのコンポーネントを定義します。
プラグインおよびテーマが Dixlase プラグイン・テーマ例外に基づき、AGPL のコピーレフト義務を
発生させることなく使用できる公開インターフェース、サービス、設定を示します。

---

## 安定性宣言（Stability Pledge）

**状態:** 公開済み・未凍結（0.x ベータ期間）
**対象範囲:** 本ドキュメントに列挙された全 Plugin API 要素

**凍結までの扱い**

- 破壊的変更はありえますが、MINOR リリース（例: v0.2.0）でのみ行い、PATCH リリースでは行いません。`^0.1` を宣言した拡張は、v0.1.x の更新で壊れません。
- 破壊的変更は `CHANGELOG.md` の「Plugin API — 破壊的変更」に移行方法とともに記載し、可能な場合は 1 リリース分 `@deprecated` として残し、`dixlase_api` のバージョンを上げます。

**凍結したら**

- 凍結は `CHANGELOG.md` で告知し、`plugin-api-v1.0` タグを付けます。コアのバージョン番号（1.0 を含む）とは結び付けません。
- それ以降は下の廃止ポリシーに従います。破壊的変更は MAJOR リリースでのみ行い、移行のために最低 1 マイナーサイクル分の猶予を設けます。

### 廃止ポリシー（凍結後）

凍結後の Plugin API の要素を後方非互換に変更する必要が生じた場合:

1. **マイナーリリース**（例: v0.2.0）: 新しいシンボルを追加形式で導入し、旧シンボルは残したまま PHPDoc `@deprecated` を付与し、可能な箇所ではランタイム警告を発します。
2. **次のメジャーリリース**（例: v0.x で deprecation した場合は v1.0.0）: 廃止予定シンボルを削除します。`CHANGELOG.md` に移行ガイドを掲載し、置換先を本ドキュメントに記載します。
3. プラグイン・テーマ作者には、最低 1 マイナーサイクル分の移行猶予を保証します。

### 告知チャネル

Plugin API への追加・廃止予告・破壊的変更は、以下のチャネルで告知します:

- **`CHANGELOG.md`**（コアリポジトリ、正本）
- **GitHub Releases**（`Dixlase/dixlase-core`）

プラグイン・テーマ作者はコアリポジトリの GitHub Releases を subscribe しておくことを推奨します。

### 公開識別子の命名規則

Dixlase が公開する文字列識別子（API スコープ、permission キー、イベント名、Webhook event type、監査ログ action、プラグインケイパビリティ等）は [`docs/development/naming.md`](docs/development/naming.md) で凍結された命名規則に従います。これらの識別子は公開 API 面の一部であり、プラグイン・発行済み API キー・監査ログ行・外部 Webhook 連携がすべてハードコードしているため、出荷後に安全な改名はできません。

新規識別子を既存の種類に追加する場合は、`naming.md` で定義された形式に従ってください。新規の識別子*種類*を導入する場合は、先に同ドキュメントを更新してください。

### 対象 API バージョンの宣言

プラグイン・テーマは、書かれた時点で対象としていた Plugin API リビジョンをマニフェストで宣言する必要があります:

```json
"requires": {
    "dixlase_api": "^0.1"
}
```

これは拡張機能が遵守を約束するコントラクトです。コアはロードごとにこの宣言を検証し、（将来のリリースでは）互換性のない拡張機能の登録を拒否します。

- **フィールド:** `requires.dixlase_api`（semver 制約、例: `"^0.1"`）
- **現在のコア API バージョン:** `0.1.0`（定数 `App\Extension\ExtensionApi::CURRENT_VERSION`）
- **サポート範囲:** `^0.1`（定数 `App\Extension\ExtensionApi::SUPPORTED_RANGE`）
- **bump ルール:** 凍結前は、MINOR bump（0.1 → 0.2）で本ドキュメント記載の面に対する破壊的変更を含むことがあります。PATCH リリースでは含みません。凍結後は MAJOR bump でのみ含みます。

強制スケジュール:

| フェーズ | 動作 |
|---|---|
| **v0.1.0**（現行） | advisory のみ — 宣言が無い／非互換の場合は警告ログを出しヘルススコアを下げますが、登録は継続します。 |
| **v0.2.0** | 厳格 — 満たす宣言がない拡張機能はロード時に登録拒否、管理画面では `Incompatible` 表示。 |
| **v1.0.0** | 厳格 + プロセス内境界が拡張機能ごとの WASM サンドボックスに置き換わる際、本フィールドが WASM PHP インタープリタイメージの選択に利用されます。 |

既存の `requires.dixlase` フィールドは**コア製品全体**のバージョン範囲を追うもので、`requires.dixlase_api` とは別物です。コアの無関係なバグフィックスでバージョンが上がっても API コントラクトバージョンは上がりません。本ドキュメントに記載された Plugin API 面に変更があった場合のみ bump します。

新規拡張機能は `dls:make:plugin` / `dls:make:theme` のスキャフォールドで本フィールドが自動的に含まれます。既存拡張機能は `dls:plugin:update-json <Name> --add-api-version` または `dls:theme:update-json <Name> --add-api-version` で in-place に宣言を挿入できます。

---

## プラグイン・テーマのライセンスについて

Dixlase CMS と**このドキュメントに記載されたインターフェースのみを通じて**連携するプラグインおよびテーマは、Dixlase CMS の派生物とは**みなされません**。プロプライエタリライセンスを含む、任意のライセンスで配布することができます。

この権利は **Dixlase プラグイン・テーマ例外条項**（GNU AGPL バージョン3 第7条に基づく追加許可を付与する `LICENSE-EXCEPTIONS` ファイルを参照）によって付与されます。例外は以下の全条件を満たす場合に適用されます:

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
| `App\Contracts\Admin\AdminNavigationManagerInterface` | Admin panel navigation manager interface |
| `App\Contracts\Backup\BackupServiceInterface` | Backup service interface |
| `App\Contracts\Backup\RestoreServiceInterface` | Restore service interface |
| `App\Contracts\Cookie\ConsentStateProviderInterface` | Read the current visitor's cookie consent state. |
| `App\Contracts\CspPolicyProvider` | CSP Policy Provider Interface |
| `App\Contracts\Encryption\FileEncryptionServiceInterface` | File encryption service interface |
| `App\Contracts\Extension\ExtensionSourceInterface` | Extension Source Provider Interface |
| `App\Contracts\Extension\ProvidesSettingsDefaultsInterface` | Opt-in contract for extensions that own a `name` / `value` settings |
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
| `App\Contracts\Site\PageTitleBuilderInterface` | Composes the document title of a rendered page. |
| `App\Contracts\Site\SiteContextInterface` | Provides the current site context for the request. |
| `App\Contracts\Theme\ThemePermissionServiceInterface` | Theme permission management service interface |
| `App\Contracts\TranslationResolver` | Translation Resolver Contract |
| `App\Contracts\TwoFaInterface` | Interface for users with two-factor authentication functionality |
| `App\Contracts\TwoFa\TwoFaPasskeyServiceInterface` | Contract for Passkey (WebAuthn) authentication service |
| `App\Contracts\Verification\FileVerificationServiceInterface` | File integrity verification service interface |

### 1.2 Licensing Contracts

| コントラクト | 説明 |
|---|---|
| `App\Contracts\Licensing\LicenseVerifierInterface` | License verification contract (reserved for marketplace Phase 2) |

### 1.3 Plugin Integration Contracts

| コントラクト | 説明 |
|---|---|
| `App\Contracts\PluginIntegration\BlockProviderInterface` | Contract for plugins/themes that provide reusable Block components |
| `App\Contracts\PluginIntegration\CaptchaFormProviderInterface` | Contract for plugins that provide CAPTCHA forms |
| `App\Contracts\PluginIntegration\DashboardNotificationProviderInterface` | Contract for plugins that provide dashboard notifications |
| `App\Contracts\PluginIntegration\DashboardWidgetProviderInterface` | Contract for plugins that provide dashboard widgets |
| `App\Contracts\PluginIntegration\DeployProtectionRegistryInterface` | Aggregated view of every "protect from cross-environment sync |
| `App\Contracts\PluginIntegration\LinkableInterface` | Minimal contract for linkable content |
| `App\Contracts\PluginIntegration\LinkableProviderInterface` | Contract for plugins that provide linkable content |
| `App\Contracts\PluginIntegration\MenuProviderInterface` | Contract for plugins that provide navigation menus |
| `App\Contracts\PluginIntegration\PreviewProviderInterface` | Contract for plugins that provide preview data |
| `App\Contracts\PluginIntegration\PrivacyDataProviderInterface` | Contract that a plugin (or core subsystem) implements to declare which |
| `App\Contracts\PluginIntegration\PrivacyPolicyProviderInterface` | Privacy policy provider contract |
| `App\Contracts\PluginIntegration\SeoMetaProviderInterface` | Interface to provide read/write access to SEO meta information per content |

### 1.4 Plugin Capability Contracts

| コントラクト | 説明 |
|---|---|
| `App\Contracts\Plugin\ApiResourceProviderInterface` | API resource provider interface |
| `App\Contracts\Plugin\ContentProviderCapableInterface` | Interface declaring content provider capability |
| `App\Contracts\Plugin\EditorCapableInterface` | Interface declaring editor provision functionality |
| `App\Contracts\Plugin\MailCapableInterface` | Interface declaring mail sending functionality |
| `App\Contracts\Plugin\PluginCapabilityInterface` | Base interface for plugin capability declaration |
| `App\Contracts\Plugin\PluginPermissionServiceInterface` | Contract for plugin permission management service |
| `App\Contracts\Plugin\SignatureVerifierInterface` | Signature verification contract |

### 1.5 Repository Contracts

| コントラクト | 説明 |
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

## 2. プラグイン用トレイト

| トレイト | 説明 |
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
| `App\Traits\VerifiesCaptcha` | Server-side CAPTCHA verification for form requests. |

---

## 3. 拡張用ベースコントローラー

| コントローラー | 説明 |
|---|---|
| `App\Http\Controllers\Admin\AdminController` | Admin panel base controller |
| `App\Http\Controllers\Admin\AdminLoggedInController` | Admin panel controller that requires authentication |

---

## 4. データ転送オブジェクト（DTO）

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

## 5. Enum

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

## 6. Blade コンポーネント

### 6.1 フォームコンポーネント

`x-form-text`, `x-form-email`, `x-form-textarea`, `x-form-select`,
`x-form-checkbox`, `x-form-checkbox-group`, `x-form-toggle`, `x-form-toggle-group`,
`x-form-radio-group`, `x-form-radio-card-group`, `x-form-color`, `x-form-range`,
`x-form-label`, `x-form-error`, `x-form-help-text`, `x-form-button`, `x-form-hidden`,
`x-form-input-with-label`, `x-form-password-tools`, `x-form-required-badge`,
`x-form-content-editor`

### 6.2 UI コンポーネント

`x-ui-modal`, `x-ui-modal-vanilla`, `x-ui-notification`,
`x-ui-message`, `x-ui-flash-message`, `x-ui-status-badge`,
`x-ui-pagination`, `x-ui-pagination-controls`, `x-ui-tooltip`, `x-ui-datetime`,
`x-ui-maintenance-banner`, `x-ui-admin-maintenance-banner`, `x-ui-system-banner`,
`x-ui-appearance-mode-selector`, `x-ui-language-switcher`, `x-ui-admin-bar`

`x-ui-datetime` は UTC で保存された日時をサイトの `display_timezone` に変換して `<time>` 要素として描画する。
プロパティ: `:value`（Carbon / DateTimeInterface / 文字列 / int / null）、
`format`（PHP date format または `date|datetime|full|iso` のキー）、`empty-label`（null/空文字時の代替表示）。

### 6.3 管理画面コンポーネント

`x-admin.save-button`, `x-admin.delete-button`, `x-admin.danger-zone`,
`x-admin.account-status`, `x-admin.right-sidebar`,
`x-admin.mode-guide-banner`, `x-admin.mode-partial-notice`, `x-admin.mode-readonly-banner`,
`x-admin.theme-preview-container`, `x-admin.theme-preview-sidebar`,
`x-admin.theme-preview-sidebar-section`,
`x-revision.list`, `x-revision.diff`

### 6.4 フロントエンドコンポーネント

`x-front.button`, `x-front.card`, `x-front.breadcrumb`, `x-front.navigation`

### 6.5 コンテンツエディタコンポーネント

`x-content-editor.tabs`, `x-content-editor.preview-tabs`,
`x-content-editor.preview-pane`, `x-content-editor.new-tab-preview`,
`x-content-editor.scroll-buttons`, `x-content-editor.storage-info`,
`x-content-editor.type-badge`

### 6.6 セキュリティ・認証コンポーネント

`x-captcha`,
`x-security.login-attempt-limit-settings`, `x-security.login-identifier-mode-selector`,
`x-security.login-notification-selector`, `x-security.passkey-device-settings`,
`x-security.password-settings`, `x-security.session-settings`,
`x-security.two-fa-detailed-settings`, `x-security.two-fa-general-settings`,
`x-two-fa.management`, `x-two-fa.mode-selector`, `x-two-fa.individual-settings`,
`x-auth.login-form`, `x-auth.account-verification`, `x-auth.forgot-password`,
`x-auth.reset-password`, `x-auth.verification-notice`, `x-auth.login-field`,
`x-application-logo`, `x-auth-session-status`

### 6.7 メディアコンポーネント

`x-media.picker`, `x-media.selector`

### 6.8 レイアウトテンプレート

プラグイン/テーマは `@extends('layouts.{name}')` を介して以下の Blade レイアウトを拡張できます。これらは `@api` でマークされた安定レイアウトで、[Stability Pledge](#stability-pledge) に基づき後方互換性が約束されています。

| レイアウト | 使用例 | 説明 |
|---|---|---|
| `layouts.admin` | `@extends('layouts.admin')` | 管理画面レイアウト(サイドバー、トップバー、ダークモード、フラッシュメッセージ)。プラグインの管理画面ビューを含むすべての管理ページで使用。 |

---

## 7. ミドルウェアと API サーフェス

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

### 7.3 API URL バージョニングとプラグインルートのレイアウト

REST API エンドポイントは `/api/v1/...` 配下に配置されます。プラグインは API ルートを次のファイルに置きます:

```
plugins/{Name}/routes/api/v1.php
```

コア側の自動ローダがこのファイルを `Route::prefix('api/v1')->middleware(EnsurePluginActiveOnSite::class.':'.$pluginSlug)->group(...)` で包むため、プラグイン作者はスラッグ配下のパスのみを記述します（例: `Route::prefix('my-plugin')->group(...)`）。結果 URL は `/api/v1/my-plugin/...` になります。プラグインが解決済みサイト上で無効化されている場合、`App\Http\Middleware\EnsurePluginActiveOnSite` がコントローラ実行前にリクエストを 404 JSON エンベロープで短絡します。

`/api/v1/` 配下の予約名前空間 — プラグインルートは以下と衝突してはいけません:

| パス | オーナー |
|---|---|
| `/api/v1/health` | Core（公開の死活確認）|
| `/api/v1/resources/{type}/{slug}` | 将来の DixlaseApi プラグイン（`ApiResourceProviderInterface` を自動発見）|
| `/api/v1/privacy/...` | 将来の Privacy API（`PrivacyDataProviderInterface` 上に構築）|

旧来の `routes/api.php`（`v1.php` ファイル名なし）も後方互換のためそのままロードされますが **deprecated** 扱いです。ローダは起動毎に `Log::warning` を出すため、プラグインは順次 `routes/api/v1.php` に移行してください。新規プラグインは初日から `routes/api/v1.php` を使うべきです。完全な契約: [REST API バージョニング](docs/development/api-reference/versioning.md)。

### 7.4 API レスポンスエンベロープ

[REST API バージョニング](docs/development/api-reference/versioning.md) で文書化されている統一エンベロープ `{data, meta, links}` / `{error: {code, message, details?}, meta}` は以下のクラスで実装されています。JSON を返すプラグインエンドポイントはこれらを継承または呼び出して、レスポンス形をコアの例外ハンドラと揃えてください:

| クラス | 用途 |
|---|---|
| `App\Http\Resources\BaseApiResource` | 単一リソースエンドポイントの abstract 基底。継承して `toArray($request)` を実装すると、基底側で `meta.site_id`・`meta.timestamp`・`links.self` が自動付与される。 |
| `App\Http\Resources\BaseApiCollection` | リストエンドポイントの abstract 基底。同じエンベロープに加え、Laravel paginator を包むと `meta.pagination` と `links.next` / `links.prev` が自動生成される。 |
| `App\Support\Api\ApiErrorResponse` | `make(code, status, message, details)` で統一エラーエンベロープを生成する static ファクトリ。`meta()` も公開しているのでレスポンスを手組みするコードでも形を揃えられる。 |

エンベロープ内のエラーメッセージは常に英語です（クライアント側で安定した `code` フィールドを翻訳テーブルに引いてください）。API エラーレスポンスは決して 3xx リダイレクトしてはいけません。認証なしでアクセスできるプラグインエンドポイントは、リダイレクトを返すのではなくプラグイン自身の README で公開範囲を明記してください。

API キー認証は `App\Http\Middleware\AuthenticateApiKey`（ルートエイリアス `auth.api`）が担当し、`missing_credentials` / `invalid_credentials` / `ip_not_allowed` / `insufficient_scope` の失敗時は `ApiErrorResponse::make()` を経由してエンベロープを返します。ネットワークスコープのキー（site_id = NULL、CLI の `dls:api:create-network-key` 経由で発行）の使用は `severity = notice` の `network_api_key_used` アクションとして自動的に audit されます。

### 7.5 ミドルウェアグループへの直接登録

プラグインの ServiceProvider は、ミドルウェアクラスをルート単位ではなくグローバルな `web` ミドルウェアグループに直接登録できます。利用可能なメソッドは以下の 2 つです。

| メソッド | 挿入位置 | 推奨用途 |
|---|---|---|
| `Router::pushMiddlewareToGroup('web', $class)` | `web` グループの**末尾**に追加 | リクエストが完全に解決された後に動く必要がある横断的なレスポンス装飾（例: SEO メタタグ注入、後段ヘッダ書き換え） |
| `Router::prependMiddlewareToGroup('web', $class)` | `web` グループの**先頭**に挿入 | 他の `web` ミドルウェアが動く前に短絡する必要がある早期終了系（例: URL リダイレクト判定で 3xx を返す処理） |

これらの登録を行うプラグインは `plugin.json` の `permissions.system.register_middleware: true` を**必ず宣言**してください。パーミッションスキャナは `pushMiddlewareToGroup(...)` と `prependMiddlewareToGroup(...)` の両方を検出します。

#### `prependMiddlewareToGroup('web', ...)` の利用は予約されています

`prependMiddlewareToGroup('web', ...)` は**リダイレクト系プラグイン専用**です（つまり、アプリケーションロジックが動く前に 301/302/307/308 でリクエストを短絡することを主な責務とするプラグイン）。それ以外のカテゴリのプラグインは `pushMiddlewareToGroup` を使ってください。

理由：

- 同じグループに複数プラグインが prepend した場合、結果として「最後に prepend した方が先頭になる」順序が生まれます。これはプラグインの boot 順（さらに DB 上の有効化順）に依存するため、リダイレクト系プラグイン同士で決定論的な順序を保証する API はコアにありません。「先頭は単一カテゴリの責務に固定する」という予約により、この曖昧さを避けます。
- リダイレクト系でないプラグインが prepend した場合、後からリダイレクト系プラグインが有効化されると無言で順序が入れ替わり、リクエストを最初に受け取れなくなる可能性があります。

将来、リダイレクト用途以外で複数プラグインの優先順位制御が本当に必要になった場合は、コア側に明示的な優先度付き `MiddlewareRegistry` を導入する形で対応します。それまではこの予約がコントラクトです。

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

**命名規約:** サービスは役割を明確にするため以下のサフィックス規約に従います。プラグイン側でサービスを実装する場合も、コア API との一貫性のため同じサフィックス規約に従うことを推奨します。

| サフィックス | 役割 | 例 |
|---|---|---|
| `*Service` | データに対するビジネスロジック・オーケストレーション | `PasswordService`, `EditorManager` |
| `*Registry` | プラガブル要素の登録・検索・一覧化 | `PermissionRegistry`, `CspPolicyRegistry`, `RouteSlugRegistry` |
| `*Manager` | ライフサイクル管理（有効化、無効化、初期化、再読み込み） | `AdminNavigationManager` |
| `*Resolver` | 実行時の依存・能力解決 | `PluginServiceResolver` |

### 9.1 プラグイン/テーマサービス

以下の `@api` マーク付きサービスは DI で直接注入できます。
その他のサービスはインターフェース経由でアクセスします（セクション 1 を参照）。

- `App\Multilingual\SiteTaglineProvider` — `@api`、直接 DI
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
- `App\Services\ContentPreviewService`
- `App\Services\DatabaseCleanupService`
- `App\Services\SystemNotificationService`
- `App\Services\Auth\AuthContextRegistryService`
- `App\Services\Editor\EditorManager`
- `App\Services\LegalPageService`
- `App\Services\MailServerValidatorService`
- `App\Services\RouteSlugRegistry`
- `App\Services\SystemWarningService`
- `App\Services\CommentTranslation\TranslationFileService`
- `App\Services\CommentTranslation\CommentBuilderService`
- `App\Services\CommentTranslation\ExtensionDictionaryLocator`
- `App\Services\Site\PageTitleBuilder`

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
| `App\Helpers\ConfigHelper` | セキュリティ設定・セッション設定の取得ヘルパー（`getDisplayTimezone()` を含む） |
| `App\Helpers\DateTimeHelper` | UTC→表示用 TZ 変換とフォーマット（名前付きフォーマット・DST 対応・null 許容） |
| `App\Helpers\ComposerLocalHelper` | composer-local.json の管理ヘルパー |
| `App\Helpers\GitExcludeHelper` | .git/info/exclude ファイル管理ヘルパー |
| `App\Helpers\GitIgnoreHelper` | .gitignore ファイル管理ヘルパー |
| `App\Helpers\GlobalHelper` | グローバルヘルパー関数（shortcode_parse 等） |
| `App\Helpers\LocaleHelper` | 言語設定・ロケール判定ヘルパー |
| `App\Helpers\LoginHelper` | ログイン認証・2FA チェック・セッション管理ヘルパー |
| `App\Helpers\LoginLockoutHelper` | ログインロックアウト判定・記録ヘルパー |
| `App\Helpers\PluginHelper` | プラグイン有効状態・パス・ルート読み込みヘルパー |
| `App\Helpers\TwoFaHelper` | 二段階認証コード生成・送信・設定取得ヘルパー |

### 9.6.1 マルチサイトサービス (`App\Services\Site\*`)

将来を見据えたマルチサイトレイヤー。新規 per-site 動作を実装する
プラグイン・テーマコードは、`site_settings` / `global_settings` への
直接 Eloquent / DB クエリではなくこれらのサービスを使うべき。

| サービス | 説明 |
|---|---|
| `App\Services\Site\SiteContext` | リクエストの「現在のサイト」を解決(`SiteContextInterface` の実装) |
| `App\Services\Site\SettingDefinition` | 設定キーの value object(`name` / `scope` / `default` / `type`) |
| `App\Services\Site\SettingDefinitionRegistry` | 設定定義の中央レジストリ。プラグインは ServiceProvider で `register()` を呼ぶ |
| `App\Services\Site\SettingResolver` | 3 分類 scope モデル(`Global` / `PerSite` / `Overridable`)で設定を読み書き |
| `App\Services\Site\SiteStorage` | サイト対応ファイルシステムヘルパー(`private()` / `public()` / `global()` で multisite tree を root に) |
| `App\Services\Site\Exceptions\UnknownSettingException` | 未登録キーを読んだ際に発火。全キーを事前登録させる強制力を持つ |

また `App\Models\Traits\BelongsToSite` は per-site Eloquent トレイト。
`site()` belongsTo + 現在サイトの Global Scope + 作成時 `site_id` 自動付与
を提供する。`site_id` カラムを持つプラグインモデルに適用する。

### 9.6.2 キャッシュヘルパー (`App\Support\Cache\*`)

Dixlase の命名規約 `dixlase:{scope}:{owner}:{domain}:{key}` に従って
キャッシュキーを生成する Builder ヘルパー。手書きでキーを組み立てる
代わりにこれらを使うことで、プラグイン・テーマ間のキャッシュ衝突を
防ぎ、マルチサイトが顕在化したときにサイト単位で分離できる状態を
保てる。

| ヘルパー | 説明 |
|---|---|
| `App\Support\Cache\CacheKey` | core / plugin / theme / tag キー用の static Builder。`site($id)` でサイトスコープ Builder への入口を提供 |
| `App\Support\Cache\SiteScopedCacheKey` | `CacheKey::site($id)` が返す fluent Builder。サイト id でパーティション化された複合キーを生成 |

詳細フォーマット・タグ規約・実用例は [キャッシュキー命名規約](docs/ja/development/cache-key-convention.md) を参照。

### 9.7 アクター

- `App\Actors\MemberActor` — Action フレームワーク用のメンバーアクター実装

### 9.8 イベント

- `App\Events\AuditLogCreated` — 監査ログ作成イベント（SIEM 連携・プラグインフック用）。`$event->payload` でバージョン管理された型付き `App\DTO\Audit\AuditLogPayload` を運ぶ。スキーマバージョン: `AuditLogCreated::SCHEMA_VERSION`。
- `App\Events\SecurityAlertEvent` — セキュリティアラートイベント
- `App\Events\DixlaseEvents` — プラグインがリッスン可能なコアイベント名定数（下表参照）

**イベント名定数** (`App\Events\DixlaseEvents::*`): プラグインは以下の定数を `Event::listen()` のターゲットに使用できます。クラス自体が Plugin API の一部であり、定数の追加は非破壊変更として扱います。定数の削除は廃止ポリシーに従います。

| カテゴリ | 定数 |
|---|---|
| バックアップライフサイクル | `BACKUP_STARTED`, `BACKUP_COMPLETED`, `BACKUP_FAILED`, `BACKUP_CLEANUP_STARTED`, `BACKUP_CLEANUP_COMPLETED`, `BACKUP_RESTORE_STARTED`, `BACKUP_RESTORE_COMPLETED`, `BACKUP_RESTORE_FAILED` |
| バックアップ暗号化 | `BACKUP_ENCRYPTING`, `BACKUP_ENCRYPTED`, `BACKUP_ENCRYPTION_FAILED` |
| バックアップ検証 | `BACKUP_VERIFYING`, `BACKUP_VERIFIED`, `BACKUP_VERIFICATION_FAILED` |
| デプロイ | `DEPLOY_BEFORE`, `DEPLOY_AFTER`, `DEPLOY_FAILED`, `DEPLOY_SYNC_BEFORE`, `DEPLOY_SYNC_AFTER`, `DEPLOY_DATABASE_BEFORE`, `DEPLOY_DATABASE_AFTER` |
| 翻訳 | `LOCALE_CHANGED`, `TRANSLATION_MODEL_RETRIEVED`, `TRANSLATION_MODEL_SAVING`, `TRANSLATION_MODEL_SAVED`, `TRANSLATION_MODEL_DELETED`, `TRANSLATION_FIELD_UPDATED`, `TRANSLATION_FIELD_DELETED` |
| プラグインライフサイクル | `PLUGIN_INSTALLING`, `PLUGIN_INSTALLED`, `PLUGIN_ACTIVATING`, `PLUGIN_ACTIVATED`, `PLUGIN_DEACTIVATING`, `PLUGIN_DEACTIVATED`, `PLUGIN_UNINSTALLING`, `PLUGIN_UNINSTALLED`, `PLUGIN_UPDATING`, `PLUGIN_UPDATED` |
| テーマライフサイクル | `THEME_ACTIVATING`, `THEME_ACTIVATED` |
| キャッシュ | `CACHE_CLEARING`, `CACHE_CLEARED` |
| メンテナンス | `MAINTENANCE_ENABLED`, `MAINTENANCE_DISABLED` |
| セキュリティ | `INTEGRITY_SCAN_STARTED`, `INTEGRITY_SCAN_COMPLETED`, `SECURITY_ALERT`, `BOT_DETECTED`, `LOGIN_ANOMALY_DETECTED` |
| 監査 | `AUDIT_LOG_CREATED` |
| AI（予約） | `AI_OPERATION_LOGGED` |

ヘルパーメソッド: `DixlaseEvents::all()` で全イベント名を取得、`DixlaseEvents::byCategory(string $category)` でカテゴリプレフィックスでフィルタ。

### 9.9 バリデーションルール

- `App\Rules\UniqueContentSlug` — コンテンツスラッグの一意性バリデーション
- `App\Rules\UniqueRouteSlug` — ルートスラッグの一意性バリデーション

### 9.10 ファサード(便利アクセサ)

主要なコアサービスへの便利な静的アクセサ。プラグイン/テーマは、サービスを DI する代わりにこれらのファサードを使用できます。

| ファサード | 対応サービス | 用途 |
|---|---|---|
| `App\Facades\Audit` | `App\Services\AuditService` | 監査イベントの記録(認証、セキュリティ、コンテンツ、プラグインライフサイクル等) |
| `App\Facades\SiteContext` | `App\Services\Site\SiteContext` | 現在のサイトを解決(`SiteContext::currentSiteId()`、`currentSite()`、`isPluginActive($slug)`) |
| `App\Facades\SiteSettings` | `App\Models\SiteSetting` | マルチサイト対応の resolver 経由でサイト/ネットワーク設定を読み取り(`SiteSettings::get('site_name')`) |
| `App\Facades\PluginPermission` | `App\Services\Plugin\PluginPermissionService` | プラグイン宣言済み権限の検証 |
| `App\Facades\Webhook` | `App\Services\WebhookDispatcher` | プラグインコードからの Webhook イベント送信 |

ファサードは Laravel 標準パターンで配線されています。完全な `@method` PHPDoc は各ファサードファイルを参照してください。

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
- `App\Models\SitePluginActivation` — サイト単位のプラグイン有効化レコード(どのプラグインがどのサイトで有効か)
- `App\Models\SiteThemeActivation` — サイト単位のテーマ有効化レコード

### 10.4 システムモデル

- `App\Models\Site` — サイト本体(マルチサイト土台)
- `App\Models\SiteSetting` — サイト単位の設定(旧 `BaseSetting`)。scope に応じたルーティングは `SettingResolver` 経由
- `App\Models\GlobalSetting` — ネットワーク全体の設定(旧 `BaseSetting` / `SecuritySetting` の一部)
- `App\Models\SecuritySetting` — セキュリティキー用エイリアス(テーブルは `global_settings`)
- `App\Models\FrontSetting` — サイト単位のフロント設定

---

## 11. グローバルヘルパー関数

| 関数 | 説明 |
|---|---|
| `shortcode_parse(string $content): string` | コンテンツ内のショートコードを解析・実行する |
| `render_x_cloak_style(): string` | `[x-cloak]` 用の style ブロックを出力(Alpine.js 初期描画時のちらつき抑制) |
| `dls_page_title(?string $pageName = null): string` | サイト名・タグライン・ページ名からドキュメントタイトルを合成する。戻り値はプレーンテキストなので、呼び出し側でエスケープすること |

### 11.1 Blade ディレクティブ

| ディレクティブ | 説明 |
|---|---|
| `@pageTitle` | `<title>` 要素全体を出力する。描画中のページの `title` セクション、または明示的な引数 (`@pageTitle($post->title)`) を読む。テーマがタイトルを出力する際の標準手段で、内部で `dls_page_title()` を呼び、結果を 1 度だけエスケープする。旧バージョンのコアでも動かす必要があるテーマは `@if(function_exists('dls_page_title'))` でガードする(未登録のディレクティブはコンパイルエラーにならず、そのまま文字列として残るため) |

---

## 11.5 読み取り専用コア config キー

プラグイン/テーマは Laravel の `config()` ヘルパーを通じて以下のコア config キーを読み取れます。これらは Plugin API の安定性宣言の対象で、メジャーバージョン内では名称・値の形が非互換に変更されません。**読み取り専用** として扱い、書き込みは責任あるコアサービス経由でのみ行ってください。

### 11.5.1 管理画面設定 (`config/admin.php`)

| キー | 型 | 説明 |
|---|---|---|
| `admin.admin_url` | `string` | 管理画面 URL prefix(例: `admin`)。管理ルートのパス構築に使用 |
| `admin.mediaPath` | `string` | 公開メディアパス(URL ヘルパー用) |
| `admin.files.mediaPath` | `string` | ファイルシステム上のメディアパス(ストレージ操作用) |

### 11.5.2 セキュリティ (`config/security.php`)

| キー | 型 | 説明 |
|---|---|---|
| `security.admin_url` | `string` | 管理画面 URL prefix(security 経由のミラー、`admin.admin_url` を優先) |

### 11.5.3 コンテンツセキュリティポリシー (`config/csp.php`)

| キー | 型 | 説明 |
|---|---|---|
| `csp.base.mode` | `string` | 有効な CSP モード(`development`, `standard`, `strict`) |
| `csp.base.admin_mode` | `string` | 管理画面用 CSP モード上書き |
| `csp.base.modes` | `array` | モード → ディレクティブセットのマップ |
| `csp.base.nonce_length` | `int` | nonce バイト長 |
| `csp.base.report_uri` | `string\|null` | CSP 違反レポートエンドポイント |
| `csp.directives` | `array` | 解決済み CSP ディレクティブ |
| `csp.domains` | `array` | 許可された外部ドメイン |

### 11.5.4 多言語化 (`config/language.php`)

| キー | 型 | 説明 |
|---|---|---|
| `language.default` | `string` | デフォルトロケール(例: `en`) |
| `language.languages` | `array` | 対応ロケール一覧 |
| `language.translations` | `array` | ロケール → 表示名のマップ |

### 11.5.5 地域 (`config/regions.php`)

| キー | 型 | 説明 |
|---|---|---|
| `regions.countries` | `array` | 国コード → 国名のマップ |
| `regions.prefectures` | `array` | 都道府県コード → 名称のマップ(日本特化) |

### 11.5.6 テーマ (`config/themes.php`)

| キー | 型 | 説明 |
|---|---|---|
| `themes.theme_directory` | `string` | テーマディレクトリパス(base からの相対) |

### 11.5.7 拡張ソース (`config/extension-sources.php`)

| キー | 型 | 説明 |
|---|---|---|
| `extension-sources.default_author_id` | `string\|null` | 新規拡張エントリのデフォルト著者 ID |
| `extension-sources.default_authority_key_id` | `string\|null` | デフォルトの Ed25519 署名鍵 ID |

> **Plugin API 対象外:** Laravel 標準の config(`app.*`, `auth.*`, `cache.*`, `queue.*`, `session.*`, `mail.*`, `database.*`, `filesystems.*` 等)は Laravel 自身の安定性に従う — Laravel ドキュメントを参照。一覧にないコア config は予告なく変更される可能性あり。

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

`permissions.system.register_blocks` は **予約済み** のフィールドで、`App\Contracts\PluginIntegration\BlockProviderInterface` の実装（GUI エディタやテーマウィジェット領域に配置可能な Block コンポーネント）を登録するプラグインで使用します。Block レジストリ、`x-block` レンダラコンポーネント、管理UI は v0.1.0 では未実装ですが、プラグイン作者が安定した接続点に依存できるよう、Contract と permission スロットを先に予約しています。

---

## 12.5 theme.json スキーマ（ウィジェット領域予約）

レイアウト中の固定領域（サイドバー、フッター等）にプラグイン提供の Block を配置できるテーマは、`theme.json` の **`widget_areas`** キーで領域を宣言します。このフィールドは v0.1.0 で **予約済み** であり、テーマ作者は今のうちに宣言しておけば、将来の Dixlase リリースでテーマを書き換えずにプラグイン Block を当該領域へレンダリングできるようになります。

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

| キー | 型 | 説明 |
|---|---|---|
| `widget_areas[].name` | `string` | `x-widget-area` コンポーネントの `name` prop から参照する安定識別子（snake_case 推奨）。 |
| `widget_areas[].label` | `object` | 多言語表示名（`{en, ja}`）。 |
| `widget_areas[].description` | `object` | 任意。管理UI に表示する多言語説明。 |

ウィジェット領域を持たないテーマ（例: ワンページLP用テーマ）はフィールドを省略するか、空配列で宣言できます。

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

## 15. プラグイン API に含まれないもの

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
