# Login Notification System

## Overview

The Dixlase login notification system provides a unified architecture for both the admin panel and the My Page, achieving a balance between security and usability appropriate to each context.

## Architecture

### Design Philosophy

- **Context-aware**: Applies different security policies for the admin panel and My Page
- **Plugin extensibility**: Plugins can easily implement their own login notifications
- **Configuration flexibility**: Priority control between global settings and profile settings
- **Multi-language support**: Translation keys are separated between core and plugins

### Component Structure

```
+-------------------------------------------------------------+
|                    Login Notification System                 |
+-------------------------------------------------------------+
|                                                             |
|  +------------------+         +------------------+         |
|  |   Core           |         |  Plugin          |         |
|  +------------------+         +------------------+         |
|  | LoginNotification|<--------|UserLoginNotif... |         |
|  |  (Base class)    |         |  (Extends)       |         |
|  +------------------+         +------------------+         |
|           ^                            ^                   |
|           |                            |                   |
|  +--------+--------+         +--------+--------+          |
|  |AdminLoginNotif..|         |                 |          |
|  |  (Admin panel)   |         |                 |          |
|  +------------------+         +-----------------+          |
|                                                             |
|  +----------------------------------------------+          |
|  |        LoginNotificationTrait                |          |
|  |  (Shared logic: notification decision,       |          |
|  |   device detection)                          |          |
|  +----------------------------------------------+          |
|           ^                            ^                   |
|           |                            |                   |
|  +--------+--------+         +--------+--------+          |
|  |AdminLoginNotif..|         |UserLoginNotif... |          |
|  |   Service       |         |   Service        |          |
|  +------------------+         +------------------+          |
|                                                             |
+-------------------------------------------------------------+
```

## Class Structure

### 1. LoginNotification (Base Class)

**Role**: Provides login notification logic shared between the admin panel and My Page

**Key methods**:
- `buildMailMessage()`: Constructs the email body
- `getContextKey()`: Gets the context translation key (for admin panel)

**Characteristics**:
- Button/URL hidden (security-first)
- Supports both system notifications and user notifications

### 2. AdminLoginNotification

**Role**: Login notification specific to the admin panel

**Implementation**:
```php
class AdminLoginNotification extends LoginNotification
{
    public function __construct(array $loginDetails, bool $isSystemNotification = false)
    {
        parent::__construct($loginDetails, $isSystemNotification, 'admin');
    }
}
```

### 3. UserLoginNotification (Plugin)

**Role**: Login notification specific to My Page

**Implementation**:
```php
class UserLoginNotification extends LoginNotification
{
    protected function buildMailMessage($notifiable)
    {
        $message = parent::buildMailMessage($notifiable);

        // My Page specific: Add site information
        $message->line('**' . __('users-plugin::mail.login_notification.site_name') . '** ' . config('app.name'));
        $message->line('**' . __('users-plugin::mail.login_notification.site_url') . '** ' . config('app.url'));

        // My Page specific: Add action button
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

**Characteristics**:
- Site information display (site name and URL)
- Action button display
- Subcopy display (URL for when the button cannot be clicked)

### 4. LoginNotificationTrait

**Role**: Provides shared logic for login notification services

**Abstract methods**:
```php
abstract protected function getGlobalSettingKey(): string;
abstract protected function getSettingGetter(): callable;
abstract protected function getNotificationClass(): string;
abstract protected function getLogContext(): string;
```

**Implementation example**:
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

## Notification Modes

### AuthenticationMode Enum

| Value | Name | Description |
|-------|------|-------------|
| 0 | Disabled | Notifications disabled |
| 1 | DifferentDevice | Notify only on login from a different device |
| 2 | Always | Always notify |
| 3 | UseProfileSetting | Follow profile settings |

### Settings Priority

#### Admin Panel (Members)

| Global Setting | Profile Setting | Result |
|----------------|-----------------|--------|
| 0: Disabled | Any | No notification |
| 1: Different device | Any | Notify on new device only |
| 2: Always enabled | Any | Always notify |
| 3: Follow profile | 0 | No notification |
| 3: Follow profile | 1 | Notify on new device only |
| 3: Follow profile | 2 | Always notify |

#### My Page (Users)

The same priority rules apply.

## Security Policies

### Admin Panel

**Policy**: Security-first

- No action button
- No site information
- No subcopy
- Login details only

**Reason**: The admin panel URL is not public information, so it should not be included in emails

### My Page

**Policy**: Balance between usability and security

- Action button displayed
- Site information displayed
- Subcopy displayed
- Login details displayed

**Reason**: My Page is public information, so user convenience is prioritized

## Translation Key Separation

### Core (lang/ja/mail.php, lang/en/mail.php)

Translation keys for the admin panel:
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

### Plugin (plugins/DixlaseUsers/lang/ja/mail.php, lang/en/mail.php)

Translation keys for My Page:
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

## Usage

### 1. Implementation in a Controller

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

### 2. Notification After 2FA Authentication

`TwoFaAuthenticationTrait` automatically sends login notifications:

```php
// After successful 2FA authentication
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

### 3. Implementation in a Plugin

#### Step 1: Create a Notification Class

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

#### Step 2: Create a Service Class

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

#### Step 3: Create Translation Files

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

## Email Template Customization

### Subcopy Localization

In `resources/views/vendor/mail/html/subcopy.blade.php`, the context is determined from the URL:

```php
// Determine context from URL (use plugin translation key for My Page)
$translationKey = 'mail.login_notification.action_subcopy';
if (str_contains($url, '/mypage/')) {
    $translationKey = 'users-plugin::mail.login_notification.action_subcopy';
}

echo __($translationKey, ['button_text' => $buttonText]);
```

## Troubleshooting

### Notifications Are Not Being Sent

1. **Check mail server settings**
   - General Settings -> Mail Server Settings
   - Run a mail test

2. **Check global settings**
   - Admin panel: Members -> Settings -> Authentication Settings
   - My Page: Users -> Settings -> Authentication Settings

3. **Check logs**
   ```bash
   tail -f storage/logs/laravel.log | grep "login notification"
   ```

### Translation Keys Are Displayed Instead of Text

1. **Verify translation file existence**
   ```bash
   ls -la lang/ja/mail.php
   ls -la plugins/DixlaseUsers/lang/ja/mail.php
   ```

2. **Clear cache**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

### Profile Settings Not Taking Effect

1. **Check global settings**
   - Verify that the global setting is set to "Follow profile"

2. **Check the database**
   ```sql
   SELECT login_notification_mode FROM members WHERE id = ?;
   SELECT login_notification_mode FROM dixlase_users_users WHERE id = ?;
   ```

## Best Practices

1. **Security**: Do not include admin panel URLs in emails
2. **Usability**: Include public page URLs in emails to improve user convenience
3. **Extensibility**: Plugins should implement their own notification and service classes
4. **Multi-language support**: Plugins should have their own translation files
5. **Testing**: Always run a test send after configuring mail server settings

## References

- [LoginNotification.php](../../../app/Notifications/LoginNotification.php)
- [LoginNotificationTrait.php](../../../app/Traits/LoginNotificationTrait.php)
- [UserLoginNotification.php](../../../plugins/DixlaseUsers/app/Notifications/UserLoginNotification.php)
- [TwoFaAuthenticationTrait.php](../../../app/Traits/TwoFa/TwoFaAuthenticationTrait.php)
