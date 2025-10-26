# Two-Factor Challenge Component Usage

## Overview

The `two-factor-challenge` component provides a reusable UI for two-factor authentication code input. It's designed to be flexible and can be used across different parts of the application, including future plugins.

## Component Location

`resources/views/components/two-factor-challenge.blade.php`

## Basic Usage

```blade
<x-two-factor-challenge 
    :action="route('your.confirm.route')"
    :resend-action="route('your.resend.route')"
/>
```

## Available Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `action` | string | **required** | Form action URL for code submission |
| `resendAction` | string | null | Form action URL for resending code (optional) |
| `title` | string | `__('auth.two_factor.title')` | Page/section title |
| `prompt` | string | `__('auth.two_factor.prompt')` | Instruction text for user |
| `submitText` | string | `__('auth.two_factor.submit')` | Submit button text |
| `resendText` | string | `__('auth.two_factor.resend')` | Resend button text |
| `expireMinutes` | int | `config('two-factor.code_expiration', 5)` | Code expiration time |
| `codeLength` | int | 6 | Number of code digits |
| `autoSubmit` | bool | true | Auto-submit when all digits entered |
| `showExpireTime` | bool | true | Show expiration time message |
| `showResend` | bool | true | Show resend button |

## Usage Examples

### Basic Admin Implementation
```blade
<x-two-factor-challenge 
    :action="route('admin.two-factor.confirm')"
    :resend-action="route('admin.two-factor.resend')"
/>
```

### User Plugin Implementation
```blade
<x-two-factor-challenge 
    :action="route('user.two-factor.confirm')"
    :resend-action="route('user.two-factor.resend')"
    :prompt="__('user.two_factor.prompt')"
    :submit-text="__('user.two_factor.submit')"
    :resend-text="__('user.two_factor.resend')"
/>
```

### Custom Configuration
```blade
<x-two-factor-challenge 
    :action="route('custom.verify')"
    :code-length="4"
    :auto-submit="false"
    :show-expire-time="false"
    :show-resend="false"
    prompt="Enter your 4-digit verification code:"
    submit-text="Verify Code"
/>
```

## Features

### Input Handling
- **Numeric Only**: Only accepts numeric input (0-9)
- **Auto-Focus**: Automatically moves focus to next field
- **Backspace Navigation**: Backspace moves to previous field when current is empty
- **Paste Support**: Supports pasting full codes (distributes across all fields)

### Auto-Submit
When `autoSubmit` is enabled (default), the form automatically submits when all code fields are filled.

### Accessibility
- Uses `inputmode="numeric"` for mobile keyboards
- Includes `autocomplete="one-time-code"` for browser integration
- Proper focus management for keyboard navigation

## JavaScript Functions

The component creates globally scoped functions with prefixes to avoid conflicts:
- `handleTwoFactorInput(event, index)`
- `handleTwoFactorKeyDown(event, index)`

## Form Data

The component submits a form with:
- CSRF token (automatically included)
- `code` field containing the complete entered code

## Integration Requirements

### Routes
Your application needs to define the following routes:
- Confirmation route (handles POST with `code` parameter)
- Resend route (optional, handles POST to resend code)

### Translation Keys
Default translation keys used:
- `auth.two_factor.title`
- `auth.two_factor.prompt`
- `auth.two_factor.submit`
- `auth.two_factor.resend`

### Configuration
Default configuration keys:
- `app.two_factor.email_code_expire` (code expiration in minutes)

## Plugin Development Guidelines

When developing plugins that use two-factor authentication:

1. **Use the component**: Don't recreate the UI, use the existing component
2. **Customize as needed**: Override properties to match your plugin's needs
3. **Maintain consistency**: Use similar translation key patterns
4. **Handle responses**: Ensure your controllers properly handle the `code` parameter
5. **Error handling**: Display validation errors appropriately in your layout

## Example Controller Integration

```php
public function confirm(Request $request)
{
    $request->validate([
        'code' => 'required|string|size:6'
    ]);
    
    // Verify the code
    if ($this->verifyTwoFactorCode($request->code)) {
        // Success - redirect or continue
        return redirect()->route('dashboard');
    }
    
    // Failed - redirect back with error
    return back()->withErrors(['code' => 'Invalid verification code']);
}
```

## Styling

The component uses Tailwind CSS classes and supports dark mode. It's designed to work with the existing admin theme but can be customized through CSS overrides if needed for plugin-specific styling.
