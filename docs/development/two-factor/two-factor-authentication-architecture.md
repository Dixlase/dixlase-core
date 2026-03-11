# Two-Factor Authentication (2FA) Architecture Documentation

## Overview

Dixlase's two-factor authentication system is composed of multiple services, traits, and helpers, each with clearly defined responsibilities. This document explains the role and usage of each component.

---

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                      Controller Layer                        │
│  (AdminProfileController, LoginController, etc.)            │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ↓
┌─────────────────────────────────────────────────────────────┐
│                  TwoFaAuthenticationTrait                    │
│            (Common 2FA authentication flow)                  │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ↓
┌─────────────────────────────────────────────────────────────┐
│                      TwoFaService                            │
│              (Service orchestration layer)                    │
└─────┬───────┬───────┬───────┬───────┬─────────────────────┘
      │       │       │       │       │
      ↓       ↓       ↓       ↓       ↓
┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
│TwoFaCode │ │TwoFaPass │ │TwoFaReco │ │TwoFaAtte │ │TwoFaHelp │
│Service   │ │keyService│ │veryCode  │ │mptService│ │er        │
│          │ │          │ │Service   │ │          │ │          │
└──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘
     │             │             │             │             │
     └─────────────┴─────────────┴─────────────┴─────────────┘
                              │
                              ↓
                    ┌──────────────────┐
                    │TwoFaUtilityTrait │
                    │ (Common utilities)│
                    └──────────────────┘
```

---

## Component List

### 1. **TwoFaCodeService** (Code Generation & Verification)

**Responsibilities:**
- Generating email verification codes
- Storing codes in the database
- Sending emails
- Verifying codes
- Cleaning up expired codes

**Key Methods:**
```php
// Generate code (DB storage only)
public function generate($user, int $expireMinutes = null): string

// Generate code + send email
public function generateAndSend($user, string $mailClass, int $expireMinutes = null, string $context = 'admin'): string

// Verify code
public function validate($user, string $inputCode): bool

// Check if a valid code exists
public function hasValidCode($user): bool

// Get remaining valid time
public function getRemainingTime($user): ?int

// Revoke all codes
public function revokeAll($user): int

// Clean up expired codes
public function cleanupExpired(): int
```

**Usage Example:**
```php
$codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);

// Generate code + send email
$code = $codeService->generateAndSend(
    $user,
    \App\Mail\TwoFaCodeMail::class,
    10, // Valid for 10 minutes
    'admin'
);

// Verify code
if ($codeService->validate($user, $inputCode)) {
    // Authentication successful
}
```

**Features:**
- Email configuration check (using TwoFaHelper)
- Generic mail class support
- Context-specific email delivery (admin, user, etc.)

---

### 2. **TwoFaRecoveryCodeService** (Recovery Code Management)

**Responsibilities:**
- Generating recovery codes
- Verifying recovery codes
- Managing used codes
- Checking regeneration limits

**Key Methods:**
```php
// Generate recovery codes (5 codes)
public function generate(TwoFaInterface $user): array

// Verify recovery code
public function validate(TwoFaInterface $user, string $code): bool

// Get remaining valid recovery code count
public function getRemainingCount(TwoFaInterface $user): int

// Check if regeneration is allowed (24-hour limit)
public function canRegenerate(TwoFaInterface $user): bool

// Get the next available regeneration time
public function getNextRegenerateTime(TwoFaInterface $user): ?Carbon

// Check if recovery codes exist
public function hasRecoveryCodes(TwoFaInterface $user): bool

// Format recovery code (for display)
public function formatCode(string $code): string

// Revoke all recovery codes
public function revokeAll(TwoFaInterface $user): int
```

**Usage Example:**
```php
$recoveryCodeService = app(\App\Services\TwoFa\TwoFaRecoveryCodeService::class);

// Generate recovery codes
$codes = $recoveryCodeService->generate($user);
// e.g.: ['1234567890123456789', '9876543210987654321', ...]

// Display formatted codes
foreach ($codes as $code) {
    echo $recoveryCodeService->formatCode($code);
    // Output: 12345-67890-12345-67890
}

// Verify recovery code
if ($recoveryCodeService->validate($user, $inputCode)) {
    // Authentication successful (code marked as used)
}

// Check if regeneration is allowed
if ($recoveryCodeService->canRegenerate($user)) {
    $newCodes = $recoveryCodeService->generate($user);
}
```

**Features:**
- 20-digit recovery codes (4 blocks of 5 digits)
- Hashed storage
- Automatic marking of used codes
- 24-hour regeneration limit
- Accepts settings model class via constructor (flexibility)

**Configuration:**
- `two_fa_recovery_codes_count`: Number of codes to generate (1-5, default 5)
- `two_fa_recovery_code_regenerate_interval`: Regeneration cooldown period (hours, default 24 hours)

---

### 3. **TwoFaPasskeyService** (Passkey Authentication)

**Responsibilities:**
- Biometric authentication based on the WebAuthn standard
- Registering and revoking Passkey credentials
- Generating authentication challenges
- Verifying authentication
- Managing trusted devices

**Key Methods:**
```php
// Check if Passkey is available (HTTPS required)
public function isAvailable(): bool

// Check if user has credentials
public function hasCredentials(TwoFaInterface $user): bool

// Register credential
public function registerCredential(TwoFaInterface $user, array $credentialData, string $deviceName = null)

// Verify assertion
public function verifyAssertion(TwoFaInterface $user, array $assertionData): bool

// Get credential list
public function getCredentials(TwoFaInterface $user)

// Revoke a credential
public function revokeCredential(TwoFaInterface $user, string $credentialId): bool

// Revoke all credentials
public function revokeAllCredentials(TwoFaInterface $user): int

// Generate registration challenge
public function generateRegistrationChallenge(TwoFaInterface $user): array

// Generate authentication challenge
public function generateAuthenticationChallenge(TwoFaInterface $user): array

// Check if device is trusted
public function isTrustedDevice(TwoFaInterface $user): bool

// Revoke a device
public function revokeDevice(TwoFaInterface $user, int $deviceId): bool

// Revoke all trusted devices
public function revokeAllTrustedDevices(Member $member): int

// Get trusted device list
public function getTrustedDevices(TwoFaInterface $user)
```

**Usage Example:**
```php
$passkeyService = app(\App\Services\TwoFa\TwoFaPasskeyService::class);

// Check if Passkey is available
if (!$passkeyService->isAvailable()) {
    // HTTPS connection required
    throw new \Exception('HTTPS connection required');
}

// Generate registration challenge
$options = $passkeyService->generateRegistrationChallenge($user);
// Pass to frontend

// Register credential
$credential = $passkeyService->registerCredential(
    $user,
    $credentialData,
    'iPhone Touch ID'
);

// Generate authentication challenge
$options = $passkeyService->generateAuthenticationChallenge($user);

// Verify authentication
if ($passkeyService->verifyAssertion($user, $assertionData)) {
    // Authentication successful
}
```

**Features:**
- WebAuthn standard compliant
- Supports Touch ID, Face ID, and Windows Hello
- Requires HTTPS connection
- Trusted device management
- Automatic device name generation

**Supported Devices:**
- iPhone/iPad: Touch ID, Face ID
- Mac: Touch ID
- Android: Fingerprint authentication
- Windows: Windows Hello

---

### 4. **TwoFaAttemptService** (Attempt Tracking)

**Responsibilities:**
- Recording 2FA attempts
- Managing lockout state
- Checking attempt limits
- Getting remaining attempts

**Key Methods:**
```php
// Record an attempt
public function recordAttempt(TwoFaInterface $user, string $attemptType, bool $success): void

// Check if user is locked out
public function isLockedOut(TwoFaInterface $user): bool

// Get remaining lockout time (minutes)
public function getRemainingLockoutTime(TwoFaInterface $user): ?int

// Check if maximum attempts have been reached
public function hasReachedMaxAttempts(TwoFaInterface $user): bool

// Get remaining available attempts
public function getRemainingAttempts(TwoFaInterface $user): int

// Handle successful authentication
public function handleSuccess(TwoFaInterface $user): void

// Check if lockout notification is enabled
public function isLockoutNotificationEnabled(): bool
```

**Usage Example:**
```php
$attemptService = app(\App\Services\TwoFa\TwoFaAttemptService::class);

// Check lockout status
if ($attemptService->isLockedOut($user)) {
    $remainingTime = $attemptService->getRemainingLockoutTime($user);
    throw new \Exception("Locked out. {$remainingTime} minutes remaining.");
}

// Record attempt
$success = $codeService->validate($user, $inputCode);
$attemptService->recordAttempt($user, 'email', $success);

// Get remaining attempts
$remaining = $attemptService->getRemainingAttempts($user);
```

**Features:**
- Records IP address and User Agent
- Per-type attempt tracking (email, passkey, recovery_code)
- Automatic lockout
- Accepts settings model class via constructor (flexibility)

**Configuration:**
- `two_fa_max_attempts`: Maximum number of attempts (default 5)
- `two_fa_attempt_window`: Attempt limit time window (minutes, default 15 minutes)
- `two_fa_lockout_duration`: Lockout duration (minutes, default 30 minutes)
- `two_fa_lockout_notification_enabled`: Enable lockout notification (default true)

---

### 5. **TwoFaService** (Orchestration)

**Responsibilities:**
- Combining and coordinating individual services
- Automatic authentication method detection
- Providing a unified authentication flow interface

**Key Methods:**
```php
// Generate 2FA code and send email (auto-detects authentication method)
public function generate($user, int $method = null)

// Verify 2FA (auto-detects authentication method)
public function validate($user, $input, int $method = null): bool

// Determine if 2FA is required
public function has($member): bool

// Determine if access is from a different environment
public function isDifferentEnvironment($member): bool

// Get system settings
public function getSystemSettings(): array

// Get the effective authentication method
public function getEffectiveAuthMethod($user): int

// Get available authentication methods
public function getAvailableMethods($user): array

// Verify recovery code
public function validateRecoveryCode($user, string $code): bool

// Check lockout status
public function checkLockout($user): array
```

**Usage Example:**
```php
$twoFaService = app(\App\Services\TwoFa\TwoFaService::class, [
    'settingModelClass' => \App\Models\MemberSetting::class,
    'context' => 'admin'
]);

// Check if 2FA is required
if ($twoFaService->has($user)) {
    // Generate code (auto-detects authentication method)
    $result = $twoFaService->generate($user);

    // For email authentication: string (code)
    // For Passkey authentication: array (challenge)
}

// Verify (auto-detects authentication method)
if ($twoFaService->validate($user, $input)) {
    // Authentication successful
}

// Check lockout status
$lockoutStatus = $twoFaService->checkLockout($user);
if ($lockoutStatus['locked_out']) {
    // User is locked out
}
```

**Features:**
- Orchestration of individual services
- Automatic authentication method detection
- Unified interface
- Context-specific behavior (admin, user, etc.)

---

### 6. **TwoFaHelper** (Helper)

**Responsibilities:**
- Retrieving system settings
- Determining 2FA enabled/disabled state
- Determining authentication methods
- Checking email configuration
- Retrieving route names

**Key Methods:**
```php
// Check if email is configured
public function isMailConfigured(): bool

// Generate code + send email (delegates to TwoFaCodeService)
public function generateAndSendCode($user, string $mailClass, int $expireMinutes = null, string $context = 'admin'): string

// Get 2FA settings
public function getTwoFaSettings(?string $settingModelClass = null): array

// Get enabled 2FA methods
public function getEnabledTwoFaMethods(?string $settingModelClass = null): array

// Get available 2FA methods for a member
public function getAvailableTwoFaMethodsForMember($member, ?string $settingModelClass = null): array

// Check if 2FA is enabled
public function isTwoFaEnabled($user, ?string $settingModelClass = null): bool

// Check if access is from a different environment
public function isDifferentEnvironment($user): bool

// Get the effective authentication method
public function getEffectiveAuthMethod($user, ?string $settingModelClass = null): int

// Get mail class for the authentication method
public function getMailClassForMethod(int $method, string $context = 'admin'): string

// Get 2FA statistics
public function getTwoFaStats(): array

// Clean up expired tokens
public function cleanupExpiredTokens(): int
```

**Usage Example:**
```php
$helper = app(\App\Helpers\TwoFaHelper::class);

// Check email configuration
if (!$helper->isMailConfigured()) {
    // Email is not configured
}

// Check if 2FA is enabled
if ($helper->isTwoFaEnabled($user, \App\Models\MemberSetting::class)) {
    // 2FA is enabled
}

// Get available authentication methods
$methods = $helper->getAvailableTwoFaMethodsForMember($user);
// e.g.: [TwoFaMethod::EMAIL->value, TwoFaMethod::PASSKEY->value]

// Get the effective authentication method
$method = $helper->getEffectiveAuthMethod($user);
```

**Features:**
- Centralized settings retrieval
- Shared decision logic
- Delegation to services
- Flexible settings model class specification

---

### 7. **TwoFaUtilityTrait** (Utility Trait)

**Responsibilities:**
- Providing delegation methods to services
- Providing common decision logic
- Abstracting settings retrieval

**Key Methods:**
```php
// Generate 2FA code (delegates to TwoFaCodeService)
public function generateTwoFaCode($user, int $expireMinutes = null): string

// Verify 2FA code (delegates to TwoFaCodeService)
public function validateTwoFaCode($user, string $inputCode): bool

// Get enabled authentication methods
public function getEnabledTwoFaMethods(string $settingsKey = 'enabled_two_fa_methods', array $defaultMethods = null): array

// Determine if 2FA is required
public function requiresTwoFa($user, int $forceSetting, array $enabledMethods): bool

// Get effective 2FA mode
protected function getEffectiveTwoFaMode($user, int $forceSetting): int

// Check member's personal settings
protected function checkMemberSetting($user): int

// Check if access is from a trusted device
protected function isFromTrustedDevice($user): bool

// Get setting value (implemented by subclass)
abstract protected function getSettingValue(string $key, $default = null);
```

**Usage Example:**
```php
class TwoFaHelper
{
    use TwoFaUtilityTrait;

    protected function getSettingValue(string $key, $default = null)
    {
        return MemberSetting::getValue($key, $default);
    }
}

// Use trait methods
$code = $this->generateTwoFaCode($user, 10);
$isValid = $this->validateTwoFaCode($user, $inputCode);
```

**Features:**
- Thin wrapper around services
- Shared decision logic
- Abstracted settings retrieval
- Improved reusability

---

### 8. **TwoFaAuthenticationTrait** (Authentication Flow Trait)

**Responsibilities:**
- Common implementation of the 2FA authentication flow
- Displaying the email authentication form
- Displaying the Passkey authentication form
- Displaying the recovery code input form
- Authentication verification processing
- Post-authentication login handling

**Key Methods:**
```php
// Show email authentication form
protected function showEmailForm(Request $request)

// Show Passkey authentication form
protected function showPasskeyForm(Request $request)

// Show recovery code input form
public function showRecoveryCodeForm(Request $request)

// Show email authentication challenge screen
public function showEmailChallenge(Request $request)

// Verify email authentication code
public function verifyEmail(Request $request)

// Resend email authentication code
public function resendEmail(Request $request)

// Show Passkey authentication challenge screen
public function showPasskeyChallenge(Request $request)

// Get Passkey challenge
public function getPasskeyChallenge(Request $request)

// Verify Passkey authentication
public function verifyPasskey(Request $request)

// Verify recovery code
public function verifyRecoveryCode(Request $request)

// Complete authentication after successful verification
protected function completeAuthentication($user, Request $request)

// Retrieve user from session
protected function getUserFromSession()

// Get available authentication methods
protected function getAvailableMethods(int $currentMethod = null): array
```

**Usage Example:**
```php
class AdminTwoFactorController extends AdminLoggedInController
{
    use TwoFaAuthenticationTrait;

    protected function getSettingModelClass(): string
    {
        return \App\Models\MemberSetting::class;
    }

    protected function getTwoFaService()
    {
        return app(\App\Services\TwoFa\TwoFaService::class, [
            'settingModelClass' => $this->getSettingModelClass(),
            'context' => 'admin'
        ]);
    }

    protected function getTwoFaRoutePrefix(): string
    {
        return 'admin';
    }

    protected function getSessionPrefix(): string
    {
        return 'admin_two_fa';
    }

    protected function getDashboardRoute(): string
    {
        return 'admin.dashboard';
    }

    protected function getLoginRoute(): string
    {
        return 'admin.login';
    }
}
```

**Features:**
- Complete 2FA authentication flow implementation
- Multiple authentication method support
- Session management
- Lockout handling
- Reusable design

**Required Methods:**
- `getSettingModelClass()`: Returns the settings model class name
- `getTwoFaService()`: Returns a TwoFaService instance
- `getTwoFaRoutePrefix()`: Returns the route name prefix
- `getSessionPrefix()`: Returns the session key prefix
- `getDashboardRoute()`: Returns the dashboard route name
- `getLoginRoute()`: Returns the login route name

---

## Authentication Flows

### Email Authentication Flow

```
1. Login attempt
   ↓
2. Check if 2FA is required via TwoFaService::has()
   ↓
3. Generate code + send email via TwoFaService::generate()
   ↓
4. Display email authentication screen
   ↓
5. User enters the code
   ↓
6. Verify code via TwoFaService::validate()
   ↓
7. Record attempt via TwoFaAttemptService::recordAttempt()
   ↓
8. Authentication successful → Login complete
```

### Passkey Authentication Flow

```
1. Login attempt
   ↓
2. Check if 2FA is required via TwoFaService::has()
   ↓
3. Generate challenge via TwoFaPasskeyService::generateAuthenticationChallenge()
   ↓
4. Display Passkey authentication screen
   ↓
5. User performs biometric authentication
   ↓
6. Verify authentication via TwoFaPasskeyService::verifyAssertion()
   ↓
7. Record attempt via TwoFaAttemptService::recordAttempt()
   ↓
8. Authentication successful → Login complete
```

### Recovery Code Authentication Flow

```
1. Email/Passkey authentication fails
   ↓
2. Display recovery code input screen
   ↓
3. User enters recovery code
   ↓
4. Verify via TwoFaRecoveryCodeService::validate()
   ↓
5. Record attempt via TwoFaAttemptService::recordAttempt()
   ↓
6. Authentication successful → Login complete
   (Used code is automatically revoked)
```

---

## Configuration Reference

### System Settings (MemberSetting)

| Setting Key | Description | Default |
|-------------|-------------|---------|
| `two_fa_force_mode` | 2FA enforcement mode (0: disabled, 1: profile settings, 2: always enabled) | 0 |
| `enabled_two_fa_methods` | Enabled authentication methods (JSON array) | `["email"]` |
| `two_fa_code_expire_minutes` | Code expiration time (minutes) | 10 |
| `two_fa_recovery_codes_count` | Number of recovery codes to generate | 5 |
| `two_fa_recovery_code_regenerate_interval` | Recovery code regeneration cooldown (hours) | 24 |
| `two_fa_max_attempts` | Maximum number of attempts | 5 |
| `two_fa_attempt_window` | Attempt limit time window (minutes) | 15 |
| `two_fa_lockout_duration` | Lockout duration (minutes) | 30 |
| `two_fa_lockout_notification_enabled` | Enable lockout notification | true |

### User Settings (Member)

| Column | Description | Default |
|--------|-------------|---------|
| `two_fa_mode` | Personal 2FA setting (0: disabled, 2: always enabled) | 0 |
| `two_fa_default_method` | Default authentication method | null |

---

## Usage Examples

### Basic Usage

```php
// 1. Check if 2FA is required
$helper = app(\App\Helpers\TwoFaHelper::class);
if ($helper->isTwoFaEnabled($user, \App\Models\MemberSetting::class)) {
    // 2. Generate code + send email
    $codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);
    $code = $codeService->generateAndSend(
        $user,
        \App\Mail\TwoFaCodeMail::class,
        10,
        'admin'
    );

    // 3. Store user ID in session
    session(['admin_two_fa.id' => $user->id]);

    // 4. Redirect to 2FA authentication screen
    return redirect()->route('admin.two-fa.email.show');
}

// If 2FA is not required, complete login
Auth::guard('member')->login($user);
return redirect()->route('admin.dashboard');
```

### Unified Approach Using TwoFaService

```php
// Create TwoFaService instance
$twoFaService = app(\App\Services\TwoFa\TwoFaService::class, [
    'settingModelClass' => \App\Models\MemberSetting::class,
    'context' => 'admin'
]);

// Check if 2FA is required
if ($twoFaService->has($user)) {
    // Generate code (auto-detects authentication method)
    $result = $twoFaService->generate($user);

    // Store user ID in session
    session(['admin_two_fa.id' => $user->id]);

    // Redirect to the appropriate route based on authentication method
    $method = $twoFaService->getEffectiveAuthMethod($user);
    $route = match($method) {
        TwoFaMethod::EMAIL->value => 'admin.two-fa.email.show',
        TwoFaMethod::PASSKEY->value => 'admin.two-fa.passkey.show',
        default => 'admin.two-fa.email.show',
    };

    return redirect()->route($route);
}
```

### Generating and Displaying Recovery Codes

```php
$recoveryCodeService = app(\App\Services\TwoFa\TwoFaRecoveryCodeService::class);

// Check if regeneration is allowed
if (!$recoveryCodeService->canRegenerate($user)) {
    $nextTime = $recoveryCodeService->getNextRegenerateTime($user);
    return response()->json([
        'success' => false,
        'message' => "Next regeneration available at: {$nextTime->format('Y-m-d H:i')}"
    ], 429);
}

// Generate recovery codes
$codes = $recoveryCodeService->generate($user);

// Format for display
$formattedCodes = array_map(function($code) use ($recoveryCodeService) {
    return $recoveryCodeService->formatCode($code);
}, $codes);

return view('admin.profile.recovery-codes', [
    'codes' => $formattedCodes
]);
```

### Passkey Registration

```php
$passkeyService = app(\App\Services\TwoFa\TwoFaPasskeyService::class);

// Check HTTPS connection
if (!$passkeyService->isAvailable()) {
    return response()->json([
        'success' => false,
        'message' => 'HTTPS connection is required'
    ], 400);
}

// Generate registration challenge
$options = $passkeyService->generateRegistrationChallenge($user);

return response()->json([
    'success' => true,
    'options' => $options
]);

// After obtaining credentials on the frontend, register them
$credential = $passkeyService->registerCredential(
    $user,
    $request->input('credential'),
    $request->input('device_name', 'My Device')
);
```

---

## Best Practices

### 1. **Choosing the Right Service**

- **When a single feature is needed**: Use the service directly
  ```php
  $codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);
  $code = $codeService->generate($user);
  ```

- **When integrated 2FA functionality is needed**: Use TwoFaService
  ```php
  $twoFaService = app(\App\Services\TwoFa\TwoFaService::class, [...]);
  $result = $twoFaService->generate($user);
  ```

- **When settings retrieval or decisions are needed**: Use TwoFaHelper
  ```php
  $helper = app(\App\Helpers\TwoFaHelper::class);
  if ($helper->isTwoFaEnabled($user)) { ... }
  ```

### 2. **Implementing the Authentication Flow**

- **When implementing the authentication flow in a controller**: Use TwoFaAuthenticationTrait
  ```php
  class MyTwoFactorController extends Controller
  {
      use TwoFaAuthenticationTrait;

      // Implement required methods
  }
  ```

### 3. **Error Handling**

```php
try {
    $code = $codeService->generateAndSend($user, ...);
} catch (\Exception $e) {
    Log::error('[2FA] Code generation failed', [
        'user_id' => $user->id,
        'error' => $e->getMessage()
    ]);

    return response()->json([
        'success' => false,
        'message' => 'Code generation failed'
    ], 500);
}
```

### 4. **Handling Lockouts**

```php
$attemptService = app(\App\Services\TwoFa\TwoFaAttemptService::class);

// Check lockout status before verification
if ($attemptService->isLockedOut($user)) {
    $remainingTime = $attemptService->getRemainingLockoutTime($user);

    return response()->json([
        'success' => false,
        'message' => "You are locked out. Please wait {$remainingTime} minutes."
    ], 429);
}

// Verify
$success = $codeService->validate($user, $inputCode);

// Record attempt
$attemptService->recordAttempt($user, 'email', $success);
```

### 5. **Specifying the Settings Model Class**

```php
// For the admin panel
$service = new TwoFaRecoveryCodeService(\App\Models\MemberSetting::class);

// For the user plugin
$service = new TwoFaRecoveryCodeService(\Plugins\DixlaseUsers\App\Models\DixlaseUsersUserSetting::class);
```

---

## Troubleshooting

### Email Sending Errors

**Problem**: Email sending error occurs during code generation

**Solution**:
1. Check email configuration
   ```php
   $helper = app(\App\Helpers\TwoFaHelper::class);
   if (!$helper->isMailConfigured()) {
       // Email is not configured
   }
   ```

2. Check logs
   ```bash
   tail -f storage/logs/laravel.log | grep "\[2FA\]"
   ```

### Passkey Authentication Errors

**Problem**: Passkey authentication is unavailable

**Solution**:
1. Verify HTTPS connection
   ```php
   $passkeyService = app(\App\Services\TwoFa\TwoFaPasskeyService::class);
   if (!$passkeyService->isAvailable()) {
       // HTTPS connection required
   }
   ```

2. Check browser compatibility
   - Chrome 67+
   - Firefox 60+
   - Safari 13+
   - Edge 18+

### Unlocking a Locked-Out User

**Problem**: A user is locked out

**Solution**:
```php
// Manually unlock (admin only)
$user->twoFaAttempts()
    ->where('success', false)
    ->where('created_at', '>=', Carbon::now()->subMinutes(30))
    ->delete();
```

---

## Summary

Dixlase's 2FA system has the following characteristics:

- **Clear separation of concerns**: Each component has a well-defined role
- **High reusability**: Traits and services provide shared functionality
- **Flexible configuration**: Settings model class can be flexibly specified
- **Multiple authentication methods**: Supports email, Passkey, and recovery codes
- **Security**: Lockout, attempt limits, and expiration management
- **Extensibility**: Easy to add new authentication methods

This architecture enables sharing 2FA functionality between the admin panel and user plugins while achieving context-specific behavior for each.
