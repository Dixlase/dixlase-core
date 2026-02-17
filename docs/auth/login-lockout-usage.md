# Login Lockout Feature Usage

The Dixlase login lockout feature is a comprehensive security feature designed to protect the system from brute-force attacks. It is designed to be reusable across both the admin system and user management plugins.

## Overview

The login lockout feature consists of the following components:

- **LoginLockoutTrait**: A trait that provides basic lockout logic
- **LoginLockoutHelper**: Common utility functions and helper methods
- **AdminLoginLockoutService**: Implementation for the admin system
- **UserLoginLockoutService**: Sample implementation for the user management plugin

## Key Features

### Security Features
- **User-based lockout**: Monitors consecutive failures for a specific email/username
- **IP-based lockout**: Prevents mass attacks from the same IP address
- **Time-based limiting**: Limits the number of failures within a specified time period
- **Automatic release**: Automatically releases the lockout after a configured time period

### Notification Features
- **Admin notifications**: Email notification to system administrators when a lockout occurs
- **Multi-language support**: Error messages in Japanese and English
- **Detailed logging**: Records detailed attack information in logs

### Management Features
- **Configurable**: Maximum attempts, time window, and lockout duration can be adjusted
- **Cleanup**: Automatic deletion of old attempt records
- **Statistics**: Retrieve detailed lockout status information

## Usage

### 1. Usage in the Admin System (Existing Implementation)

The `AdminLoginLockoutService` is already implemented in the admin system.

```php
// Usage in AdminLoginController.php
$lockoutService = app(AdminLoginLockoutService::class);

// Pre-login check
if ($lockoutService->isLockedOut($email)) {
    $minutes = $lockoutService->getLockoutRemainingMinutes($email);
    return back()->withErrors([
        'email' => __('auth.lockout.account_locked_out.admin', ['minutes' => $minutes])
    ]);
}

// Record login attempt
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

### 2. Usage in a User Management Plugin

#### Method A: Using UserLoginLockoutService (Recommended)

```php
// Plugin login controller
use App\Plugins\UsersPlugin\Services\UserLoginLockoutService;

class UserLoginController extends Controller
{
    protected $lockoutService;

    public function __construct()
    {
        // Specify the plugin's own settings model
        $this->lockoutService = new UserLoginLockoutService(
            \App\Plugins\UsersPlugin\Models\UserSetting::class
        );
    }

    public function login(Request $request)
    {
        $email = $request->email;

        // Pre-login lockout check
        if ($this->lockoutService->isLockedOut($email)) {
            return back()->withErrors([
                'email' => $this->lockoutService->generateLockoutMessage([
                    'is_locked_out' => true,
                    'lockout_minutes' => $this->lockoutService->getLockoutRemainingMinutes($email)
                ])
            ]);
        }

        // Login process
        $loginSuccessful = Auth::attempt($request->only('email', 'password'));

        // Lockout handling
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

#### Method B: Using LoginLockoutTrait Directly

```php
// Plugin's custom service class
use App\Traits\LoginLockoutTrait;

class CustomLoginLockoutService
{
    use LoginLockoutTrait;

    protected function getSetting(string $key, $default = null)
    {
        // Implement the plugin's own settings system
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

#### Method C: Using LoginLockoutHelper

```php
// Simple usage with static methods
use App\Helpers\LoginLockoutHelper;

class SimpleLoginController extends Controller
{
    public function login(Request $request)
    {
        $email = $request->email;

        // Login process
        $loginSuccessful = Auth::attempt($request->only('email', 'password'));

        // Use LoginLockoutHelper static methods for a simple implementation
        $lockoutInfo = LoginLockoutHelper::recordAndCheckLockout(
            $request,
            $email,
            $loginSuccessful,
            [
                'enabled_key' => 'user_lockout_enabled',
                'max_attempts_key' => 'user_max_attempts',
                // ... other setting keys
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

## Configuration

### Required Settings

When using this feature in a plugin, add the following settings to your settings system:

```php
// Basic settings
'user_login_attempt_limit_enabled' => true,     // Enable/disable lockout feature
'user_login_attempt_max_attempts' => 5,         // Maximum number of attempts
'user_login_attempt_time_window' => 15,         // Time window (minutes)
'user_login_attempt_lockout_duration' => 30,    // Lockout duration (minutes)

// Notification settings
'user_lockout_notification_enabled' => true,    // Enable/disable notifications
```

### Configuration Example (Seeder)

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

## Translation Keys

### Required Translation Keys

When using this feature in a plugin, add the following translation keys:

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

## Maintenance

### Cleanup Command

It is recommended to periodically clean up old login attempt records:

```php
// Plugin command class
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

### Manual Reset

Manually reset the lockout status for a specific user:

```php
use App\Helpers\LoginLockoutHelper;

// Clear failed attempt records for a specific user
$deleted = LoginLockoutHelper::clearFailedAttempts('user@example.com');

// Clear all failed attempt records
$deleted = LoginLockoutHelper::clearAllFailedAttempts();
```

## Advanced Usage

### Custom Notifications

When using a custom notification system:

```php
class CustomUserLoginLockoutService extends UserLoginLockoutService
{
    protected function sendCustomNotification(string $identifier, Request $request): void
    {
        // Implement custom notification logic
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

### Multiple Authentication Systems

Using different settings for different authentication systems:

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

## Troubleshooting

### Common Issues

1. **Settings not taking effect**
   - Verify that the settings model is correctly implemented
   - Verify that the `getSetting()` method is properly implemented

2. **Notifications not being sent**
   - Verify that `SystemNotificationService` is correctly configured
   - Verify that the mail server settings are complete

3. **Lockout not being released**
   - Verify that the time settings are correct
   - Verify that the server clock is accurate

### Debugging

```php
// Retrieve detailed lockout status information
$details = $lockoutService->getLockoutStatusDetails($email, $request->ip());
Log::info('Lockout status', $details);

// Check current settings values
$settings = LoginLockoutHelper::getLockoutSettings([], $settingModel);
Log::info('Lockout settings', $settings);
```

## Summary

This login lockout feature provides consistent security functionality across both the Dixlase admin system and user management plugins. By using shared components, it avoids code duplication and improves maintainability.

Plugin developers can use `UserLoginLockoutService` as a base and customize it to meet their specific requirements.
