# Beginner's Guide
This guide introduces the Dixlase admin panel — its layout, main sections, and core features. After completing the [Installation Guide](../installation/index.md), use this guide to get familiar with managing your site.

## Accessing the Admin Panel

Open your browser and navigate to:

```
https://your-domain.com/{admin-url}
```

The `{admin-url}` is the path you configured during installation (default: `admin`). Log in with the super administrator credentials you created.

## Dashboard

The dashboard is the landing page after login. It provides an at-a-glance overview of your site's status and activity.

## Main Sections

The admin panel is organized into the following sections, accessible from the sidebar navigation.

### Front (Content Management)

Manage your site's public-facing pages.

| Page | Description |
|------|-------------|
| Index | View and manage all pages |
| Edit | Create or edit individual pages |
| Settings | Configure front-end display options |

### Media

Manage uploaded images, documents, and other files.

| Page | Description |
|------|-------------|
| Index | Browse the media library |
| Upload | Upload new files |
| Settings | Configure media storage and processing options |

### Members

Manage admin users and their access levels.

| Page | Description |
|------|-------------|
| Index | List all members |
| Create / Edit | Add new members or edit existing ones |
| Roles | Configure role-based access permissions |

### Member Roles

Dixlase uses a role hierarchy to control access. Higher-priority roles can manage lower-priority ones.

| Role | Priority | Description |
|------|----------|-------------|
| Super Admin | 10 | Full system access, can manage all settings and users |
| Admin | 9 | Near-full access, can manage most settings |
| Editor | 8 | Can edit and publish content |
| Author | 7 | Can create content, limited publishing |
| Contributor | 6 | Can create drafts, cannot publish |
| Receptionist | 5 | Limited operational access |
| Guest | 1 | Minimal read-only access |

A member can only manage users whose role priority is lower than their own.

### Settings

Comprehensive site configuration, divided into categories.

#### Base Settings

| Page | Description |
|------|-------------|
| Site | Site name, locale, timezone |
| Admin | Admin panel URL and behavior |
| Mail | SMTP and email delivery settings |
| Maintenance | Maintenance mode configuration |
| Mode | Simple vs Advanced admin mode toggle |

#### Security Settings

| Page | Description |
|------|-------------|
| Password | Password policy rules |
| Login Attempts | Failed login lockout thresholds |
| 2FA | Two-factor authentication settings |
| Notifications | Security alert notifications |
| CAPTCHA | Login/form CAPTCHA configuration |
| Session | Session driver, lifetime, encryption |
| CSP | Content Security Policy headers |
| Extensions | Security extension management |
| IP Whitelist | IP-based access restrictions |
| File Integrity | Core file change monitoring |
| Environment | SSL and environment security |

#### Themes

| Page | Description |
|------|-------------|
| Index | View and switch installed themes |
| Add | Install new themes |

#### Plugins

| Page | Description |
|------|-------------|
| Index | View and manage installed plugins |
| Add | Install new plugins |

#### System

| Page | Description |
|------|-------------|
| Cache | Clear and manage application caches |
| Database | Database management and cleanup |
| API | API key and endpoint configuration |
| Logs | Audit logs and file-based logs |
| Info | System information and diagnostics |

### Profile

Each logged-in member can manage their own profile.

| Page | Description |
|------|-------------|
| Basic | Account name, display name, email |
| Password | Change password |
| Appearance | Theme and UI preferences |
| Notifications | Notification preferences |
| 2FA | Enable/disable two-factor authentication |
| 2FA Management | Manage trusted devices and recovery codes |

## Simple Mode vs Advanced Mode

Dixlase offers two admin modes:

| Mode | Description |
|------|-------------|
| **Simple** | Hides advanced settings to reduce complexity. Ideal for everyday content management. |
| **Advanced** | Shows all available settings and features. For experienced administrators. |

You can switch between modes at **Settings > Base > Mode**.

## Tips for New Users

1. **Start with Simple Mode** — Switch to Advanced only when you need specific settings
2. **Enable 2FA early** — Go to Profile > 2FA to secure your account
3. **Explore the media library** — Upload images before creating pages so they're ready to use
4. **Check the settings overview** — Browse [Settings Reference](../settings/index.md) to understand what's configurable
5. **Keep your admin URL private** — Don't share the admin panel URL publicly
