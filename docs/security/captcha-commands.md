# CAPTCHA Management Commands Guide

## Overview

Dixlase provides two primary commands for managing the CAPTCHA system:

1. **`dls:admin:captcha-failover`** - CAPTCHA configuration and failover management (normal operations)
2. **`dls:admin:captcha-bypass`** - Emergency CAPTCHA bypass (disaster recovery only)

These commands are essential tools that allow administrators to access the system during CAPTCHA provider outages or emergencies.

---

## 1. CAPTCHA Failover Management Command

### Command: `dls:admin:captcha-failover`

Manages CAPTCHA provider status checks, switching, and failover configuration.

### Use Cases

- **Provider management during normal operations**
- Google reCAPTCHA is slow -> switch to Cloudflare Turnstile
- Provider status checks
- Enabling/disabling automatic failover

---

### 1.1 Status Check

Check the current status of CAPTCHA providers.

```bash
php artisan dls:admin:captcha-failover status
```

**Example output:**
```
[CAPTCHA Failover Status]

+------------------+----------------------+
| Setting          | Value                |
+------------------+----------------------+
| Primary Provider | Cloudflare Turnstile |
| Active Provider  | Cloudflare Turnstile |
| Failed Over      | No                   |
| Auto Failover    | Enabled              |
+------------------+----------------------+

[Configured Providers]
+--------------------------------+------------+---------+----------+----------+
| Provider                       | Configured | Enabled | Verified | Failures |
+--------------------------------+------------+---------+----------+----------+
| Google reCAPTCHA               | No         | No      | No       | 0        |
| Google reCAPTCHA Enterprise    | No         | No      | No       | 0        |
| Cloudflare Turnstile           | Yes        | No      | No       | 0        |
+--------------------------------+------------+---------+----------+----------+
```

**Displayed information:**
- **Primary Provider**: The configured main provider
- **Active Provider**: The currently active provider
- **Failed Over**: Whether failover is in progress
- **Auto Failover**: Whether automatic failover is enabled/disabled
- **Configured Providers**: Configuration status of each provider

**Icon legend:**
- Green: Currently active
- Yellow: Configured, enabled, and verified (switchable)
- White: Configured but not verified
- Red: Not configured

---

### 1.2 List Available Providers

Displays the list of CAPTCHA providers supported by the system.

```bash
php artisan dls:admin:captcha-failover providers
```

**Example output:**
```
[Available Providers]

  - google: Google reCAPTCHA
  - google_enterprise: Google reCAPTCHA Enterprise
  - turnstile: Cloudflare Turnstile
```

---

### 1.3 Switch Providers

Switch the CAPTCHA provider to a different one.

#### Temporary switch (recommended)

```bash
php artisan dls:admin:captcha-failover switch --provider=turnstile
```

A temporary switch remains in effect until the next default reset.

#### Permanent switch

```bash
php artisan dls:admin:captcha-failover switch --provider=turnstile --permanent
```

A permanent switch displays a confirmation prompt:

```
 Permanently switch to Cloudflare Turnstile? (yes/no) [no]:
 > yes

Switched to Cloudflare Turnstile (permanent).
```

**Notes:**
- The target provider must be configured, enabled, and verified
- Attempting to switch to an unconfigured provider will result in an error

**Error example:**
```bash
php artisan dls:admin:captcha-failover switch --provider=google
```
```
Switch failed. Please verify the provider is configured, enabled, and verified.
```

---

### 1.4 Reset to Default

Cancel a temporary switch and revert to the default provider.

```bash
php artisan dls:admin:captcha-failover reset
```

**Output:**
```
Reset to default provider.
```

---

### 1.5 Auto-Failover Configuration

Configure the automatic failover feature that switches to another provider when the current CAPTCHA provider fails.

#### Enable auto-failover

```bash
php artisan dls:admin:captcha-failover --auto-failover=on
```

**Output:**
```
Auto failover set to Enabled.
```

#### Disable auto-failover

```bash
php artisan dls:admin:captcha-failover --auto-failover=off
```

**Output:**
```
Auto failover set to Disabled.
```

---

### 1.6 Command Options Reference

| Option | Description | Default |
|--------|-------------|---------|
| `action` | Action to perform (status / switch / reset / providers) | - |
| `--provider` | Target provider name for switching | - |
| `--permanent` | Perform a permanent switch | false |
| `--auto-failover` | Enable/disable auto-failover (on/off) | - |

---

## 2. Emergency CAPTCHA Bypass Command (Break Glass)

### Command: `dls:admin:captcha-bypass`

**Warning: This command is for emergencies only**

Use this only when all CAPTCHA providers are down and administrators cannot log in.

### Use Cases

- **Disaster recovery only**
- All CAPTCHA providers are down
- Administrators cannot log in
- Emergency access is required

### Security Considerations

1. **Minimize usage**: Should only be needed once a year at most
2. **Reason required**: All usage is recorded in the audit log
3. **Time limit**: Maximum of 60 minutes
4. **Confirmation prompt**: Activation always requires confirmation

---

### 2.1 Check Bypass Status

Check the current bypass state and history.

```bash
php artisan dls:admin:captcha-bypass status
```

**Example output (inactive):**
```
[CAPTCHA Bypass Status]

Bypass is inactive (normal operation)

[Recent Bypass History]
+---------------------+------------------------+-------------+--------------------+
| Time                | Action                 | Scope       | Reason             |
+---------------------+------------------------+-------------+--------------------+
| 2026-01-02 00:35:40 | captcha_bypass_enabled | admin_login | Emergency response |
+---------------------+------------------------+-------------+--------------------+
```

**Example output (active):**
```
[CAPTCHA Bypass Status]

Bypass is ACTIVE (security risk)
+-------------------+---------------------+
| Field             | Value               |
+-------------------+---------------------+
| Scope             | admin_login         |
| Reason            | All providers down  |
| Expires At        | 2026-01-02 01:00:00 |
| Remaining Minutes | 45 minutes          |
| Enabled At        | 2026-01-02 00:15:00 |
+-------------------+---------------------+
```

---

### 2.2 Enable Bypass

Temporarily bypass CAPTCHA verification.

#### Basic usage (default: 10 minutes, admin_login only)

```bash
php artisan dls:admin:captcha-bypass enable --reason="All providers down"
```

#### Usage with options

```bash
php artisan dls:admin:captcha-bypass enable \
  --minutes=30 \
  --scope=all \
  --reason="Both Google and Cloudflare are down, emergency maintenance required"
```

**Confirmation prompt:**
```
Warning: CAPTCHA bypass poses a security risk.

Settings: 30 minutes, scope: all, reason: Both Google and Cloudflare are down, emergency maintenance required

 Enable CAPTCHA bypass? (yes/no) [no]:
 > yes

CAPTCHA bypass enabled for 30 minutes (expires at 2026-01-02 01:00:00).
```

**Audit log:**
Bypass activation is automatically recorded in the audit log:
- Action: `captcha_bypass_enabled`
- Category: `security`
- Severity: `critical`
- Context: duration, scope, reason, expiration time

---

### 2.3 Disable Bypass

Manually disable an active bypass.

```bash
php artisan dls:admin:captcha-bypass disable
```

**Output (when bypass is active):**
```
CAPTCHA bypass has been disabled.
```

**Output (when bypass is already inactive):**
```
CAPTCHA bypass is not currently active.
```

**Audit log:**
Bypass deactivation is also recorded in the audit log:
- Action: `captcha_bypass_disabled`
- Category: `security`
- Severity: `warning`

---

### 2.4 Command Options Reference

| Option | Description | Default | Constraints |
|--------|-------------|---------|-------------|
| `action` | Action to perform (enable / disable / status) | status | - |
| `--minutes` | Bypass duration in minutes | 10 | Maximum 60 minutes |
| `--scope` | Bypass scope (admin_login / all) | admin_login | - |
| `--reason` | Reason for bypass (required for enable) | - | Required |

**Scope descriptions:**
- `admin_login`: Bypass admin panel login only (recommended)
- `all`: Bypass all CAPTCHA verification (more risky)

---

## 3. Usage Examples and Best Practices

### 3.1 Normal Operation Scenarios

#### Scenario 1: Google reCAPTCHA is slow

```bash
# 1. Check current status
php artisan dls:admin:captcha-failover status

# 2. Temporarily switch to Cloudflare Turnstile
php artisan dls:admin:captcha-failover switch --provider=turnstile

# 3. Reset to default once the issue is resolved
php artisan dls:admin:captcha-failover reset
```

#### Scenario 2: Provider change during scheduled maintenance

```bash
# Permanently switch to Cloudflare Turnstile
php artisan dls:admin:captcha-failover switch --provider=turnstile --permanent
```

---

### 3.2 Emergency Scenarios

#### Scenario 3: All CAPTCHA providers are down

```bash
# 1. Check status (just in case)
php artisan dls:admin:captcha-failover status

# 2. Enable emergency bypass (with minimum duration)
php artisan dls:admin:captcha-bypass enable \
  --minutes=15 \
  --scope=admin_login \
  --reason="Both Google and Cloudflare are down, emergency response required"

# 3. Log into the admin panel and address the issue

# 4. Disable bypass once the issue is resolved
php artisan dls:admin:captcha-bypass disable

# 5. Review history
php artisan dls:admin:captcha-bypass status
```

---

### 3.3 Best Practices

#### Failover Management

1. **Regular status checks**
   ```bash
   # Run weekly
   php artisan dls:admin:captcha-failover status
   ```

2. **Enable auto-failover**
   ```bash
   php artisan dls:admin:captcha-failover --auto-failover=on
   ```

3. **Configure multiple providers**
   - Google reCAPTCHA
   - Cloudflare Turnstile
   - Keep at least two providers configured and verified

#### Bypass Management

1. **Minimize usage**
   - Only for emergencies that occur once a year at most
   - Consider whether normal provider switching can resolve the issue first

2. **Principle of least privilege**
   - Use `--scope=admin_login` (avoid `all`)
   - Set `--minutes` to the minimum necessary

3. **Always record a reason**
   - Provide a detailed reason in `--reason`
   - Ensures traceability through audit logs

4. **Always disable after use**
   - Disable manually rather than waiting for automatic expiration
   - Set up alerts to avoid forgetting to disable

---

## 4. Troubleshooting

### 4.1 Provider Switch Fails

**Error:**
```
Switch failed. Please verify the provider is configured, enabled, and verified.
```

**Causes and solutions:**
1. Provider is not configured
   - Configure it in Admin Panel > Security Settings > CAPTCHA Settings
2. Provider is not enabled
   - Enable it in the admin panel
3. Provider is not verified
   - Run a test in the admin panel

### 4.2 Bypass Cannot Be Enabled

**Error:**
```
Reason for enabling bypass is required.
```

**Solution:**
```bash
# Always specify the --reason option
php artisan dls:admin:captcha-bypass enable --reason="Emergency response"
```

### 4.3 Command Not Found

**Error:**
```
Command "dls:admin:captcha" not found.
```

**Solution:**
```bash
# Use the correct command name
php artisan dls:admin:captcha-failover status

# or
php artisan dls:admin:captcha-bypass status
```

---

## 5. Security Considerations

### 5.1 Audit Logs

All CAPTCHA management operations are recorded in the audit log:

**Recorded information:**
- Execution date and time
- Executor (for CLI operations: `triggered_by: cli`)
- Action (enable, disable, switch, etc.)
- Details (provider name, reason, duration, etc.)

**How to check logs:**
```bash
# Check the audit log table
php artisan tinker
>>> \App\Models\AuditLog::where('action', 'like', 'captcha%')->latest()->get();
```

### 5.2 Access Control

**Recommendations:**
1. Restrict command execution to SUPER_ADMIN only
2. Strictly manage SSH access to the server
3. Regularly review execution history

### 5.3 Notification Settings

**Recommended configuration:**
1. Slack/email notifications when bypass is enabled
2. Warning notifications before automatic expiration
3. Notifications on abnormal failover events

---

## 6. Frequently Asked Questions (FAQ)

### Q1: What is the difference between `captcha-failover` and `captcha-bypass`?

**A:**
- **`captcha-failover`**: Provider management during normal operations (safe)
- **`captcha-bypass`**: Emergency CAPTCHA disabling (risky)

### Q2: What is the maximum bypass duration?

**A:** The maximum is 60 minutes. Extended bypass periods are not recommended for security reasons.

### Q3: What if I cannot log in during a bypass?

**A:**
1. Verify that the bypass is actually active: `php artisan dls:admin:captcha-bypass status`
2. Check that the scope is correct (`admin_login` vs `all`)
3. Clear the cache: `php artisan cache:clear`

### Q4: How does auto-failover work?

**A:** When a provider fails consecutively, the system automatically switches to the next available provider.

### Q5: How do I run these commands in a Docker environment?

**A:**
```bash
# Run inside the Docker container
docker exec dixlase-laravel.test-1 php artisan dls:admin:captcha-failover status
docker exec dixlase-laravel.test-1 php artisan dls:admin:captcha-bypass status
```

---

## 7. Related Documentation

- [CAPTCHA Implementation Guide](./captcha-usage.md) - Basic usage of the CAPTCHA feature
- [Security Settings Guide](./security-settings.md) - CAPTCHA configuration in the admin panel
- [Audit Logs Guide](./audit-logs.md) - How to review audit logs

---

## 8. Support

If you encounter issues, please contact support with the following information:

1. The command you executed
2. The error message
3. Output of `php artisan dls:admin:captcha-failover status`
4. Relevant logs from `storage/logs/dixlase.log`
5. The corresponding audit log entries

---

**Last updated**: 2026-01-02
**Version**: Dixlase Alpha
