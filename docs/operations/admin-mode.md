# Simple Mode and Advanced Mode

Dixlase has two admin panel modes. You can switch modes from **Global Settings > Basic Settings > Mode Settings**.

## Simple Mode (Default)

Designed for day-to-day operations. Security settings are automatically configured with safe defaults, and high-risk settings are hidden to prevent accidental misconfiguration.

## Advanced Mode

All features and settings are accessible. Intended for experienced administrators who need full control.

## What changes between modes

### Always available (both modes)

| Feature | Notes |
|---------|-------|
| Dashboard | Full access |
| Front Page Management | Create, edit, reset |
| Media upload and browsing | Upload and manage files |
| Profile settings | Account info, password, appearance, 2FA |
| Basic Settings (site info) | Site name, description, OGP |
| Maintenance mode | Enable/disable maintenance |
| Mode settings | Switch between Simple and Advanced |
| Theme management | Install, switch, remove themes |
| Plugin management | Install, enable, disable plugins |
| Cache management | Clear application caches |

### Limited in Simple Mode (Partial)

These features are available but some detailed parameters are auto-configured with safe defaults.

| Feature | What you can do | What is auto-configured |
|---------|----------------|------------------------|
| Media settings | Choose allowed file types, set size limits | MIME validation, SVG sanitization, ZIP security checks are always ON |
| Mail settings | Set admin email address | SMTP server details (guided to Advanced Mode) |
| Login security | Configure login notifications | Attempt limits, lockout duration, lockout notifications |
| Two-factor auth | Enable/disable 2FA and Passkeys | Device limits, expiration, recovery code settings |
| CAPTCHA | Choose provider, enter API keys, toggle per form | New plugin forms auto-enable CAPTCHA |

### Hidden in Simple Mode

These settings are hidden and auto-configured. Switch to Advanced Mode to change them.

| Setting | Auto-configured value |
|---------|----------------------|
| Password policy | Min 8 chars, uppercase + digits + symbols required, breach check ON |
| Session lifetime | 120 minutes |
| CSP (Content Security Policy) | ON, standard mode, warn-only |
| Error notifications | ON, critical level and above |
| Environment | Production, debug OFF |
| IP access control | Disabled |
| Admin panel URL | Read-only (guided to Advanced Mode) |
| Member role permissions | Default template maintained |
| Database cleanup | Manual only in Advanced Mode |
| API management | Disabled |

### View-only in Simple Mode (ReadOnly)

You can see these settings but cannot change them.

| Setting | What is shown |
|---------|--------------|
| Security overview | Summary of all security settings |
| Extension security | Current security preset |
| Log management | View audit and file logs (no export) |
| System information | Server environment, PHP, database info |

## Switching modes

- **Simple to Advanced**: All features unlock immediately. Auto-configured values remain until you change them.
- **Advanced to Simple**: A confirmation dialog appears. Hidden settings will be overwritten with safe defaults.
