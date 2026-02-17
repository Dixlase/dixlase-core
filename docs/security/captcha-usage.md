# reCAPTCHA Implementation Guide

## Overview

A multi-provider CAPTCHA feature has been implemented in Dixlase. It currently supports Google reCAPTCHA, with an architecture designed to allow future additions such as hCaptcha and Cloudflare Turnstile.

## Configuration

### 1. Admin Panel Configuration

1. Navigate to Admin Panel > Security Settings
2. Check "Enable reCAPTCHA"
3. Enter Google reCAPTCHA settings:
   - Site key
   - Secret key
   - Version (v3 recommended)
   - Minimum score (for v3, typically 0.5)
4. Configure reCAPTCHA usage for each form in the per-form settings

### 2. Obtaining Google reCAPTCHA Keys

1. Visit [Google reCAPTCHA](https://www.google.com/recaptcha/)
2. Register a new site
3. Obtain the site key and secret key
4. Enter them in the admin panel

## Usage

### Displaying reCAPTCHA in Forms

```blade
<!-- Display reCAPTCHA inside a form -->
<form method="POST" action="/contact">
    @csrf

    <!-- Other form fields -->
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <textarea name="message" required></textarea>

    <!-- reCAPTCHA widget -->
    <x-captcha action="contact_form" />

    <button type="submit">Submit</button>
</form>
```

### Verification in Controllers

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\VerifiesCaptcha;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    use VerifiesCaptcha;

    public function store(Request $request)
    {
        // reCAPTCHA verification
        $this->verifyCaptcha($request, 'contact');

        // Standard validation
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string',
        ]);

        // Process the form
        // ...

        return redirect()->back()->with('success', 'Your inquiry has been received.');
    }
}
```

### Verification in Form Requests

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\VerifiesCaptcha;

class ContactFormRequest extends FormRequest
{
    use VerifiesCaptcha;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string',
        ], $this->getCaptchaRules());
    }

    protected function prepareForValidation()
    {
        $this->verifyCaptcha($this, 'contact');
    }
}
```

## Configuration Options

### reCAPTCHA v3 (Recommended)
- Non-interactive
- Score-based (0.0-1.0)
- UX-friendly

### reCAPTCHA v2
- Checkbox-based
- More reliable but impacts UX

## Implementing CAPTCHA in Plugins and Themes

### Action Name Conventions

When implementing CAPTCHA in plugins or themes, action names must be prefixed.

**Naming conventions:**
- **Core features**: `admin_login`, `user_register`, etc.
- **Plugins**: `{plugin-slug}.{action}` format
- **Themes**: `{theme-slug}.{action}` format

**Examples:**
- DixlaseUsers plugin: `dixlase-users.user_register`
- DixlaseUsers plugin: `dixlase-users.user_profile_update`
- DixlaseBlog plugin: `dixlase-blog.comment_submit`

### Controller Implementation

#### 1. Implementing the getCaptchaAction() Method

Implement the `getCaptchaAction()` method in your plugin's controller to return the prefixed action name.

```php
<?php

namespace Plugins\DixlaseUsers\App\Http\Controllers\Front\Register;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DixlaseUsersRegisterController extends Controller
{
    /**
     * Returns the CAPTCHA action name
     */
    protected function getCaptchaAction(): string
    {
        return 'dixlase-users.user_register';
    }

    /**
     * Display the registration form
     */
    public function create()
    {
        // Get CAPTCHA settings
        $captchaAction = $this->getCaptchaAction();
        $captchaEnabled = \App\Helpers\CaptchaHelper::shouldShowCaptcha($captchaAction);
        $captchaWidget = \App\Helpers\CaptchaHelper::renderWidget($captchaAction);

        return view('dixlase-users::front.register.create', [
            'captchaEnabled' => $captchaEnabled,
            'captchaWidget' => $captchaWidget,
        ]);
    }
}
```

#### 2. Displaying CAPTCHA in Views

In views, use the `$captchaEnabled` and `$captchaWidget` variables passed from the controller to display the CAPTCHA.

```blade
<form method="POST" action="{{ route('dixlase-users.register.store') }}">
    @csrf

    <!-- Form fields -->
    <div class="form-group">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" required>
    </div>

    <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" name="email" id="email" required>
    </div>

    <!-- CAPTCHA widget -->
    @if($captchaEnabled ?? false)
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            {!! $captchaWidget !!}
        </div>
    @endif

    <button type="submit" class="btn btn-primary">Register</button>
</form>
```

#### 3. CAPTCHA Verification

Use the `VerifiesCaptcha` trait to verify the CAPTCHA when the form is submitted.

```php
<?php

namespace Plugins\DixlaseUsers\App\Http\Controllers\Front\Register;

use App\Http\Controllers\Controller;
use App\Traits\VerifiesCaptcha;
use Illuminate\Http\Request;

class DixlaseUsersRegisterController extends Controller
{
    use VerifiesCaptcha;

    protected function getCaptchaAction(): string
    {
        return 'dixlase-users.user_register';
    }

    public function store(Request $request)
    {
        // CAPTCHA verification
        $this->verifyCaptcha($request, $this->getCaptchaAction());

        // Standard validation
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Registration processing
        // ...

        return redirect()->route('dixlase-users.register.complete')
            ->with('success', 'Registration is complete.');
    }
}
```

### Sharing Across Multiple Forms (e.g., Profile Updates)

Here is an example of using the same CAPTCHA action name across multiple forms.

```php
<?php

namespace Plugins\DixlaseUsers\App\Http\Controllers\Mypage\Profile;

use App\Http\Controllers\Controller;
use App\Traits\VerifiesCaptcha;
use Illuminate\Http\Request;

class DixlaseUsersMypageProfileController extends Controller
{
    use VerifiesCaptcha;

    /**
     * Shared CAPTCHA action name for profile updates
     */
    protected function getCaptchaAction(): string
    {
        return 'dixlase-users.user_profile_update';
    }

    /**
     * Display basic info form
     */
    public function basicInfo(Request $request)
    {
        $captchaAction = $this->getCaptchaAction();
        $captchaEnabled = \App\Helpers\CaptchaHelper::shouldShowCaptcha($captchaAction);
        $captchaWidget = \App\Helpers\CaptchaHelper::renderWidget($captchaAction);

        return view('dixlase-users::mypage.profile.basic-info', [
            'user' => $request->user(),
            'captchaEnabled' => $captchaEnabled,
            'captchaWidget' => $captchaWidget,
        ]);
    }

    /**
     * Display appearance settings form
     */
    public function appearance(Request $request)
    {
        // Use the same CAPTCHA action name
        $captchaAction = $this->getCaptchaAction();
        $captchaEnabled = \App\Helpers\CaptchaHelper::shouldShowCaptcha($captchaAction);
        $captchaWidget = \App\Helpers\CaptchaHelper::renderWidget($captchaAction);

        return view('dixlase-users::mypage.profile.appearance', [
            'user' => $request->user(),
            'captchaEnabled' => $captchaEnabled,
            'captchaWidget' => $captchaWidget,
        ]);
    }
}
```

### CSP Compliance

When using save buttons with Alpine.js, an `x-data` scope is required.

```blade
<div x-data="{}">
    <form method="POST" action="{{ route('dixlase-users.profile.update') }}">
        @csrf

        <!-- Form fields -->
        <input type="text" name="name" value="{{ $user->name }}">

        <!-- CAPTCHA widget -->
        @if($captchaEnabled ?? false)
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                {!! $captchaWidget !!}
            </div>
        @endif

        <!-- Save button (using Alpine.js) -->
        <x-form-button
            type="button"
            variant="primary"
            :label="__('common.save')"
            icon="fas fa-save"
            :x-click="'openModal(\'confirmationModal\')'"
        />
    </form>
</div>
```

### Admin Panel Configuration

After implementing CAPTCHA in a plugin, administrators enable it with the following steps:

1. Navigate to Admin Panel > Security Settings > CAPTCHA Settings
2. Find the relevant form in the "Per-Form Settings" section
3. Turn on the checkbox to enable
4. Save settings

**Note:** Plugin forms will not be added to the CAPTCHA settings records until they are enabled in the admin panel.

### CAPTCHA Helper Methods

#### shouldShowCaptcha()

Determines whether CAPTCHA should be displayed for the specified action.

```php
$captchaEnabled = \App\Helpers\CaptchaHelper::shouldShowCaptcha('dixlase-users.user_register');
// Returns true or false
```

#### renderWidget()

Generates the CAPTCHA widget HTML.

```php
$captchaWidget = \App\Helpers\CaptchaHelper::renderWidget('dixlase-users.user_register');
// Returns HTML code (output in Blade as {!! $captchaWidget !!})
```

## Future Extensions

### Adding New Providers

1. Create a new driver class in `app/Captcha/`
2. Implement the `CaptchaDriver` interface
3. Add configuration to `config/captcha.php`
4. Update the admin panel UI

Example: hCaptcha driver

```php
<?php

namespace App\Captcha;

use Illuminate\Http\Request;

class HCaptchaDriver implements CaptchaDriver
{
    // Implement the CaptchaDriver interface
}
```

## Troubleshooting

### Common Issues

1. **reCAPTCHA is not displayed**
   - Verify that the site key is correctly configured
   - Verify that reCAPTCHA is enabled

2. **Verification fails**
   - Verify that the secret key is correctly configured
   - Verify that the domain is registered with reCAPTCHA

3. **Score is too low (v3)**
   - Adjust the minimum score (within the 0.3-0.7 range)
   - Check user behavior patterns

### Checking Logs

reCAPTCHA-related errors are logged in `storage/logs/laravel.log`.

## Security Considerations

1. Protect the secret key properly
2. Combine multiple defense layers (rate limiting, honeypots, etc.)
3. Regularly review the score threshold
4. Monitor logs to identify patterns
