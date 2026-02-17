# セキュリティ設定統一レジストリ

## 概要

`SecuritySettingsRegistry`は、散らばったセキュリティ設定を一元管理するサービスです。複数のテーブル（`security_settings`、`members_settings`、`base_settings`）に分散した設定を、統一されたAPIで取得・設定できます。

## 設定カテゴリ

| カテゴリ | 説明 |
|---------|------|
| `auth` | 認証（二段階認証、パスワードポリシー） |
| `login` | ログイン（試行回数、ロックアウト） |
| `session` | セッション（ドライバー、有効期間） |
| `captcha` | CAPTCHA設定 |
| `ip` | IP制限（許可/ブロックリスト） |
| `csp` | Content Security Policy |
| `extension` | 拡張機能セキュリティ |
| `notification` | システム通知 |
| `api` | API設定（レートリミット、署名） |
| `lockdown` | ロックダウン設定 |

## 使用方法

### 設定の取得

```php
use App\Services\SecuritySettingsRegistry;

// 単一の設定を取得
$captchaEnabled = SecuritySettingsRegistry::get('captcha_enabled');
$maxAttempts = SecuritySettingsRegistry::get('login_max_attempts');

// デフォルト値を指定
$timeout = SecuritySettingsRegistry::get('api_timeout', 30);

// カテゴリ別に取得
$authSettings = SecuritySettingsRegistry::getByCategory('auth');
// => ['two_factor_enabled' => true, 'password_min_length' => 8, ...]

// 全設定を取得
$allSettings = SecuritySettingsRegistry::getAll();

// カテゴリ別にグループ化して取得
$grouped = SecuritySettingsRegistry::getAllGrouped();
// => ['auth' => [...], 'login' => [...], ...]
```

### 設定の更新

```php
// 単一の設定を更新
SecuritySettingsRegistry::set('captcha_enabled', true);
SecuritySettingsRegistry::set('login_max_attempts', 10);

// 複数の設定を一括更新
SecuritySettingsRegistry::setMultiple([
    'captcha_enabled' => true,
    'captcha_driver' => 'google',
    'captcha_site_key' => 'xxx',
]);
```

### 設定定義の取得

```php
// 特定の設定の定義を取得
$definition = SecuritySettingsRegistry::getDefinition('captcha_enabled');
// => [
//     'category' => 'captcha',
//     'source' => 'security_settings',
//     'type' => 'bool',
//     'default' => false,
//     'description' => 'CAPTCHA有効/無効',
// ]

// 全定義を取得
$allDefinitions = SecuritySettingsRegistry::getDefinition();

// 設定が存在するか確認
if (SecuritySettingsRegistry::has('captcha_enabled')) {
    // ...
}
```

### キャッシュ管理

```php
// 特定の設定のキャッシュをクリア
SecuritySettingsRegistry::clearCache('captcha_enabled');

// 全キャッシュをクリア
SecuritySettingsRegistry::clearCache();
```

### エクスポート/インポート

```php
// 設定をエクスポート（バックアップ）
$backup = SecuritySettingsRegistry::export();
// => [
//     'version' => '1.0',
//     'exported_at' => '2025-12-21T21:00:00+09:00',
//     'settings' => [...],
// ]

// 機密情報を含めてエクスポート
$backup = SecuritySettingsRegistry::export(includeSensitive: true);

// 設定をインポート（リストア）
$count = SecuritySettingsRegistry::import($backup);
```

## 設定一覧

### 認証 (auth)

| キー | 型 | デフォルト | 説明 |
|-----|-----|----------|------|
| `two_factor_enabled` | bool | false | 二段階認証の有効/無効 |
| `two_factor_mode` | string | optional | 二段階認証モード |
| `password_min_length` | int | 8 | パスワード最小文字数 |
| `password_require_mixed_case` | bool | true | 大文字小文字を必須にするか |
| `password_require_numbers` | bool | true | 数字を必須にするか |
| `password_require_symbols` | bool | false | 記号を必須にするか |
| `password_check_pwned` | bool | true | 漏洩パスワードチェック |

### ログイン (login)

| キー | 型 | デフォルト | 説明 |
|-----|-----|----------|------|
| `login_max_attempts` | int | 5 | ログイン試行回数上限 |
| `login_lockout_duration` | int | 15 | ロックアウト時間（分） |
| `login_notification_enabled` | bool | true | ログイン通知 |
| `lockout_notification_enabled` | bool | true | ロックアウト通知 |

### セッション (session)

| キー | 型 | デフォルト | 説明 |
|-----|-----|----------|------|
| `session_driver` | string | file | セッションドライバー |
| `session_lifetime` | int | 120 | セッション有効期間（分） |
| `session_encrypt` | bool | false | セッション暗号化 |

### CAPTCHA (captcha)

| キー | 型 | デフォルト | 説明 |
|-----|-----|----------|------|
| `captcha_enabled` | bool | false | CAPTCHA有効/無効 |
| `captcha_driver` | string | google | CAPTCHAドライバー |
| `captcha_site_key` | string | | サイトキー |
| `captcha_secret_key` | string | | シークレットキー（機密） |

### IP制限 (ip)

| キー | 型 | デフォルト | 説明 |
|-----|-----|----------|------|
| `enable_allowed_admin_ips` | bool | false | 管理画面IP許可リスト有効 |
| `allowed_admin_ips` | string | | 管理画面許可IPリスト |
| `enable_blocked_admin_ips` | bool | false | 管理画面IPブロックリスト有効 |
| `blocked_admin_ips` | string | | 管理画面ブロックIPリスト |

### CSP (csp)

| キー | 型 | デフォルト | 説明 |
|-----|-----|----------|------|
| `csp_enabled` | bool | true | CSP有効/無効 |
| `csp_mode` | string | standard | CSPモード |
| `csp_log_violations` | bool | true | CSP違反をログに記録 |

### API (api)

| キー | 型 | デフォルト | 説明 |
|-----|-----|----------|------|
| `api_rate_limit_enabled` | bool | true | レートリミット有効 |
| `api_rate_limit_per_minute` | int | 60 | 1分あたりのリクエスト上限 |
| `api_signature_required` | bool | true | 署名を必須にするか |
| `api_timestamp_tolerance` | int | 300 | タイムスタンプ許容範囲（秒） |

## 設定ソース

設定は以下のテーブルに保存されます：

| ソース | テーブル | 用途 |
|--------|---------|------|
| `security_settings` | `security_settings` | セキュリティ全般 |
| `members_settings` | `members_settings` | メンバー関連 |
| `base_settings` | `base_settings` | 基本設定 |

レジストリは各設定がどのソースに保存されるかを自動的に判断し、適切なテーブルに読み書きします。

## キャッシュ

設定値は5分間キャッシュされます。設定を更新すると、該当するキャッシュは自動的にクリアされます。

## β版以降の予定

- 管理画面でのセキュリティ設定ダッシュボード
- 設定変更の監査ログ
- 設定のバリデーション強化
- プラグインからの設定登録API
