# ログインロックアウト機能の使用方法

Dixlaseのログインロックアウト機能は、ブルートフォース攻撃からシステムを保護するための包括的なセキュリティ機能です。この機能は管理者システムとユーザー管理プラグインの両方で再利用できるように設計されています。

## 概要

ログインロックアウト機能は以下のコンポーネントで構成されています：

- **LoginLockoutTrait**: 基本的なロックアウトロジックを提供するトレイト
- **LoginLockoutHelper**: 共通のユーティリティ機能とヘルパーメソッド
- **AdminLoginLockoutService**: 管理者システム用の実装
- **UserLoginLockoutService**: ユーザー管理プラグイン用のサンプル実装

## 主な機能

### セキュリティ機能
- **ユーザーベースロックアウト**: 特定のメールアドレス/ユーザー名での連続失敗を監視
- **IPベースロックアウト**: 同一IPアドレスからの大量攻撃を防止
- **時間ベース制限**: 指定時間内の失敗回数を制限
- **自動解除**: 設定された時間経過後に自動的にロックアウトを解除

### 通知機能
- **管理者通知**: ロックアウト発生時にシステム管理者へメール通知
- **日本語・英語に対応**: エラーメッセージを日本語・英語で表示
- **詳細ログ**: 攻撃の詳細情報をログに記録

### 管理機能
- **設定可能**: 最大試行回数、時間窓、ロックアウト時間を調整可能
- **クリーンアップ**: 古い試行記録の自動削除
- **統計情報**: ロックアウト状態の詳細情報を取得

## 使用方法

### 1. 管理者システムでの使用（既存実装）

管理者システムでは`AdminLoginLockoutService`が既に実装されています。

```php
// AdminLoginController.php での使用例
$lockoutService = app(AdminLoginLockoutService::class);

// ログイン前のチェック
if ($lockoutService->isLockedOut($email)) {
    $minutes = $lockoutService->getLockoutRemainingMinutes($email);
    return back()->withErrors([
        'email' => __('auth.lockout.account_locked_out.admin', ['minutes' => $minutes])
    ]);
}

// ログイン試行の記録
if ($loginSuccessful) {
    $lockoutService->handleSuccessfulLogin($email);
} else {
    $lockoutInfo = $lockoutService->handleFailedLogin($request, $email);
    
    if ($lockoutInfo['is_locked_out']) {
        return back()->withErrors([
            'email' => $lockoutService->generateLockoutMessage($lockoutInfo)
        ]);
    }
}
```

### 2. ユーザー管理プラグインでの使用

#### 方法A: UserLoginLockoutServiceを使用（推奨）

```php
// プラグインのログインコントローラー
use App\Plugins\UsersPlugin\Services\UserLoginLockoutService;

class UserLoginController extends Controller
{
    protected $lockoutService;

    public function __construct()
    {
        // プラグイン独自の設定モデルを指定
        $this->lockoutService = new UserLoginLockoutService(
            \App\Plugins\UsersPlugin\Models\UserSetting::class
        );
    }

    public function login(Request $request)
    {
        $email = $request->email;

        // ログイン前のロックアウトチェック
        if ($this->lockoutService->isLockedOut($email)) {
            return back()->withErrors([
                'email' => $this->lockoutService->generateLockoutMessage([
                    'is_locked_out' => true,
                    'lockout_minutes' => $this->lockoutService->getLockoutRemainingMinutes($email)
                ])
            ]);
        }

        // ログイン処理
        $loginSuccessful = Auth::attempt($request->only('email', 'password'));

        // ロックアウト処理
        $lockoutInfo = $this->lockoutService->processLogin($request, $email, $loginSuccessful);
        
        if ($lockoutInfo) {
            return back()->withErrors([
                'email' => $this->lockoutService->generateLockoutMessage($lockoutInfo)
            ]);
        }

        return $loginSuccessful ? redirect()->intended() : back()->withErrors([
            'email' => __('auth.failed.user')
        ]);
    }
}
```

#### 方法B: LoginLockoutTraitを直接使用

```php
// プラグインの独自サービスクラス
use App\Traits\LoginLockoutTrait;

class CustomLoginLockoutService
{
    use LoginLockoutTrait;

    protected function getSetting(string $key, $default = null)
    {
        // プラグイン独自の設定システムを実装
        return CustomSetting::getValue($key, $default);
    }

    public function checkUserLockout(string $email): bool
    {
        $settings = [
            'enabled_key' => 'custom_lockout_enabled',
            'max_attempts_key' => 'custom_max_attempts',
            'time_window_key' => 'custom_time_window',
        ];

        return $this->isLockedOut($email, $settings);
    }
}
```

#### 方法C: LoginLockoutHelperを使用

```php
// 静的メソッドでの簡単な使用
use App\Helpers\LoginLockoutHelper;

class SimpleLoginController extends Controller
{
    public function login(Request $request)
    {
        $email = $request->email;
        
        // ログイン処理
        $loginSuccessful = Auth::attempt($request->only('email', 'password'));
        
        // 簡単な実装にはLoginLockoutHelperの静的メソッドを使用
        $lockoutInfo = LoginLockoutHelper::recordAndCheckLockout(
            $request,
            $email,
            $loginSuccessful,
            [
                'enabled_key' => 'user_lockout_enabled',
                'max_attempts_key' => 'user_max_attempts',
                // ... 他の設定キー
            ],
            \App\Plugins\UsersPlugin\Models\UserSetting::class
        );

        if (isset($lockoutInfo['is_locked_out']) && $lockoutInfo['is_locked_out']) {
            return back()->withErrors([
                'email' => LoginLockoutHelper::generateLockoutMessage($lockoutInfo, 'user')
            ]);
        }

        return $loginSuccessful ? redirect()->intended() : back()->withErrors([
            'email' => __('auth.failed.user')
        ]);
    }
}
```

## 設定

### 必要な設定項目

プラグインで使用する場合は、以下の設定項目を設定システムに追加してください：

```php
// 基本設定
'user_login_attempt_limit_enabled' => true,     // ロックアウト機能の有効/無効
'user_login_attempt_max_attempts' => 5,         // 最大試行回数
'user_login_attempt_time_window' => 15,         // 時間窓（分）
'user_login_attempt_lockout_duration' => 30,    // ロックアウト時間（分）

// 通知設定
'user_lockout_notification_enabled' => true,    // 通知機能の有効/無効
```

### 設定例（Seeder）

```php
// UserSettingsSeeder.php
class UserSettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            'user_login_attempt_limit_enabled' => '1',
            'user_login_attempt_max_attempts' => '5',
            'user_login_attempt_time_window' => '15',
            'user_login_attempt_lockout_duration' => '30',
            'user_lockout_notification_enabled' => '1',
        ];

        foreach ($settings as $key => $value) {
            UserSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
```

## 翻訳キー

### 必要な翻訳キー

プラグインで使用する場合は、以下の翻訳キーを追加してください：

```php
// lang/ja/auth.php
'lockout' => [
    'account_locked_out' => [
        'user' => 'アカウントがロックされています。:minutes分後に再試行してください。',
    ],
    'ip_locked_out' => [
        'user' => 'このIPアドレスからのアクセスが制限されています。しばらく時間をおいてから再試行してください。',
    ],
    'remaining_attempts' => [
        'user' => 'ログインに失敗しました。残り:attempts回の試行が可能です。',
    ],
],

'failed' => [
    'user' => 'ログイン情報が正しくありません。',
],
```

```php
// lang/en/auth.php
'lockout' => [
    'account_locked_out' => [
        'user' => 'Account is locked. Please try again in :minutes minutes.',
    ],
    'ip_locked_out' => [
        'user' => 'Access from this IP address is restricted. Please try again later.',
    ],
    'remaining_attempts' => [
        'user' => 'Login failed. :attempts attempts remaining.',
    ],
],

'failed' => [
    'user' => 'These credentials do not match our records.',
],
```

## メンテナンス

### クリーンアップコマンド

古いログイン試行記録を定期的にクリーンアップすることを推奨します：

```php
// プラグインのコマンドクラス
use App\Helpers\LoginLockoutHelper;

class CleanupUserLoginAttempts extends Command
{
    protected $signature = 'user:cleanup-login-attempts {--days=30}';

    public function handle()
    {
        $days = $this->option('days');
        $deleted = LoginLockoutHelper::cleanupExpiredAttempts($days);
        
        $this->info("Deleted {$deleted} expired login attempt records.");
    }
}
```

### 手動リセット

特定のユーザーのロックアウト状態を手動でリセット：

```php
use App\Helpers\LoginLockoutHelper;

// 特定ユーザーの失敗記録をクリア
$deleted = LoginLockoutHelper::clearFailedAttempts('user@example.com');

// 全ての失敗記録をクリア
$deleted = LoginLockoutHelper::clearAllFailedAttempts();
```

## 高度な使用方法

### カスタム通知

独自の通知システムを使用する場合：

```php
class CustomUserLoginLockoutService extends UserLoginLockoutService
{
    protected function sendCustomNotification(string $identifier, Request $request): void
    {
        // 独自の通知ロジックを実装
        CustomNotificationService::send([
            'type' => 'user_lockout',
            'identifier' => $identifier,
            'ip_address' => $request->ip(),
            'timestamp' => now(),
        ]);
    }

    public function handleFailedLogin(Request $request, string $identifier): array
    {
        $result = parent::handleFailedLogin($request, $identifier);
        
        if ($result['is_locked_out']) {
            $this->sendCustomNotification($identifier, $request);
        }
        
        return $result;
    }
}
```

### 複数の認証システム

異なる認証システムで異なる設定を使用：

```php
class MultiAuthLoginLockoutService
{
    public function getServiceForGuard(string $guard): LoginLockoutService
    {
        switch ($guard) {
            case 'admin':
                return app(AdminLoginLockoutService::class);
            
            case 'user':
                return new UserLoginLockoutService(
                    \App\Models\UserSetting::class
                );
            
            case 'api':
                return new UserLoginLockoutService(
                    \App\Models\ApiSetting::class
                );
            
            default:
                throw new InvalidArgumentException("Unknown guard: {$guard}");
        }
    }
}
```

## トラブルシューティング

### よくある問題

1. **設定が反映されない**
   - 設定モデルが正しく実装されているか確認
   - `getSetting()`メソッドが適切に実装されているか確認

2. **通知が送信されない**
   - `SystemNotificationService`が正しく設定されているか確認
   - メールサーバー設定が完了しているか確認

3. **ロックアウトが解除されない**
   - 時間設定が正しいか確認
   - サーバーの時刻が正確か確認

### デバッグ方法

```php
// ロックアウト状態の詳細情報を取得
$details = $lockoutService->getLockoutStatusDetails($email, $request->ip());
Log::info('Lockout status', $details);

// 現在の設定値を確認
$settings = LoginLockoutHelper::getLockoutSettings([], $settingModel);
Log::info('Lockout settings', $settings);
```

## まとめ

このログインロックアウト機能は、Dixlaseの管理者システムとユーザー管理プラグインの両方で一貫したセキュリティ機能を提供します。共通コンポーネントを使用することで、コードの重複を避け、保守性を向上させることができます。

プラグイン開発者は、`UserLoginLockoutService`をベースにして、独自の要件に合わせてカスタマイズすることができます。
