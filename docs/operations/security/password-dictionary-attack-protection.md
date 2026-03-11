# Password Dictionary Attack Protection

Dixlase's password dictionary attack protection feature uses the Have I Been Pwned API to check whether a password has been found in breach databases, preventing the use of compromised passwords.

## Overview

This feature consists of the following components:

- **PwnedPasswordTrait**: Have I Been Pwned API integration functionality
- **PasswordSecurityHelper**: Password validation helper class
- **NotPwnedPassword**: Validation rule
- **Security settings**: Enable/disable toggle in the admin panel

## Configuration

### 1. Enabling in Security Settings

Enable the feature in Admin Panel > Security Settings > Password Dictionary Attack Protection Settings.

```
Dictionary attack protection: Enabled/Disabled
```

### 2. Database Configuration

The setting is managed via the `pwned_password_check_enabled` key in the `security_settings` table.

```php
// Retrieve the setting
$enabled = SecuritySetting::get('pwned_password_check_enabled', false);

// Save the setting
SecuritySetting::set('pwned_password_check_enabled', true);
```

## Usage

### 1. Using as a Validation Rule

```php
use App\Rules\NotPwnedPassword;

// Use in a form request class
public function rules(): array
{
    return [
        'password' => ['required', 'string', 'min:8', new NotPwnedPassword()],
    ];
}

// Use with a custom setting key
public function rules(): array
{
    return [
        'password' => ['required', 'string', 'min:8', NotPwnedPassword::using('custom_setting_key')],
    ];
}
```

### 2. Direct Check Using the Trait

```php
use App\Traits\PwnedPasswordTrait;

class YourController extends Controller
{
    use PwnedPasswordTrait;

    public function checkPassword($password)
    {
        // Check if the password has been breached
        $result = $this->checkPwnedPassword($password);

        if ($result['is_pwned']) {
            // Handle breached password
            return "This password has been breached {$result['count']} times";
        }

        return "Password is safe";
    }
}
```

### 3. Comprehensive Check Using the Helper Class

```php
use App\Helpers\PasswordSecurityHelper;

// Basic password validation
$result = PasswordSecurityHelper::validatePassword($password);

if (!$result['is_valid']) {
    foreach ($result['errors'] as $error) {
        echo $error . "\n";
    }
}

// Combined password strength and dictionary attack protection check
$strengthRequirements = [
    'min_length' => 8,
    'require_uppercase' => true,
    'require_symbol' => false,
];

$result = PasswordSecurityHelper::comprehensivePasswordCheck(
    $password,
    $strengthRequirements
);

if (!$result['is_valid']) {
    // Strength errors
    foreach ($result['strength_errors'] as $error) {
        echo "Strength error: " . $error . "\n";
    }

    // Security errors (dictionary attack protection)
    foreach ($result['security_errors'] as $error) {
        echo "Security error: " . $error . "\n";
    }
}
```

## Usage in User Management Plugins

### 1. Using a Custom Settings System

```php
use App\Traits\PwnedPasswordTrait;

class UserPasswordController extends Controller
{
    use PwnedPasswordTrait;

    public function validateUserPassword($password)
    {
        // Use a custom setting key
        $safetyCheck = $this->validatePasswordSafety($password, 'user_pwned_password_check_enabled');

        if (!$safetyCheck['is_safe']) {
            throw new ValidationException($safetyCheck['message']);
        }
    }
}
```

### 2. Using the Validation Rule

```php
use App\Rules\NotPwnedPassword;

class UserRegistrationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
                'min:8',
                // Specify the user-specific setting key
                NotPwnedPassword::using('user_pwned_password_check_enabled')
            ],
        ];
    }
}
```

## API Specification

### Have I Been Pwned API

This feature uses the Have I Been Pwned API v3:

- **Endpoint**: `https://api.pwnedpasswords.com/range/{hash_prefix}`
- **Method**: k-Anonymity (the full password hash is never sent)
- **Privacy**: The password itself is never transmitted; only the first 5 characters of its SHA-1 hash are used

### Security

1. **Privacy protection**: The password itself is never sent externally
2. **k-Anonymity**: Only a portion of the hash is transmitted to protect privacy
3. **Error handling**: On API errors, the password is allowed by default
4. **Timeout**: 10-second timeout setting

## Configuration Options

### NotPwnedPassword Rule

```php
// Default configuration
new NotPwnedPassword()

// Custom setting key
new NotPwnedPassword('custom_setting_key')

// Fail validation on API error
new NotPwnedPassword('pwned_password_check_enabled', false)

// Static factory method
NotPwnedPassword::using('custom_key', false)
```

### PwnedPasswordTrait Methods

```php
// Password check
$result = $this->checkPwnedPassword($password);
// Returns: ['is_pwned' => bool, 'count' => int, 'error' => string|null]

// Check setting
$enabled = $this->isPwnedPasswordCheckEnabled($settingKey);

// Safety check
$result = $this->validatePasswordSafety($password, $settingKey);
// Returns: ['is_safe' => bool, 'message' => string, 'pwned_info' => array]
```

## Error Handling

### API Errors

When an API error occurs, the password is allowed by default:

```php
// Change behavior on API error
$rule = new NotPwnedPassword('pwned_password_check_enabled', false); // Fail on error
```

### Log Output

Errors are automatically logged:

```php
// Warning level (API failure)
Log::warning('Have I Been Pwned API request failed', $context);

// Error level (exception thrown)
Log::error('Pwned password check failed', $context);
```

## Translations

### Japanese (lang/ja/validation.php)

```php
'pwned_password_found' => 'This password has been found :count times in data breaches and is not safe. Please choose a different password.',
'pwned_password_api_error' => 'An error occurred while checking password safety, but the password has been accepted.',
```

### English (lang/en/validation.php)

```php
'pwned_password_found' => 'This password has been found :count times in data breaches and is not safe. Please choose a different password.',
'pwned_password_api_error' => 'An error occurred while checking password safety, but the password has been accepted.',
```

## Integrated Locations

This feature is already integrated in the following locations:

1. **Member creation/editing** (`AdminSettingsMemberStoreRequest`)
2. **Password change in profile settings** (`AdminProfileController`)

## Performance Considerations

1. **API calls**: Network latency may occur since an external API is called
2. **Timeout**: A 10-second timeout is configured
3. **Caching**: Caching is not currently implemented, but can be added as needed

## Troubleshooting

### Common Issues

1. **API connection error**: Check your network connection
2. **Settings not taking effect**: Clear your browser cache
3. **Validation not working**: Verify that the setting is enabled

### Debugging

```php
// Retrieve debug information
$helper = new PasswordSecurityHelper();
$settings = $helper->getPwnedPasswordSettings();
var_dump($settings);
```

## Future Enhancements

1. **Caching**: Cache check results
2. **Statistics**: Statistics on breached password detections
3. **Custom API**: Support for custom breach password databases
4. **Batch processing**: Bulk checking of existing passwords
