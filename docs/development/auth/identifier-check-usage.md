# Login Identifier Check Feature Usage

The login identifier check feature is a shared utility that verifies the existence of an email address or account name.
It can be used for both admin login and user login.

## Overview

`IdentifierCheckHelper` provides the following features:

- **Rate limiting**: IP + identifier-based rate limiting
- **Timing attack protection**: Always waits a consistent duration (100-300ms)
- **Audit logging**: Records both successes and failures
- **Unified error messages**: Countermeasure against user enumeration attacks
- **Passkey registration status check**: Checks for the presence of WebAuthn credentials

## Basic Usage

### 1. Usage in Admin Login

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

        // Retrieve lockout settings
        $settings = IdentifierCheckHelper::getLockoutSettings(MemberSetting::class);

        // Execute identifier check (with rate limiting)
        $result = IdentifierCheckHelper::checkWithRateLimit(
            $login,
            $ipAddress,
            Member::class,
            $settings,
            'admin' // Context
        );

        return response()->json([
            'exists' => $result['exists'],
            'has_passkey' => $result['has_passkey'],
        ]);
    }
}
```

### 2. Usage in a User Management Plugin

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

        // Use the plugin's settings model and user model
        $settings = IdentifierCheckHelper::getLockoutSettings(
            \Plugins\DixlaseUsers\App\Models\UserSetting::class
        );

        $result = IdentifierCheckHelper::checkWithRateLimit(
            $login,
            $ipAddress,
            \Plugins\DixlaseUsers\App\Models\User::class,
            $settings,
            'user' // Set context to 'user'
        );

        return response()->json([
            'exists' => $result['exists'],
            'has_passkey' => $result['has_passkey'],
        ]);
    }
}
```

### 3. Implementation Using a Service Class (Within a Plugin)

For more complex logic, you can create a dedicated service class within your plugin:

**File location**: `plugins/DixlaseUsers/app/Services/UserLoginIdentifierCheckService.php`

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
        // Retrieve lockout settings
        $settings = IdentifierCheckHelper::getLockoutSettings($settingModelClass);

        // Execute identifier check (with rate limiting)
        return IdentifierCheckHelper::checkWithRateLimit(
            $login,
            $ipAddress,
            $userModelClass,
            $settings,
            'user' // Set context to 'user'
        );
    }
}
```

**Usage in a controller**:

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

## Configuration Requirements

### Settings Model Requirements

To use `getLockoutSettings()`, the settings model must have the following keys:

```php
// Setting keys
'login_attempt_limit_enabled' => true/false,  // Enable/disable rate limiting
'login_attempt_max_attempts' => 5,            // Maximum number of attempts
'login_attempt_time_window' => 15,            // Time window (minutes)
```

The settings model must have a `getValue()` method:

```php
public static function getValue(string $key, $default = null)
{
    // Logic to retrieve the setting value
}
```

### User Model Requirements

The user model must have the following columns:

- `email`: Email address
- `account_name`: Account name (optional)

For passkey verification, one of the following relationships is required:

- `webauthnCredentials()`: WebAuthn credentials (recommended)
- `twoFaPasskeys()`: Two-factor authentication passkeys
- `passkeys()`: General-purpose passkeys

## Return Values

`checkWithRateLimit()` returns the following array:

```php
[
    'exists' => true,           // Whether the user exists
    'has_passkey' => false,     // Whether a passkey is registered
    'user' => User|null,        // The user model (if exists)
]
```

## Exception Handling

### ValidationException

A `ValidationException` is thrown when the rate limit is exceeded or the user does not exist:

```php
try {
    $result = IdentifierCheckHelper::checkWithRateLimit(...);
} catch (\Illuminate\Validation\ValidationException $e) {
    // Get error messages
    $errors = $e->errors();
    // 'login' => 'The email address or password is incorrect.'
    // or
    // 'login' => 'Too many login attempts. Please try again in 60 seconds.'
}
```

## Security Features

### 1. Rate Limiting

- Rate limited by IP address + identifier hash
- Configurable maximum attempts and time window
- Audit logging on lockout

### 2. Timing Attack Protection

- A consistent delay of 100-300ms regardless of whether the user exists or not
- Prevents inference of user existence from response time

### 3. User Enumeration Attack Protection

- Unified error messages
- Never returns specific messages such as "This email address is not registered"

### 4. Audit Logging

On success:
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

On failure:
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

## Customization

### Using Custom Lockout Settings

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

### Customizing Passkey Check Logic

If you need custom passkey verification logic, you can extend `IdentifierCheckHelper`:

```php
class CustomIdentifierCheckHelper extends IdentifierCheckHelper
{
    protected static function hasPasskey($user): bool
    {
        // Custom passkey verification logic
        return $user->customPasskeys()->active()->count() > 0;
    }
}
```

## Troubleshooting

### Rate Limiting Not Working

1. Verify that the settings model's `getValue()` method is correctly implemented
2. Verify that `login_attempt_limit_enabled` is set to `true`
3. Verify that Redis or the cache driver is correctly configured

### Passkey Registration Status Not Retrieved Correctly

1. Verify that the appropriate relationship is defined on the user model
2. Verify that the relationship name is one of `webauthnCredentials`, `twoFaPasskeys`, or `passkeys`

### Timing Attack Protection Delay Is Too Long

The delay is a random value between 100-300ms. This cannot be changed, but it is an important security feature.

## Related Documentation

- [Login Lockout Feature](./login-lockout-usage.md)
- [Two-Factor Authentication Feature](./two-factor-authentication-usage.md)
- [Audit Log Feature](./audit-log-usage.md)
