# Post-Install Checklist

> **[日本語版はこちら](../ja/installation/post-install.md)**

After completing the installation wizard, follow this checklist to verify your installation and secure your site.

## Verification

### 1. Admin Login

Log in to the admin panel using the credentials you created during installation:

```
https://your-domain.com/{admin-url}
```

Confirm that the dashboard loads without errors.

### 2. Email Delivery

If you skipped the mail tests during installation, verify email delivery now:

1. Go to **Settings > Base > Mail**
2. Update SMTP settings if needed
3. Use the test email feature to confirm delivery

Email is essential for password resets, two-factor authentication codes, and security notifications.

### 3. Storage Symlink

Verify the public storage link exists:

```bash
php artisan storage:link
```

This is necessary for serving uploaded media files.

## Security Hardening

Dixlase includes a comprehensive set of security features. Review and configure these after installation:

### Authentication

| Feature | Location | Description |
|---------|----------|-------------|
| Two-Factor Authentication | Settings > Security > 2FA | Enable TOTP or email-based 2FA for admin accounts |
| Password Policies | Settings > Security > Password | Minimum length, complexity, expiration rules |
| Login Attempt Limits | Settings > Security > Login Attempts | Lockout after failed login attempts |
| CAPTCHA | Settings > Security > CAPTCHA | Add CAPTCHA to login and registration forms |

### Access Control

| Feature | Location | Description |
|---------|----------|-------------|
| IP Whitelist/Blacklist | Settings > Security > IP Whitelist | Restrict admin access by IP address |
| Session Settings | Settings > Security > Session | Session lifetime, encryption, driver |
| Admin URL | Settings > Base > Admin | Change the admin panel URL path |

### Content Security

| Feature | Location | Description |
|---------|----------|-------------|
| CSP (Content Security Policy) | Settings > Security > CSP | Configure HTTP security headers |
| File Integrity Monitoring | Settings > Security > File Integrity | Detect unauthorized file changes |
| SSL / Force HTTPS | Settings > Security > Environment | Enforce HTTPS connections |

## Recommended Immediate Actions

1. **Enable 2FA** for all admin accounts — this is the single most effective security measure
2. **Change the admin URL** from the default `admin` to a custom path
3. **Set up IP restrictions** if admin access should be limited to specific networks
4. **Review CSP settings** and enable strict mode when ready
5. **Configure session encryption** for sensitive environments

## Performance

### Cache Configuration

For production environments:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Queue Worker

If you haven't set up a queue worker yet, see the [Manual Installation](manual.md#8-post-installation) guide for systemd configuration.

## Next Steps

- [Beginner's Guide](../beginners-guide/index.md) - Learn the admin panel layout and core features
- [Operations Guide](../operations/index.md) - Ongoing maintenance and security management
- [Settings Reference](../settings/index.md) - Detailed configuration reference
