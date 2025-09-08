# ログイン通知トレイト使用ガイド

## 概要

`HandlesLoginNotifications` トレイトは、ログイン通知機能の共通ロジックを提供し、コアシステムとプラグインの両方で使用できるように設計されています。

## 基本的な使用方法

### 1. トレイトのインポート

```php
use App\Traits\HandlesLoginNotifications;

class YourLoginNotificationService
{
    use HandlesLoginNotifications;
    
    // サービスの実装
}
```

### 2. 基本的な実装例

```php
<?php

namespace YourPlugin\Services;

use App\Traits\HandlesLoginNotifications;
use YourPlugin\Models\User;
use YourPlugin\Notifications\LoginNotification;
use Illuminate\Http\Request;

class UserLoginNotificationService
{
    use HandlesLoginNotifications;

    public function handle(User $user, Request $request): void
    {
        // 1. ログイン情報を記録
        $this->recordLoginInfo($user, $request);

        // 2. ログイン詳細データを準備
        $loginDetails = $this->prepareLoginDetails($request);

        // 3. メール送信可能性をチェック
        if (!$this->canSendNotification($user, 'User login notification')) {
            return;
        }

        // 4. ユーザー通知を送信
        if ($this->shouldSendUserNotification(
            $user, 
            $request->ip(), 
            $request->userAgent(),
            fn() => $this->getGlobalNotificationSetting()
        )) {
            $user->notify(new LoginNotification($loginDetails, false));
        }

        // 5. システム通知を送信（オプション）
        $this->sendSystemNotification(
            $loginDetails,
            fn() => $this->getSystemEmail(),
            fn() => $this->isSystemNotificationEnabled(),
            LoginNotification::class
        );
    }

    private function getGlobalNotificationSetting(): string
    {
        // プラグイン固有の設定取得ロジック
        return YourPluginSetting::getValue('login_notification_mode', '0');
    }

    private function getSystemEmail(): ?string
    {
        return YourPluginSetting::getValue('system_notification_email');
    }

    private function isSystemNotificationEnabled(): bool
    {
        return YourPluginSetting::getValue('send_system_notifications') === '1';
    }
}
```

## 提供されるメソッド

### ログイン情報記録

#### `recordLoginInfo(Model $user, Request $request): void`
ユーザーのログイン情報（IP、User-Agent、ログイン時刻）をデータベースに記録します。

**前提条件:**
- ユーザーモデルに以下のフィールドが必要:
  - `last_login_ip` (string)
  - `last_login_ua` (string) 
  - `last_login_at` (timestamp)

### データ準備

#### `prepareLoginDetails(Request $request): array`
通知に使用するログイン詳細データを準備します。

**戻り値:**
```php
[
    'datetime' => '2025-01-15 12:34:56',
    'ip' => '192.168.1.1',
    'user_agent' => 'Mozilla/5.0...'
]
```

### 通知送信判定

#### `canSendNotification(Model $user, string $context): bool`
メールサーバーの設定状況を確認し、通知送信が可能かを判定します。

#### `shouldSendUserNotification(Model $user, string $ip, string $ua, callable $getGlobalSetting): bool`
グローバル設定とユーザー設定に基づいて、ユーザー通知を送信すべきかを判定します。

**パラメータ:**
- `$getGlobalSetting`: グローバル設定値を返すコールバック関数

### システム通知

#### `sendSystemNotification(array $loginDetails, callable $getSystemEmail, callable $isSystemNotificationEnabled, string $notificationClass): void`
システム管理者への通知を送信します。

**パラメータ:**
- `$getSystemEmail`: システムメールアドレスを返すコールバック関数
- `$isSystemNotificationEnabled`: システム通知が有効かを返すコールバック関数
- `$notificationClass`: 使用する通知クラス名

## 通知モードの対応

トレイトは以下の通知モードに対応しています（`LoginNotificationMode` enum）:

- `Disabled` (0): 通知無効
- `Always` (1): 常に通知
- `OnlyNewDevice` (2): 新しいデバイス/IPのみ通知
- `UseProfileSetting` (3): プロフィール設定を使用

## プラグインでの実装要件

### 1. ユーザーモデル要件

```php
class User extends Model
{
    protected $fillable = [
        // 他のフィールド...
        'last_login_ip',
        'last_login_ua', 
        'last_login_at',
        'login_notification_mode', // プロフィール設定用
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'login_notification_mode' => 'integer',
    ];
}
```

### 2. 通知クラス要件

```php
class LoginNotification extends Notification
{
    public function __construct(
        public array $loginDetails,
        public bool $isSystemNotification = false
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // 通知メールの実装
    }
}
```

### 3. 設定管理

プラグインは以下の設定を管理する必要があります:

- `login_notification_mode`: グローバル通知モード
- `send_system_notifications`: システム通知有効/無効
- `system_notification_email`: システム通知先メールアドレス

## 使用例: フロントエンドユーザーログイン

```php
<?php

namespace YourPlugin\Http\Controllers;

use App\Traits\HandlesLoginNotifications;
use YourPlugin\Models\FrontUser;
use YourPlugin\Notifications\FrontUserLoginNotification;
use Illuminate\Http\Request;

class FrontUserLoginController extends Controller
{
    use HandlesLoginNotifications;

    public function store(Request $request)
    {
        // 認証処理...
        $user = $this->authenticate($request);
        
        if ($user) {
            // ログイン通知処理
            $this->handleLoginNotification($user, $request);
            
            // ログイン処理完了
            auth('front')->login($user);
            return redirect()->intended('/dashboard');
        }
        
        return back()->withErrors(['email' => 'Invalid credentials']);
    }

    private function handleLoginNotification(FrontUser $user, Request $request): void
    {
        $this->recordLoginInfo($user, $request);
        $loginDetails = $this->prepareLoginDetails($request);

        if (!$this->canSendNotification($user, 'Front user login')) {
            return;
        }

        if ($this->shouldSendUserNotification(
            $user,
            $request->ip(),
            $request->userAgent(),
            fn() => config('front_user.login_notification_mode', '0')
        )) {
            $user->notify(new FrontUserLoginNotification($loginDetails, false));
        }
    }
}
```

## 注意事項

1. **メールサーバー設定**: トレイトは `MailServerValidatorService` を使用してメール送信可能性をチェックします
2. **ログ出力**: 通知がスキップされた場合、適切なログが出力されます
3. **エラーハンドリング**: 通知送信エラーは自動的にログに記録されます
4. **パフォーマンス**: 大量のログインがある場合は、キューを使用することを推奨します

## トラブルシューティング

### よくある問題

1. **通知が送信されない**
   - メールサーバー設定を確認
   - `MailServerValidatorService::canSendMail()` の結果を確認
   - ログファイルでエラーメッセージを確認

2. **ユーザーモデルのフィールドエラー**
   - 必要なフィールド（`last_login_ip`, `last_login_ua`, `last_login_at`）がテーブルに存在するか確認
   - モデルの `$fillable` に含まれているか確認

3. **通知クラスのエラー**
   - 通知クラスが正しく実装されているか確認
   - コンストラクタの引数が正しいか確認
