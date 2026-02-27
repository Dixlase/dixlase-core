# Two-Factor Authentication (2FA) Practical Guide

This document is a practical guide for using Dixlase's two-factor authentication system in plugins and custom implementations.

> **Related Documentation**
> - [2FA Architecture](../../two-factor/two-factor-authentication-architecture.md) - Technical specifications and details
> - [UI Components](../../two-factor/two-factor-ui-components.md) - Frontend implementation

---

## Quick Start

### Minimal Implementation (5 Steps)

```php
// 1. Use TwoFaAuthenticationTrait
class MyTwoFactorController extends Controller
{
    use TwoFaAuthenticationTrait;

    // 2. Implement required methods
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

// 3. Define routes
Route::middleware('guest:member')->group(function () {
    Route::get('/two-fa/email', [MyTwoFactorController::class, 'showEmailChallenge'])
        ->name('admin.two-fa.email.show');
    Route::post('/two-fa/email/verify', [MyTwoFactorController::class, 'verifyEmail'])
        ->name('admin.two-fa.email.verify');
    Route::post('/two-fa/email/resend', [MyTwoFactorController::class, 'resendEmail'])
        ->name('admin.two-fa.email.resend');
});

// 4. Create the view
// resources/views/admin/two-fa/email-challenge.blade.php
@extends('layouts.auth')
@section('content')
    <x-two-factor-challenge
        :action="route('admin.two-fa.email.verify')"
        :resend-action="route('admin.two-fa.email.resend')"
    />
@endsection

// 5. Add 2FA check to the login flow
$helper = app(\App\Helpers\TwoFaHelper::class);
if ($helper->isTwoFaEnabled($user, \App\Models\MemberSetting::class)) {
    // Redirect to 2FA authentication screen
    session(['admin_two_fa.id' => $user->id]);
    return redirect()->route('admin.two-fa.email.show');
}
```

---

## Implementation Patterns

### Pattern 1: Using TwoFaAuthenticationTrait (Recommended)

**Benefits:**
- Complete authentication flow already implemented
- Automatic session management
- Built-in lockout handling
- Multiple authentication method support

**Implementation Example:**

```php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\TwoFa\TwoFaAuthenticationTrait;

class AdminTwoFactorController extends Controller
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

**Route Definition:**

```php
Route::middleware('guest:member')->prefix('admin')->name('admin.')->group(function () {
    // Email authentication
    Route::get('/two-fa/email', [AdminTwoFactorController::class, 'showEmailChallenge'])
        ->name('two-fa.email.show');
    Route::post('/two-fa/email/verify', [AdminTwoFactorController::class, 'verifyEmail'])
        ->name('two-fa.email.verify');
    Route::post('/two-fa/email/resend', [AdminTwoFactorController::class, 'resendEmail'])
        ->name('two-fa.email.resend');

    // Passkey authentication
    Route::get('/two-fa/passkey', [AdminTwoFactorController::class, 'showPasskeyChallenge'])
        ->name('two-fa.passkey.show');
    Route::post('/two-fa/passkey/challenge', [AdminTwoFactorController::class, 'getPasskeyChallenge'])
        ->name('two-fa.passkey.challenge');
    Route::post('/two-fa/passkey/verify', [AdminTwoFactorController::class, 'verifyPasskey'])
        ->name('two-fa.passkey.verify');

    // Recovery code authentication
    Route::get('/two-fa/recovery', [AdminTwoFactorController::class, 'showRecoveryCodeChallenge'])
        ->name('two-fa.recovery.show');
    Route::post('/two-fa/recovery/verify', [AdminTwoFactorController::class, 'verifyRecoveryCode'])
        ->name('two-fa.recovery.verify');
});
```

---

### Pattern 2: Using TwoFaService Directly

**Benefits:**
- More fine-grained control
- Custom flow support

**Implementation Example:**

```php
namespace App\Http\Controllers\Custom;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomTwoFactorController extends Controller
{
    protected $twoFaService;

    public function __construct()
    {
        $this->twoFaService = app(\App\Services\TwoFa\TwoFaService::class, [
            'settingModelClass' => \App\Models\MemberSetting::class,
            'context' => 'custom'
        ]);
    }

    public function showChallenge()
    {
        $userId = session('custom_two_fa.id');
        if (!$userId) {
            return redirect()->route('custom.login');
        }

        $user = \App\Models\Member::find($userId);

        // Check lockout status
        $lockoutStatus = $this->twoFaService->checkLockout($user);
        if ($lockoutStatus['locked_out']) {
            return back()->withErrors([
                'code' => "You are locked out. Please wait {$lockoutStatus['remaining_minutes']} minutes."
            ]);
        }

        // Get available authentication methods
        $methods = $this->twoFaService->getAvailableMethods($user);

        return view('custom.two-fa.challenge', [
            'methods' => $methods,
            'remaining_attempts' => $lockoutStatus['remaining_attempts'] ?? null
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $userId = session('custom_two_fa.id');
        if (!$userId) {
            return redirect()->route('custom.login');
        }

        $user = \App\Models\Member::find($userId);

        // Check lockout status
        $lockoutStatus = $this->twoFaService->checkLockout($user);
        if ($lockoutStatus['locked_out']) {
            return back()->withErrors([
                'code' => "You are locked out."
            ]);
        }

        // Verify code
        if ($this->twoFaService->validate($user, $request->code)) {
            // Authentication successful
            session()->forget('custom_two_fa');
            Auth::guard('member')->login($user);

            return redirect()->route('custom.dashboard');
        }

        // Authentication failed
        return back()->withErrors([
            'code' => 'The authentication code is incorrect.'
        ]);
    }
}
```

---

### Pattern 3: Using Individual Services

**Benefits:**
- Most fine-grained control
- Use only specific features as needed

**Implementation Example:**

```php
namespace App\Http\Controllers\Custom;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomCodeController extends Controller
{
    public function sendCode(Request $request)
    {
        $user = $request->user();

        // Generate code + send email
        $codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);

        try {
            $code = $codeService->generateAndSend(
                $user,
                \App\Mail\TwoFaCodeMail::class,
                10, // Valid for 10 minutes
                'custom'
            );

            return response()->json([
                'success' => true,
                'message' => 'Code sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send code'
            ], 500);
        }
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        $user = $request->user();

        // Check attempt count
        $attemptService = app(\App\Services\TwoFa\TwoFaAttemptService::class);
        if ($attemptService->isLockedOut($user)) {
            $remainingTime = $attemptService->getRemainingLockoutTime($user);
            return response()->json([
                'success' => false,
                'message' => "You are locked out. {$remainingTime} minutes remaining."
            ], 429);
        }

        // Verify code
        $codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);
        $success = $codeService->validate($user, $request->code);

        // Record attempt
        $attemptService->recordAttempt($user, 'email', $success);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Authentication successful'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'The code is incorrect'
        ], 400);
    }
}
```

---

## Integrating with the Login Flow

### Basic Integration

```php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Verify credentials
        if (!Auth::guard('member')->attempt($credentials, $request->filled('remember'))) {
            return back()->withErrors([
                'email' => 'The provided credentials are incorrect.'
            ]);
        }

        $user = Auth::guard('member')->user();

        // Check if 2FA is required
        $helper = app(\App\Helpers\TwoFaHelper::class);
        if ($helper->isTwoFaEnabled($user, \App\Models\MemberSetting::class)) {
            // Temporarily log out
            Auth::guard('member')->logout();

            // Store information in session
            session([
                'admin_two_fa.id' => $user->id,
                'admin_two_fa.remember' => $request->filled('remember')
            ]);

            // Generate code + send email
            $twoFaService = app(\App\Services\TwoFa\TwoFaService::class, [
                'settingModelClass' => \App\Models\MemberSetting::class,
                'context' => 'admin'
            ]);

            try {
                $twoFaService->generate($user);
            } catch (\Exception $e) {
                \Log::error('[Login] 2FA code generation failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);

                return back()->withErrors([
                    'email' => 'Failed to prepare 2FA authentication.'
                ]);
            }

            // Redirect to 2FA authentication screen
            $method = $twoFaService->getEffectiveAuthMethod($user);
            $route = match($method) {
                \App\Enums\TwoFaMethod::EMAIL->value => 'admin.two-fa.email.show',
                \App\Enums\TwoFaMethod::PASSKEY->value => 'admin.two-fa.passkey.show',
                default => 'admin.two-fa.email.show',
            };

            return redirect()->route($route);
        }

        // If 2FA is not required, complete login
        $request->session()->regenerate();
        return redirect()->intended('admin/dashboard');
    }
}
```

---

## Passkey Authentication Implementation

### Registration Flow

```php
namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PasskeyController extends Controller
{
    protected $passkeyService;

    public function __construct()
    {
        $this->passkeyService = app(\App\Services\TwoFa\TwoFaPasskeyService::class);
    }

    // Generate registration challenge
    public function registerOptions(Request $request)
    {
        $user = Auth::guard('member')->user();

        // Check HTTPS connection
        if (!$this->passkeyService->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'HTTPS connection is required'
            ], 400);
        }

        try {
            $options = $this->passkeyService->generateRegistrationChallenge($user);

            return response()->json([
                'success' => true,
                'options' => $options
            ]);
        } catch (\Exception $e) {
            \Log::error('[Passkey] Registration challenge generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Challenge generation failed'
            ], 500);
        }
    }

    // Registration handler
    public function register(Request $request)
    {
        $request->validate([
            'credential' => 'required|array',
            'device_name' => 'nullable|string|max:255'
        ]);

        $user = Auth::guard('member')->user();

        try {
            $credential = $this->passkeyService->registerCredential(
                $user,
                $request->input('credential'),
                $request->input('device_name')
            );

            return response()->json([
                'success' => true,
                'message' => 'Passkey registered successfully',
                'credential' => [
                    'id' => $credential->id,
                    'name' => $credential->name,
                    'created_at' => $credential->created_at->format('Y-m-d H:i')
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('[Passkey] Registration failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Passkey registration failed'
            ], 500);
        }
    }

    // Revocation handler
    public function revoke(Request $request, string $credentialId)
    {
        $user = Auth::guard('member')->user();

        if ($credentialId === 'all') {
            $count = $this->passkeyService->revokeAllCredentials($user);

            return response()->json([
                'success' => true,
                'message' => "{$count} passkey(s) deleted"
            ]);
        }

        if ($this->passkeyService->revokeCredential($user, $credentialId)) {
            return response()->json([
                'success' => true,
                'message' => 'Passkey deleted'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Passkey not found'
        ], 404);
    }
}
```

### Authentication Flow

```php
// If using TwoFaAuthenticationTrait, this is already implemented automatically.
// For custom implementation:

public function getPasskeyChallenge(Request $request)
{
    $userId = session('admin_two_fa.id');
    if (!$userId) {
        return response()->json([
            'success' => false,
            'message' => 'Session is invalid'
        ], 401);
    }

    $user = \App\Models\Member::find($userId);

    try {
        $options = $this->passkeyService->generateAuthenticationChallenge($user);

        return response()->json([
            'success' => true,
            'options' => $options
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Challenge generation failed'
        ], 500);
    }
}

public function verifyPasskey(Request $request)
{
    $request->validate([
        'credential' => 'required|array'
    ]);

    $userId = session('admin_two_fa.id');
    if (!$userId) {
        return response()->json([
            'success' => false,
            'message' => 'Session is invalid'
        ], 401);
    }

    $user = \App\Models\Member::find($userId);

    // Check attempt count
    $attemptService = app(\App\Services\TwoFa\TwoFaAttemptService::class);
    if ($attemptService->isLockedOut($user)) {
        return response()->json([
            'success' => false,
            'message' => 'You are locked out'
        ], 429);
    }

    // Verify authentication
    $success = $this->passkeyService->verifyAssertion($user, $request->input('credential'));

    // Record attempt
    $attemptService->recordAttempt($user, 'passkey', $success);

    if ($success) {
        // Authentication successful
        $remember = session('admin_two_fa.remember', false);
        session()->forget('admin_two_fa');

        Auth::guard('member')->login($user, $remember);

        return response()->json([
            'success' => true,
            'redirect' => route('admin.dashboard')
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Authentication failed'
    ], 400);
}
```

---

## Recovery Code Implementation

### Generation & Display

```php
namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecoveryCodeController extends Controller
{
    protected $recoveryCodeService;

    public function __construct()
    {
        $this->recoveryCodeService = app(\App\Services\TwoFa\TwoFaRecoveryCodeService::class);
    }

    // Generate
    public function generate(Request $request)
    {
        $user = Auth::guard('member')->user();

        // If codes already exist, treat as regeneration
        if ($this->recoveryCodeService->hasRecoveryCodes($user)) {
            return $this->regenerate($request);
        }

        try {
            $codes = $this->recoveryCodeService->generate($user);

            // Format
            $formattedCodes = array_map(function($code) {
                return $this->recoveryCodeService->formatCode($code);
            }, $codes);

            return response()->json([
                'success' => true,
                'codes' => $formattedCodes,
                'message' => 'Recovery codes generated'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Recovery code generation failed'
            ], 500);
        }
    }

    // Regenerate
    public function regenerate(Request $request)
    {
        $user = Auth::guard('member')->user();

        // Check if regeneration is allowed
        if (!$this->recoveryCodeService->canRegenerate($user)) {
            $nextTime = $this->recoveryCodeService->getNextRegenerateTime($user);

            return response()->json([
                'success' => false,
                'message' => "Next regeneration available at: {$nextTime->format('Y-m-d H:i')}",
                'next_time' => $nextTime->format('Y-m-d H:i')
            ], 429);
        }

        try {
            $codes = $this->recoveryCodeService->generate($user);

            $formattedCodes = array_map(function($code) {
                return $this->recoveryCodeService->formatCode($code);
            }, $codes);

            return response()->json([
                'success' => true,
                'codes' => $formattedCodes,
                'message' => 'Recovery codes regenerated'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Recovery code regeneration failed'
            ], 500);
        }
    }
}
```

### Using Recovery Codes for Authentication

```php
// If using TwoFaAuthenticationTrait, this is already implemented automatically.
// For custom implementation:

public function verifyRecoveryCode(Request $request)
{
    $request->validate([
        'recovery_code' => 'required|string'
    ]);

    $userId = session('admin_two_fa.id');
    if (!$userId) {
        return redirect()->route('admin.login');
    }

    $user = \App\Models\Member::find($userId);

    // Check attempt count
    $attemptService = app(\App\Services\TwoFa\TwoFaAttemptService::class);
    if ($attemptService->isLockedOut($user)) {
        return back()->withErrors([
            'recovery_code' => 'You are locked out'
        ]);
    }

    // Verify recovery code
    $recoveryCodeService = app(\App\Services\TwoFa\TwoFaRecoveryCodeService::class);
    $success = $recoveryCodeService->validate($user, $request->recovery_code);

    // Record attempt
    $attemptService->recordAttempt($user, 'recovery_code', $success);

    if ($success) {
        // Authentication successful
        $remember = session('admin_two_fa.remember', false);
        session()->forget('admin_two_fa');

        Auth::guard('member')->login($user, $remember);

        // Check remaining recovery code count
        $remaining = $recoveryCodeService->getRemainingCount($user);
        if ($remaining <= 2) {
            session()->flash('warning', "You have {$remaining} recovery code(s) remaining. Please generate new codes.");
        }

        return redirect()->route('admin.dashboard');
    }

    return back()->withErrors([
        'recovery_code' => 'The recovery code is incorrect'
    ]);
}
```

---

## Usage in Plugin Development

### Implementation Example for a User Plugin

```php
namespace Plugins\DixlaseUsers\App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\TwoFa\TwoFaAuthenticationTrait;

class DixlaseUsersTwoFactorController extends Controller
{
    use TwoFaAuthenticationTrait;

    protected function getSettingModelClass(): string
    {
        // Use the user plugin's settings model
        return \Plugins\DixlaseUsers\App\Models\DixlaseUsersUserSetting::class;
    }

    protected function getTwoFaService()
    {
        return app(\App\Services\TwoFa\TwoFaService::class, [
            'settingModelClass' => $this->getSettingModelClass(),
            'context' => 'user' // Set context to 'user'
        ]);
    }

    protected function getTwoFaRoutePrefix(): string
    {
        return 'users-plugin.two-fa';
    }

    protected function getSessionPrefix(): string
    {
        return 'user_two_fa';
    }

    protected function getDashboardRoute(): string
    {
        return 'users-plugin.dashboard';
    }

    protected function getLoginRoute(): string
    {
        return 'users-plugin.login';
    }
}
```

**Route Definition (within the plugin):**

```php
// plugins/DixlaseUsers/routes/web.php

Route::middleware('guest:dixlase_users_user')->prefix('user')->name('users-plugin.')->group(function () {
    Route::prefix('two-fa')->name('two-fa.')->group(function () {
        // Email authentication
        Route::get('/email', [DixlaseUsersTwoFactorController::class, 'showEmailChallenge'])
            ->name('email.show');
        Route::post('/email/verify', [DixlaseUsersTwoFactorController::class, 'verifyEmail'])
            ->name('email.verify');
        Route::post('/email/resend', [DixlaseUsersTwoFactorController::class, 'resendEmail'])
            ->name('email.resend');

        // Passkey authentication
        Route::get('/passkey', [DixlaseUsersTwoFactorController::class, 'showPasskeyChallenge'])
            ->name('passkey.show');
        Route::post('/passkey/challenge', [DixlaseUsersTwoFactorController::class, 'getPasskeyChallenge'])
            ->name('passkey.challenge');
        Route::post('/passkey/verify', [DixlaseUsersTwoFactorController::class, 'verifyPasskey'])
            ->name('passkey.verify');

        // Recovery code authentication
        Route::get('/recovery', [DixlaseUsersTwoFactorController::class, 'showRecoveryCodeChallenge'])
            ->name('recovery.show');
        Route::post('/recovery/verify', [DixlaseUsersTwoFactorController::class, 'verifyRecoveryCode'])
            ->name('recovery.verify');
    });
});
```

---

## Troubleshooting

### Email Sending Errors

**Symptom**: Email sending error occurs during code generation

**Causes and Solutions:**

1. **Email is not configured**
   ```php
   $helper = app(\App\Helpers\TwoFaHelper::class);
   if (!$helper->isMailConfigured()) {
       // Please complete the email configuration
   }
   ```

2. **Mail class not found**
   ```php
   // Specify the correct mail class
   $codeService->generateAndSend(
       $user,
       \App\Mail\TwoFaCodeMail::class, // ← Specify an existing class
       10,
       'admin'
   );
   ```

3. **Check logs**
   ```bash
   tail -f storage/logs/laravel.log | grep "\[2FA\]"
   ```

---

### Passkey Authentication Errors

**Symptom**: Passkey authentication is unavailable

**Causes and Solutions:**

1. **HTTPS connection required**
   ```php
   $passkeyService = app(\App\Services\TwoFa\TwoFaPasskeyService::class);
   if (!$passkeyService->isAvailable()) {
       // HTTPS connection is required
   }
   ```

2. **Browser is not supported**
   - Chrome 67+
   - Firefox 60+
   - Safari 13+
   - Edge 18+

3. **Device is not supported**
   - Devices with Touch ID/Face ID
   - PCs with Windows Hello support
   - FIDO2-compatible security keys

---

### Lockout Issues

**Symptom**: User is locked out

**Solution:**

```php
// Manually unlock (admin only)
$user = \App\Models\Member::find($userId);
$user->twoFaAttempts()
    ->where('success', false)
    ->where('created_at', '>=', \Carbon\Carbon::now()->subMinutes(30))
    ->delete();
```

---

### Session Errors

**Symptom**: "Session is invalid" error on the 2FA authentication screen

**Causes and Solutions:**

1. **Incorrect session key**
   ```php
   // During login
   session(['admin_two_fa.id' => $user->id]);

   // During 2FA authentication
   $userId = session('admin_two_fa.id'); // ← Use the same key
   ```

2. **Session expired**
   ```php
   // config/session.php
   'lifetime' => 120, // Session lifetime (minutes)
   ```

---

## Best Practices

### 1. Error Handling

```php
try {
    $code = $codeService->generateAndSend($user, ...);
} catch (\Exception $e) {
    \Log::error('[2FA] Code generation failed', [
        'user_id' => $user->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    return response()->json([
        'success' => false,
        'message' => 'Code generation failed'
    ], 500);
}
```

### 2. Logging

```php
\Log::info('[2FA] Authentication attempt', [
    'user_id' => $user->id,
    'method' => 'email',
    'ip' => request()->ip(),
    'user_agent' => request()->userAgent()
]);
```

### 3. Security

```php
// Always implement lockout checks
$attemptService = app(\App\Services\TwoFa\TwoFaAttemptService::class);
if ($attemptService->isLockedOut($user)) {
    // Handle lockout
}

// Always record attempts
$attemptService->recordAttempt($user, 'email', $success);
```

### 4. User Experience

```php
// Display remaining attempts
$remaining = $attemptService->getRemainingAttempts($user);
if ($remaining <= 3) {
    session()->flash('warning', "You have {$remaining} attempt(s) remaining.");
}

// Warn about remaining recovery codes
$recoveryCodeService = app(\App\Services\TwoFa\TwoFaRecoveryCodeService::class);
$remaining = $recoveryCodeService->getRemainingCount($user);
if ($remaining <= 2) {
    session()->flash('warning', "You have {$remaining} recovery code(s) remaining.");
}
```

---

## Summary

Dixlase's 2FA system has the following characteristics:

- **Easy implementation**: Implement in 5 steps with TwoFaAuthenticationTrait
- **Flexible design**: Fine-grained control possible with individual services
- **Plugin support**: Settings model class can be flexibly specified
- **Multiple authentication methods**: Supports email, Passkey, and recovery codes
- **Security**: Lockout, attempt limits, and expiration management
- **Extensibility**: Easy to add new authentication methods

By following this guide, you can easily add secure and user-friendly 2FA functionality.

---

## Related Documentation

- [2FA Architecture](../../two-factor/two-factor-authentication-architecture.md) - Technical specifications and details
- [UI Components](../../two-factor/two-factor-ui-components.md) - Frontend implementation
