# Two-Factor Authentication (2FA) UI Components

This document explains how to use the UI components for Dixlase's two-factor authentication system.

> **Related Documentation**
> - [2FA Architecture](./two-factor-authentication-architecture.md) - Technical specifications and details
> - [2FA Practical Guide](../../operations/security/two-factor-authentication-guide.md) - Backend implementation

---

## Overview

Dixlase provides reusable Blade components that make it easy to implement two-factor authentication UIs. These components can be used across the admin panel, user plugins, and custom plugins.

### Provided Components

1. **`<x-two-factor-challenge>`** - 6-digit code input form
2. **Authentication layout** - `layouts.auth`

---

## Basic Usage

### Minimal Implementation

```blade
@extends('layouts.auth')

@section('title', 'Two-Factor Authentication')
@section('header', 'Two-Factor Authentication')
@section('description', 'Enter the verification code sent to your email')

@section('content')
    <x-two-factor-challenge
        :action="route('admin.two-fa.email.verify')"
        :resend-action="route('admin.two-fa.email.resend')"
    />
@endsection
```

---

## Component Details

### 1. `<x-two-factor-challenge>` Component

Provides a 6-digit verification code input form.

**Location**: `resources/views/components/two-factor-challenge.blade.php`

#### Properties

| Property | Type | Default | Required | Description |
|----------|------|---------|----------|-------------|
| `action` | string | - | Yes | Form submission URL |
| `resendAction` | string | null | No | Resend URL (optional) |
| `title` | string | `__('auth.two_factor.title')` | No | Title |
| `prompt` | string | `__('auth.two_factor.prompt')` | No | Prompt message |
| `submitText` | string | `__('auth.two_factor.submit')` | No | Submit button text |
| `resendText` | string | `__('auth.two_factor.resend')` | No | Resend button text |
| `expireMinutes` | int | 10 | No | Expiration time (minutes) |
| `codeLength` | int | 6 | No | Code length |
| `autoSubmit` | bool | true | No | Auto-submit |
| `showExpireTime` | bool | true | No | Show expiration time |
| `showResend` | bool | true | No | Show resend button |

#### Basic Usage Example

```blade
<x-two-factor-challenge
    :action="route('admin.two-fa.email.verify')"
    :resend-action="route('admin.two-fa.email.resend')"
/>
```

#### Customization Example

```blade
<x-two-factor-challenge
    :action="route('custom.verify')"
    :resend-action="route('custom.resend')"
    title="Custom Authentication"
    prompt="Enter your custom message here"
    submit-text="Confirm"
    resend-text="Resend Code"
    :expire-minutes="5"
    :code-length="4"
    :auto-submit="false"
    :show-expire-time="false"
    :show-resend="false"
/>
```

#### Features

##### 1. Input Handling

- **Numeric only**: Accepts only digits 0-9
- **Auto-focus**: Automatically moves focus to the next field after input
- **Backspace support**: Pressing Backspace on an empty field moves focus to the previous field
- **Paste support**: Pasting a 6-digit code automatically distributes it across the fields

##### 2. Auto-Submit

When `autoSubmit` is `true` (default), the form is automatically submitted once all fields are filled.

##### 3. Accessibility

- `inputmode="numeric"`: Displays the numeric keyboard on mobile devices
- `autocomplete="one-time-code"`: Supports browser auto-fill
- Proper focus management

##### 4. Form Data

The component submits the following data:

```php
[
    '_token' => 'csrf_token', // Automatically included
    'code' => '123456'        // The entered code
]
```

---

### 2. `layouts.auth` Layout

Provides a unified layout for authentication screens.

**Location**: `resources/views/layouts/auth.blade.php`

#### Sections

| Section | Type | Required | Description |
|---------|------|----------|-------------|
| `@section('title')` | string | Yes | Page title (browser tab) |
| `@section('icon')` | string | No | Font Awesome icon class |
| `@section('header')` | string | Yes | Page header |
| `@section('description')` | string | No | Description text |
| `@section('content')` | blade | Yes | Main content |
| `@section('back_link')` | blade | No | Back link |

#### Usage Example

```blade
@extends('layouts.auth')

@section('title', 'Two-Factor Authentication')
@section('icon', 'fas fa-shield-alt')
@section('header', 'Two-Factor Authentication')
@section('description', 'Additional authentication is required for security')

@section('content')
    <x-two-factor-challenge
        :action="route('admin.two-fa.email.verify')"
        :resend-action="route('admin.two-fa.email.resend')"
    />
@endsection

@section('back_link')
    <a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← Back to login
    </a>
@endsection
```

---

## Implementation Patterns

### Pattern 1: Email Authentication Only

```blade
@extends('layouts.auth')

@section('title', 'Email Authentication')
@section('icon', 'fas fa-envelope')
@section('header', 'Email Authentication')
@section('description', 'Enter the 6-digit code sent to your email')

@section('content')
    <x-two-factor-challenge
        :action="route('admin.two-fa.email.verify')"
        :resend-action="route('admin.two-fa.email.resend')"
    />
@endsection

@section('back_link')
    <a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← Back to login
    </a>
@endsection
```

---

### Pattern 2: Displaying Alternative Authentication Methods

```blade
@extends('layouts.auth')

@section('title', 'Two-Factor Authentication')
@section('icon', 'fas fa-shield-alt')
@section('header', 'Two-Factor Authentication')
@section('description', 'Choose your authentication method')

@section('content')
    {{-- Email authentication form --}}
    <x-two-factor-challenge
        :action="route('admin.two-fa.email.verify')"
        :resend-action="route('admin.two-fa.email.resend')"
    />

    {{-- Alternative authentication methods --}}
    @if(!empty($availableMethods))
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 text-center">
                or
            </p>

            <div class="space-y-2">
                @foreach($availableMethods as $method)
                    <a href="{{ $method['url'] }}"
                       class="block w-full px-4 py-2 text-sm text-center text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        {{ $method['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Recovery code link --}}
    <div class="mt-4 text-center">
        <a href="{{ route('admin.two-fa.recovery.show') }}"
           class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
            Use a recovery code
        </a>
    </div>
@endsection

@section('back_link')
    <a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← Back to login
    </a>
@endsection
```

**Controller Side:**

```php
public function showEmailChallenge()
{
    $user = $this->getUserFromSession();

    // Get available authentication methods
    $twoFaService = $this->getTwoFaService();
    $methods = $twoFaService->getAvailableMethods($user);

    // Exclude the current method (email)
    $availableMethods = array_filter($methods, function($method) {
        return $method['value'] !== \App\Enums\TwoFaMethod::EMAIL->value;
    });

    // Add route information
    $availableMethods = array_map(function($method) {
        $method['url'] = match($method['value']) {
            \App\Enums\TwoFaMethod::PASSKEY->value => route('admin.two-fa.passkey.show'),
            default => '#',
        };
        return $method;
    }, $availableMethods);

    return view('admin.two-fa.email-challenge', [
        'availableMethods' => $availableMethods
    ]);
}
```

---

### Pattern 3: Passkey Authentication Screen

```blade
@extends('layouts.auth')

@section('title', 'Passkey Authentication')
@section('icon', 'fas fa-fingerprint')
@section('header', 'Passkey Authentication')
@section('description', 'Authenticate using Touch ID, Face ID, or similar')

@section('content')
    <div class="space-y-4">
        {{-- Authentication button --}}
        <button type="button"
                id="passkey-auth-button"
                class="w-full px-4 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors">
            <i class="fas fa-fingerprint mr-2"></i>
            Authenticate with Passkey
        </button>

        {{-- Status display --}}
        <div id="passkey-status" class="text-sm text-center text-gray-600 dark:text-gray-400 hidden">
            Authenticating...
        </div>

        {{-- Error display --}}
        <div id="passkey-error" class="text-sm text-center text-red-600 dark:text-red-400 hidden"></div>
    </div>

    {{-- Alternative authentication methods --}}
    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 text-center">
            or
        </p>

        <a href="{{ route('admin.two-fa.email.show') }}"
           class="block w-full px-4 py-2 text-sm text-center text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            Use email authentication
        </a>
    </div>
@endsection

@section('back_link')
    <a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← Back to login
    </a>
@endsection

@push('scripts')
<script>
document.getElementById('passkey-auth-button').addEventListener('click', async function() {
    const button = this;
    const status = document.getElementById('passkey-status');
    const error = document.getElementById('passkey-error');

    button.disabled = true;
    status.classList.remove('hidden');
    error.classList.add('hidden');

    try {
        // Get challenge
        const challengeResponse = await fetch('{{ route("admin.two-fa.passkey.challenge") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const challengeData = await challengeResponse.json();

        if (!challengeData.success) {
            throw new Error(challengeData.message || 'Authentication failed');
        }

        // WebAuthn authentication
        const credential = await navigator.credentials.get({
            publicKey: challengeData.options
        });

        // Verify authentication
        const verifyResponse = await fetch('{{ route("admin.two-fa.passkey.verify") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                credential: {
                    id: credential.id,
                    rawId: btoa(String.fromCharCode(...new Uint8Array(credential.rawId))),
                    response: {
                        authenticatorData: btoa(String.fromCharCode(...new Uint8Array(credential.response.authenticatorData))),
                        clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credential.response.clientDataJSON))),
                        signature: btoa(String.fromCharCode(...new Uint8Array(credential.response.signature)))
                    },
                    type: credential.type
                }
            })
        });

        const verifyData = await verifyResponse.json();

        if (verifyData.success) {
            window.location.href = verifyData.redirect;
        } else {
            throw new Error(verifyData.message || 'Authentication failed');
        }
    } catch (err) {
        error.textContent = err.message;
        error.classList.remove('hidden');
        button.disabled = false;
        status.classList.add('hidden');
    }
});
</script>
@endpush
```

---

### Pattern 4: Recovery Code Input Screen

```blade
@extends('layouts.auth')

@section('title', 'Recovery Code')
@section('icon', 'fas fa-key')
@section('header', 'Recovery Code')
@section('description', 'Enter your 20-digit recovery code')

@section('content')
    <form method="POST" action="{{ route('admin.two-fa.recovery.verify') }}">
        @csrf

        <div class="space-y-4">
            {{-- Recovery code input --}}
            <div>
                <label for="recovery_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Recovery Code
                </label>
                <input type="text"
                       id="recovery_code"
                       name="recovery_code"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white @error('recovery_code') border-red-500 @enderror"
                       placeholder="12345-67890-12345-67890"
                       required
                       autofocus>

                @error('recovery_code')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Hyphens are automatically removed
                </p>
            </div>

            {{-- Submit button --}}
            <button type="submit"
                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors">
                Authenticate
            </button>
        </div>
    </form>

    {{-- Alternative authentication methods --}}
    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 text-center">
            or
        </p>

        <a href="{{ route('admin.two-fa.email.show') }}"
           class="block w-full px-4 py-2 text-sm text-center text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            Back to email authentication
        </a>
    </div>
@endsection

@section('back_link')
    <a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← Back to login
    </a>
@endsection
```

---

## Usage in Plugin Development

### Implementation Example for a User Plugin

```blade
{{-- plugins/DixlaseUsers/resources/views/two-fa/email-challenge.blade.php --}}

@extends('users-plugin::layouts.auth')

@section('title', 'Two-Factor Authentication')
@section('header', 'Two-Factor Authentication')
@section('description', 'Enter the verification code sent to your email')

@section('content')
    <x-two-factor-challenge
        :action="route('users-plugin.two-fa.email.verify')"
        :resend-action="route('users-plugin.two-fa.email.resend')"
        :title="__('users-plugin::auth.two_factor.title')"
        :prompt="__('users-plugin::auth.two_factor.prompt')"
        :submit-text="__('users-plugin::auth.two_factor.submit')"
        :resend-text="__('users-plugin::auth.two_factor.resend')"
    />
@endsection

@section('back_link')
    <a href="{{ route('users-plugin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← Back to login
    </a>
@endsection
```

---

## Customization

### Style Customization

The components use Tailwind CSS and support dark mode.

**To add custom CSS:**

```blade
@push('styles')
<style>
/* Custom styles */
.two-factor-input {
    /* Add custom styles */
}
</style>
@endpush
```

### JavaScript Customization

To customize the component's JavaScript behavior:

```blade
@push('scripts')
<script>
// Listen to component events
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action="{{ route('admin.two-fa.email.verify') }}"]');

    form.addEventListener('submit', function(e) {
        // Custom processing
        console.log('Form submitting...');
    });
});
</script>
@endpush
```

---

## Translation Keys

### Default Translation Keys

The components use the following translation keys:

```php
// lang/en/auth.php
'two_factor' => [
    'title' => 'Two-Factor Authentication',
    'prompt' => 'Enter the 6-digit code sent to your email',
    'submit' => 'Authenticate',
    'resend' => 'Resend',
    'expire_message' => 'The code is valid for :minutes minutes',
],
```

### Using Custom Translation Keys

```blade
<x-two-factor-challenge
    :action="route('custom.verify')"
    :title="__('custom.two_factor.title')"
    :prompt="__('custom.two_factor.prompt')"
    :submit-text="__('custom.two_factor.submit')"
    :resend-text="__('custom.two_factor.resend')"
/>
```

---

## Troubleshooting

### Component Not Displaying

**Cause**: Component file not found

**Solution**:
```bash
# Verify the component file exists
ls resources/views/components/two-factor-challenge.blade.php
```

### Auto-Submit Not Working

**Cause**: JavaScript error

**Solution**:
1. Check the browser console
2. Verify that the `autoSubmit` property is set to `true`
3. Verify that JavaScript is loaded correctly

### Styles Not Applied

**Cause**: Tailwind CSS is not loaded

**Solution**:
```blade
{{-- Load Tailwind CSS in the layout --}}
<link href="{{ asset('css/app.css') }}" rel="stylesheet">
```

---

## Best Practices

### 1. Use a Consistent Layout

```blade
{{-- Recommended: Use layouts.auth --}}
@extends('layouts.auth')

{{-- Not recommended: Create a custom layout each time --}}
@extends('custom-layout')
```

### 2. Display Error Messages

```blade
@if($errors->any())
    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md">
        <p class="text-sm text-red-600 dark:text-red-400">
            {{ $errors->first() }}
        </p>
    </div>
@endif

<x-two-factor-challenge
    :action="route('admin.two-fa.email.verify')"
    :resend-action="route('admin.two-fa.email.resend')"
/>
```

### 3. Display Lockout Status

```blade
@if(session('lockout'))
    <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
        <p class="text-sm text-yellow-600 dark:text-yellow-400">
            You are locked out. Please wait {{ session('lockout_minutes') }} minutes.
        </p>
    </div>
@else
    <x-two-factor-challenge
        :action="route('admin.two-fa.email.verify')"
        :resend-action="route('admin.two-fa.email.resend')"
    />
@endif
```

### 4. Display Remaining Attempts

```blade
@if(isset($remaining_attempts) && $remaining_attempts <= 3)
    <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
        <p class="text-sm text-yellow-600 dark:text-yellow-400">
            You have {{ $remaining_attempts }} attempt(s) remaining.
        </p>
    </div>
@endif

<x-two-factor-challenge
    :action="route('admin.two-fa.email.verify')"
    :resend-action="route('admin.two-fa.email.resend')"
/>
```

---

## Summary

Dixlase's 2FA UI components have the following characteristics:

- **Easy implementation**: Add a complete authentication form with a single line of code
- **Reusable**: Shared across admin panel and plugins
- **Customizable**: Flexibly adjustable via properties
- **Accessible**: Mobile-friendly with keyboard navigation support
- **Dark mode support**: Automatically adapts to dark mode
- **Japanese and English**: Messages come from translation keys

By using these components, you can easily implement beautiful, consistent 2FA authentication screens.

---

## Related Documentation

- [2FA Architecture](./two-factor-authentication-architecture.md) - Technical specifications and details
- [2FA Practical Guide](../../operations/security/two-factor-authentication-guide.md) - Backend implementation
