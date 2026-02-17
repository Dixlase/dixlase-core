# ログイン識別子確認機能の使用方法

ログイン識別子確認機能は、メールアドレスまたはアカウント名の存在確認を行う共通機能です。
管理者ログインとユーザーログインの両方で使用できます。

## 概要

`IdentifierCheckHelper`は、以下の機能を提供します：

- **レート制限**: IP + 識別子ベースのレート制限
- **タイミング攻撃対策**: 常に一定時間（100-300ms）待機
- **監査ログ記録**: 成功/失敗の両方を記録
- **統一されたエラーメッセージ**: ユーザー列挙攻撃対策
- **パスキー登録状況確認**: WebAuthn認証情報の有無を確認

## 基本的な使用方法

### 1. 管理者ログインでの使用例

```php
use App\Helpers\IdentifierCheckHelper;
use App\Models\Member;
use App\Models\MemberSetting;

class AdminLoginIdentifierCheckController extends AdminController
{
    public function check(Request $request)
    {
        $request->validate([
            'login' => 'required|string|max:255',
        ]);

        $login = $request->input('login');
        $ipAddress = $request->ip();
        
        // ロックアウト設定を取得
        $settings = IdentifierCheckHelper::getLockoutSettings(MemberSetting::class);
        
        // 識別子確認を実行（レート制限付き）
        $result = IdentifierCheckHelper::checkWithRateLimit(
            $login,
            $ipAddress,
            Member::class,
            $settings,
            'admin' // コンテキスト
        );
        
        return response()->json([
            'exists' => $result['exists'],
            'has_passkey' => $result['has_passkey'],
        ]);
    }
}
```

### 2. ユーザー管理プラグインでの使用例

```php
use App\Helpers\IdentifierCheckHelper;

class UserLoginIdentifierCheckController extends Controller
{
    public function check(Request $request)
    {
        $request->validate([
            'login' => 'required|string|max:255',
        ]);

        $login = $request->input('login');
        $ipAddress = $request->ip();
        
        // プラグインの設定モデルとユーザーモデルを使用
        $settings = IdentifierCheckHelper::getLockoutSettings(
            \Plugins\DixlaseUsers\App\Models\UserSetting::class
        );
        
        $result = IdentifierCheckHelper::checkWithRateLimit(
            $login,
            $ipAddress,
            \Plugins\DixlaseUsers\App\Models\User::class,
            $settings,
            'user' // コンテキストを'user'に設定
        );
        
        return response()->json([
            'exists' => $result['exists'],
            'has_passkey' => $result['has_passkey'],
        ]);
    }
}
```

### 3. サービスクラスを使用した実装（プラグイン内）

より複雑なロジックが必要な場合は、プラグイン内に専用のサービスクラスを作成できます：

**ファイル配置**: `plugins/DixlaseUsers/app/Services/UserLoginIdentifierCheckService.php`

```php
<?php

namespace Plugins\DixlaseUsers\App\Services;

use App\Helpers\IdentifierCheckHelper;

class UserLoginIdentifierCheckService
{
    public function check(
        string $login,
        string $ipAddress,
        string $userModelClass,
        string $settingModelClass
    ): array {
        // ロックアウト設定を取得
        $settings = IdentifierCheckHelper::getLockoutSettings($settingModelClass);
        
        // 識別子確認を実行（レート制限付き）
        return IdentifierCheckHelper::checkWithRateLimit(
            $login,
            $ipAddress,
            $userModelClass,
            $settings,
            'user' // コンテキストを'user'に設定
        );
    }
}
```

**コントローラーでの使用**:

```php
use Plugins\DixlaseUsers\App\Services\UserLoginIdentifierCheckService;

class UserLoginIdentifierCheckController extends Controller
{
    protected UserLoginIdentifierCheckService $identifierCheckService;

    public function __construct(UserLoginIdentifierCheckService $identifierCheckService)
    {
        $this->identifierCheckService = $identifierCheckService;
    }

    public function check(Request $request)
    {
        $request->validate([
            'login' => 'required|string|max:255',
        ]);

        $result = $this->identifierCheckService->check(
            $request->input('login'),
            $request->ip(),
            \Plugins\DixlaseUsers\App\Models\User::class,
            \Plugins\DixlaseUsers\App\Models\UserSetting::class
        );
        
        return response()->json([
            'exists' => $result['exists'],
            'has_passkey' => $result['has_passkey'],
        ]);
    }
}
```

## 設定要件

### 設定モデルの要件

`getLockoutSettings()`を使用するには、設定モデルに以下のキーが必要です：

```php
// 設定キー
'login_attempt_limit_enabled' => true/false,  // レート制限の有効/無効
'login_attempt_max_attempts' => 5,            // 最大試行回数
'login_attempt_time_window' => 15,            // 時間窓（分）
```

設定モデルには`getValue()`メソッドが必要です：

```php
public static function getValue(string $key, $default = null)
{
    // 設定値を取得するロジック
}
```

### ユーザーモデルの要件

ユーザーモデルには以下のカラムが必要です：

- `email`: メールアドレス
- `account_name`: アカウント名（オプション）

パスキー確認のため、以下のいずれかのリレーションが必要です：

- `webauthnCredentials()`: WebAuthn認証情報（推奨）
- `twoFaPasskeys()`: 二段階認証用パスキー
- `passkeys()`: 汎用パスキー

## 戻り値

`checkWithRateLimit()`は以下の配列を返します：

```php
[
    'exists' => true,           // ユーザーが存在するか
    'has_passkey' => false,     // パスキーが登録されているか
    'user' => User|null,        // ユーザーモデル（存在する場合）
]
```

## 例外処理

### ValidationException

レート制限超過またはユーザーが存在しない場合、`ValidationException`がスローされます：

```php
try {
    $result = IdentifierCheckHelper::checkWithRateLimit(...);
} catch (\Illuminate\Validation\ValidationException $e) {
    // エラーメッセージを取得
    $errors = $e->errors();
    // 'login' => 'メールアドレスまたはパスワードが正しくありません'
    // または
    // 'login' => 'ログイン試行回数が多すぎます。60秒後に再試行してください。'
}
```

## セキュリティ機能

### 1. レート制限

- IPアドレス + 識別子のハッシュでレート制限
- 設定可能な最大試行回数と時間窓
- ロックアウト時の監査ログ記録

### 2. タイミング攻撃対策

- ユーザーの存在/非存在に関わらず、常に100-300msの遅延
- レスポンス時間からユーザーの存在を推測できないようにする

### 3. ユーザー列挙攻撃対策

- 統一されたエラーメッセージ
- 「このメールアドレスは登録されていません」などの具体的なメッセージは返さない

### 4. 監査ログ

成功時：
```php
AuditLog::logAuth(AuditLog::ACTION_LOGIN_IDENTIFIER_CHECK, [
    'severity' => AuditLog::SEVERITY_INFO,
    'outcome' => AuditLog::OUTCOME_SUCCESS,
    'actor' => $user,
    'context' => [
        'login_identifier' => $login,
        'identifier_type' => 'email' or 'account_name',
        'check_context' => 'admin' or 'user',
    ],
]);
```

失敗時：
```php
AuditLog::logSecurity(AuditLog::ACTION_LOGIN_IDENTIFIER_NOT_FOUND, [
    'severity' => AuditLog::SEVERITY_WARNING,
    'outcome' => AuditLog::OUTCOME_FAILURE,
    'context' => [
        'login_identifier' => $login,
        'identifier_type' => 'email' or 'account_name',
        'check_context' => 'admin' or 'user',
    ],
]);
```

## カスタマイズ

### 独自のロックアウト設定を使用する場合

```php
$customSettings = [
    'enabled' => true,
    'max_attempts' => 10,
    'time_window' => 30,
];

$result = IdentifierCheckHelper::checkWithRateLimit(
    $login,
    $ipAddress,
    $userModelClass,
    $customSettings,
    'custom'
);
```

### パスキー確認ロジックのカスタマイズ

独自のパスキー確認ロジックが必要な場合は、`IdentifierCheckHelper`を継承してカスタマイズできます：

```php
class CustomIdentifierCheckHelper extends IdentifierCheckHelper
{
    protected static function hasPasskey($user): bool
    {
        // 独自のパスキー確認ロジック
        return $user->customPasskeys()->active()->count() > 0;
    }
}
```

## トラブルシューティング

### レート制限が機能しない

1. 設定モデルの`getValue()`メソッドが正しく実装されているか確認
2. `login_attempt_limit_enabled`が`true`に設定されているか確認
3. Redisまたはキャッシュドライバーが正しく設定されているか確認

### パスキー登録状況が正しく取得できない

1. ユーザーモデルに適切なリレーションが定義されているか確認
2. リレーション名が`webauthnCredentials`、`twoFaPasskeys`、`passkeys`のいずれかであることを確認

### タイミング攻撃対策の遅延が長すぎる

遅延時間は100-300msのランダムな値です。これは変更できませんが、セキュリティ上重要な機能です。

## 関連ドキュメント

- [ログインロックアウト機能](./login-lockout-usage.md)
- [二段階認証機能](./two-factor-authentication-usage.md)
- [監査ログ機能](./audit-log-usage.md)
