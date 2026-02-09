# ログイン通知システム

## 概要

Dixlaseのログイン通知システムは、管理画面とマイページの両方で統一されたアーキテクチャを提供し、コンテキストに応じたセキュリティとユーザビリティのバランスを実現します。

## アーキテクチャ

### 設計思想

- **コンテキスト対応**: 管理画面とマイページで異なるセキュリティポリシーを適用
- **プラグイン拡張性**: プラグインが独自のログイン通知を簡単に実装可能
- **設定の柔軟性**: グローバル設定とプロフィール設定の優先順位制御
- **多言語対応**: コアとプラグインで翻訳キーを分離

### コンポーネント構成

```
┌─────────────────────────────────────────────────────────────┐
│                    ログイン通知システム                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────────┐         ┌──────────────────┐         │
│  │   コア (Core)    │         │  プラグイン      │         │
│  ├──────────────────┤         ├──────────────────┤         │
│  │ LoginNotification│◄────────│UserLoginNotif... │         │
│  │  (基底クラス)     │         │  (継承)          │         │
│  └──────────────────┘         └──────────────────┘         │
│           ▲                            ▲                   │
│           │                            │                   │
│  ┌────────┴────────┐         ┌────────┴────────┐          │
│  │AdminLoginNotif..│         │                 │          │
│  │  (管理画面)      │         │                 │          │
│  └─────────────────┘         └─────────────────┘          │
│                                                             │
│  ┌──────────────────────────────────────────────┐          │
│  │        LoginNotificationTrait                │          │
│  │  (共通ロジック: 通知判定、デバイス検出)        │          │
│  └──────────────────────────────────────────────┘          │
│           ▲                            ▲                   │
│           │                            │                   │
│  ┌────────┴────────┐         ┌────────┴────────┐          │
│  │AdminLoginNotif..│         │UserLoginNotif... │          │
│  │   Service       │         │   Service        │          │
│  └─────────────────┘         └──────────────────┘          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## クラス構成

### 1. LoginNotification (基底クラス)

**役割**: 管理画面とマイページで共通のログイン通知ロジックを提供

**主要メソッド**:
- `buildMailMessage()`: メール本文を構築
- `getContextKey()`: コンテキスト翻訳キーを取得（管理画面用）

**特徴**:
- ボタン・URL非表示（セキュリティ優先）
- システム通知とユーザー通知の両方に対応

### 2. AdminLoginNotification

**役割**: 管理画面専用のログイン通知

**実装**:
```php
class AdminLoginNotification extends LoginNotification
{
    public function __construct(array $loginDetails, bool $isSystemNotification = false)
    {
        parent::__construct($loginDetails, $isSystemNotification, 'admin');
    }
}
```

### 3. UserLoginNotification (プラグイン)

**役割**: マイページ専用のログイン通知

**実装**:
```php
class UserLoginNotification extends LoginNotification
{
    protected function buildMailMessage($notifiable)
    {
        $message = parent::buildMailMessage($notifiable);
        
        // マイページ固有: サイト情報を追加
        $message->line('**' . __('users-plugin::mail.login_notification.site_name') . '** ' . config('app.name'));
        $message->line('**' . __('users-plugin::mail.login_notification.site_url') . '** ' . config('app.url'));
        
        // マイページ固有: アクションボタンを追加
        if (!$this->isSystemNotification) {
            $actionUrl = $this->getActionUrl();
            if ($actionUrl) {
                $buttonText = __('users-plugin::mail.login_notification.action_button', ['context' => __($contextKey)]);
                $message->action($buttonText, $actionUrl);
            }
        }
        
        return $message;
    }
    
    protected function getContextKey(): string
    {
        return 'users-plugin::mail.login_notification.context.mypage';
    }
    
    protected function getActionUrl(): ?string
    {
        return route('users-plugin::mypage.dashboard');
    }
}
```

**特徴**:
- サイト情報表示（サイト名・URL）
- アクションボタン表示
- subcopy表示（ボタンクリックできない場合のURL）

### 4. LoginNotificationTrait

**役割**: ログイン通知サービスの共通ロジックを提供

**抽象メソッド**:
```php
abstract protected function getGlobalSettingKey(): string;
abstract protected function getSettingGetter(): callable;
abstract protected function getNotificationClass(): string;
abstract protected function getLogContext(): string;
```

**実装例**:
```php
class AdminLoginNotificationService
{
    use LoginNotificationTrait;
    
    protected function getGlobalSettingKey(): string
    {
        return 'login_notification_mode';
    }
    
    protected function getSettingGetter(): callable
    {
        return fn() => MemberSetting::getValue($this->getGlobalSettingKey(), '0');
    }
    
    protected function getNotificationClass(): string
    {
        return AdminLoginNotification::class;
    }
    
    protected function getLogContext(): string
    {
        return 'Admin login notification';
    }
}
```

## 通知モード

### AuthenticationMode Enum

| 値 | 名前 | 説明 |
|---|------|------|
| 0 | Disabled | 通知無効 |
| 1 | DifferentDevice | 異なるデバイスからのログイン時のみ通知 |
| 2 | Always | 常に通知 |
| 3 | UseProfileSetting | プロフィール設定に従う |

### 設定の優先順位

#### 管理画面（メンバー）

| グローバル設定 | プロフィール設定 | 結果 |
|---------------|----------------|------|
| 0: 無効 | 任意 | 通知なし |
| 1: 異なるデバイス | 任意 | 新デバイスのみ通知 |
| 2: 常に有効 | 任意 | 常に通知 |
| 3: プロフィールに従う | 0 | 通知なし |
| 3: プロフィールに従う | 1 | 新デバイスのみ通知 |
| 3: プロフィールに従う | 2 | 常に通知 |

#### マイページ（ユーザー）

同様の優先順位ルールが適用されます。

## セキュリティポリシー

### 管理画面

**方針**: セキュリティ最優先

- ❌ アクションボタン非表示
- ❌ サイト情報非表示
- ❌ subcopy非表示
- ✅ ログイン詳細のみ表示

**理由**: 管理画面のURLは公開情報ではないため、メールに含めない

### マイページ

**方針**: 利便性とセキュリティのバランス

- ✅ アクションボタン表示
- ✅ サイト情報表示
- ✅ subcopy表示
- ✅ ログイン詳細表示

**理由**: マイページは公開情報であり、ユーザーの利便性を優先

## 翻訳キーの分離

### コア (lang/ja/mail.php, lang/en/mail.php)

管理画面用の翻訳キー:
```php
'login_notification' => [
    'subject_user' => '【ログイン通知】:nameさん、:contextにログインがありました',
    'subject_system' => '【システム通知】:contextへのログインがありました',
    'user_message' => ':nameさん、:contextにログインがありました。',
    'system_message' => 'システム通知',
    'details_title' => 'ログイン詳細:',
    'datetime' => '日時:',
    'ip_address' => 'IPアドレス:',
    'user_agent' => 'User-Agent:',
    'user_id' => 'ユーザーID:',
    'security_notice' => 'もしこのログインに心当たりがない場合は、すぐにパスワードを変更してください。',
    'regards' => 'よろしくお願いいたします。',
    'context' => [
        'admin' => '管理画面',
    ],
],
```

### プラグイン (plugins/DixlaseUsers/lang/ja/mail.php, lang/en/mail.php)

マイページ用の翻訳キー:
```php
'login_notification' => [
    'site_name' => 'サイト名:',
    'site_url' => 'サイトURL:',
    'action_button' => ':contextへアクセス',
    'action_subcopy' => '":button_text" ボタンをクリックできない場合は、以下のURLをコピーしてWebブラウザに貼り付けてください:',
    'context' => [
        'mypage' => 'マイページ',
    ],
],
```

## 使用方法

### 1. コントローラーでの実装

```php
class DixlaseUsersMypageLoginController extends DixlaseUsersMypageController
{
    use \App\Traits\LoginTrait;
    
    protected function getLoginNotificationServiceClass(): string
    {
        return \Plugins\DixlaseUsers\App\Services\UserLoginNotificationService::class;
    }
}
```

### 2. 2FA認証後の通知

`TwoFaAuthenticationTrait`が自動的にログイン通知を送信します:

```php
// 2FA認証成功後
if (method_exists($this, 'getLoginNotificationServiceClass')) {
    try {
        app($this->getLoginNotificationServiceClass())->handle($user, $request);
    } catch (\Exception $e) {
        Log::error('[2FA] Login notification failed', [
            'user_id' => $user->id,
            'error' => $e->getMessage()
        ]);
    }
}
```

### 3. プラグインでの実装

#### ステップ1: 通知クラスを作成

```php
namespace YourPlugin\App\Notifications;

use App\Notifications\LoginNotification;

class YourLoginNotification extends LoginNotification
{
    public function __construct(array $loginDetails, bool $isSystemNotification = false)
    {
        parent::__construct($loginDetails, $isSystemNotification, 'your_context');
    }
    
    protected function getContextKey(): string
    {
        return 'your-plugin::mail.login_notification.context.your_context';
    }
    
    protected function getActionUrl(): ?string
    {
        return route('your-plugin::dashboard');
    }
}
```

#### ステップ2: サービスクラスを作成

```php
namespace YourPlugin\App\Services;

use App\Traits\LoginNotificationTrait;
use YourPlugin\App\Models\YourSetting;
use YourPlugin\App\Notifications\YourLoginNotification;

class YourLoginNotificationService
{
    use LoginNotificationTrait;
    
    protected function getGlobalSettingKey(): string
    {
        return 'login_notification_mode';
    }
    
    protected function getSettingGetter(): callable
    {
        return fn() => YourSetting::getValue($this->getGlobalSettingKey(), '0');
    }
    
    protected function getNotificationClass(): string
    {
        return YourLoginNotification::class;
    }
    
    protected function getLogContext(): string
    {
        return 'Your login notification';
    }
}
```

#### ステップ3: 翻訳ファイルを作成

```php
// plugins/YourPlugin/lang/ja/mail.php
return [
    'login_notification' => [
        'context' => [
            'your_context' => 'あなたのコンテキスト',
        ],
    ],
];
```

## メールテンプレートのカスタマイズ

### subcopyの多言語化

`resources/views/vendor/mail/html/subcopy.blade.php`でURLからコンテキストを判断:

```php
// URLからコンテキストを判断（マイページの場合はプラグインの翻訳キーを使用）
$translationKey = 'mail.login_notification.action_subcopy';
if (str_contains($url, '/mypage/')) {
    $translationKey = 'users-plugin::mail.login_notification.action_subcopy';
}

echo __($translationKey, ['button_text' => $buttonText]);
```

## トラブルシューティング

### 通知が送信されない

1. **メールサーバー設定を確認**
   - 基本設定 → メールサーバー設定
   - メールテストを実行

2. **グローバル設定を確認**
   - 管理画面: メンバー → 設定 → 認証設定
   - マイページ: ユーザー → 設定 → 認証設定

3. **ログを確認**
   ```bash
   tail -f storage/logs/laravel.log | grep "login notification"
   ```

### 翻訳キーが表示される

1. **翻訳ファイルの存在確認**
   ```bash
   ls -la lang/ja/mail.php
   ls -la plugins/DixlaseUsers/lang/ja/mail.php
   ```

2. **キャッシュクリア**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

### プロフィール設定が反映されない

1. **グローバル設定を確認**
   - グローバル設定が「プロフィールに従う」になっているか確認

2. **データベースを確認**
   ```sql
   SELECT login_notification_mode FROM members WHERE id = ?;
   SELECT login_notification_mode FROM dixlase_users_users WHERE id = ?;
   ```

## ベストプラクティス

1. **セキュリティ**: 管理画面のURLはメールに含めない
2. **ユーザビリティ**: 公開ページのURLはメールに含めてユーザーの利便性を向上
3. **拡張性**: プラグインは独自の通知クラスとサービスクラスを実装
4. **多言語対応**: プラグインは独自の翻訳ファイルを持つ
5. **テスト**: メールサーバー設定後、必ずテスト送信を実行

## 参考資料

- [LoginNotification.php](../app/Notifications/LoginNotification.php)
- [LoginNotificationTrait.php](../app/Traits/LoginNotificationTrait.php)
- [UserLoginNotification.php](../plugins/DixlaseUsers/app/Notifications/UserLoginNotification.php)
- [TwoFaAuthenticationTrait.php](../app/Traits/TwoFa/TwoFaAuthenticationTrait.php)
