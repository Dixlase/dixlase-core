# Dixlase Documentation

> **[日本語版はこちら](ja/index.md)**

Welcome to the Dixlase CMS documentation. This documentation covers the architecture, features, and development guides for the Dixlase platform.

## Settings

### Base

- [Admin Mode Specification](settings/base/admin-mode-specification.md) - Admin mode feature details and behavior

### Security

- [CAPTCHA Usage](settings/security/captcha-usage.md) - CAPTCHA integration guide
- [CSP Guide](settings/security/csp-guide.md) - Content Security Policy complete guide
- [File Integrity Check](settings/security/file-integrity-check.md) - Core file tampering detection
- [Login Notification System](settings/security/login-notification-system.md) - Login notification architecture
- [Password Dictionary Attack Protection](settings/security/password-dictionary-attack-protection.md) - Have I Been Pwned integration
- [Safe Mode Guide](settings/security/safe-mode-guide.md) - Multi-level safe mode for crash recovery
- [2FA Guide](settings/security/two-factor-authentication-guide.md) - Two-factor authentication implementation guide

### System

- [Database Cleanup](settings/system/database-cleanup.md) - Database maintenance and cleanup

## API

- [Events API](api-reference/events.md) - Event system specification
- [Translation API](api-reference/translation.md) - Translation system specification
- [Webhooks](api-reference/webhooks.md) - Webhook integration guide

## Authentication

- [Identifier Check](auth/identifier-check-usage.md) - Login identifier verification
- [Login Lockout](auth/login-lockout-usage.md) - Brute-force protection with login lockout

## Components

- [Admin Pagination](components/admin-pagination.md) - Pagination component usage
- [Components Styling Guide](components/components-styling-guide.md) - Component styling conventions
- [Content File Storage](components/content-file-storage.md) - File-based content storage guide
- [Media Selector](components/media-selector.md) - Media selector modal usage
- [Save Button](components/save-button-usage.md) - Save button and modal variants

## Members

- [RBAC Permissions](members/rbac-permissions.md) - Role-based access control model
- [Role Permission System](members/role-permission-system.md) - Permission settings system design

## Plugins

- [Permission Guidelines](plugins/permission-guidelines.md) - Plugin permission framework guidelines

## Security

- [Alpine.js CSP Coding Rules](security/alpine-csp-coding-rules.md) - CSP-compatible Alpine.js patterns
- [CAPTCHA Commands](security/captcha-commands.md) - CAPTCHA management CLI commands
- [Emergency Lockdown](security/emergency-lockdown.md) - Emergency lockdown system
- [Security Settings Registry](security/security-settings-registry.md) - Unified security settings management

## System

- [API Signature Specification](system/api-signature-spec.md) - HMAC-SHA256 API signature spec
- [Audit Log Integrity](system/audit-log-integrity.md) - Hash chain tamper detection
- [Audit Log Usage](system/audit-log-usage.md) - Audit logging API guide
- [Backup](system/backup.md) - Backup system commands
- [Deployment](system/deployment.md) - Multi-stage deployment system

## Two-Factor Authentication

- [2FA Architecture](two-factor/two-factor-authentication-architecture.md) - Technical specifications
- [2FA UI Components](two-factor/two-factor-ui-components.md) - Frontend components
