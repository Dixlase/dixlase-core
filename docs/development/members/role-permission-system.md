# Dixlase Permission Settings System

## Overview

The Dixlase permission settings system adopts a design that **separates declaration (defaults) from storage (overrides)**.

- **Default permissions**: Declared in `config/roles.php` (core) or `plugins/{slug}/config/roles.php` (plugins)
- **Overrides**: Stored as diffs in the `role_permission_overrides` table only when modified through the admin panel
- **Effective permissions**: Computed at runtime by merging defaults with overrides

## Benefits

1. **Plugins don't modify the database**: No need to insert records into core DB tables during installation
2. **Easy uninstallation**: Minimal permission record cleanup when removing a plugin
3. **Alignment with the capability-declaration model**: Lowers the barrier for "explicit permission required" decisions
4. **Reset to defaults**: Simply delete the override record

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Admin Panel (UI)                          │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │  Permission Settings Screen (roles.blade.php)           │ │
│  │  - Displays default values                              │ │
│  │  - Saves as override only when modified                 │ │
│  │  - "Reset to default" = delete override                 │ │
│  └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              PermissionRegistry (Service)                    │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │  getEffective($menuKey)                                  │ │
│  │  - Retrieves defaults from config/roles.php              │ │
│  │  - Retrieves overrides from role_permission_overrides    │ │
│  │  - Merges and returns effective permissions              │ │
│  └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
          │                                    │
          ▼                                    ▼
┌─────────────────────┐          ┌─────────────────────────────┐
│  config/roles.php   │          │  role_permission_overrides   │
│  (Default decl.)    │          │  (Diffs only)                │
│                     │          │                              │
│  - Core features    │          │  - source_type: core/plugin  │
│  - Immutable        │          │  - source_id: plugin slug    │
│                     │          │  - menu_key                  │
│                     │          │  - access_roles              │
│                     │          │  - view_roles                │
└─────────────────────┘          └──────────────────────────────┘
```

## Permission Types

| Permission | Description | Usage |
|------------|-------------|-------|
| `access_roles` | Edit permission (write) | Create, update, delete operations |
| `view_roles` | View permission (read) | Menu display, list viewing |

## Permission Values (MemberRole)

| Value | Constant | Description |
|-------|----------|-------------|
| 10 | SUPER_ADMIN | Super administrator only |
| 9 | ADMIN | Administrator or above |
| 8 | EDITOR | Editor or above |
| 7 | AUTHOR | Author or above |
| 6 | CONTRIBUTOR | Contributor or above |
| 5 | RECEPTIONIST | Receptionist or above |
| 1 | GUEST | Everyone |

## Usage

### Defining Default Permissions for Core Features

`config/roles.php`:

```php
<?php

use App\Enums\MemberRole;

return [
    'permissions' => [
        'dashboard' => [
            'access_roles' => MemberRole::CONTRIBUTOR->value,
            'view_roles' => MemberRole::GUEST->value,
        ],
        'settings.base.index' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        // ...
    ],
];
```

### Defining Default Permissions for Plugins

`plugins/{slug}/config/roles.php`:

```php
<?php

use App\Enums\MemberRole;

return [
    'permissions' => [
        'settings.inquiry.index' => [
            'access_roles' => MemberRole::EDITOR->value,
            'view_roles' => MemberRole::EDITOR->value,
        ],
        'settings.inquiry.settings' => [
            'access_roles' => MemberRole::ADMIN->value,
            'view_roles' => MemberRole::ADMIN->value,
        ],
    ],
];
```

### Permission Checks (Controller)

```php
use App\Services\PermissionRegistry;
use App\Enums\MemberRole;

// Get effective permissions
$effective = PermissionRegistry::getEffective('settings.base.index');
// => ['access_roles' => 10, 'view_roles' => 10, 'is_overridden' => false, ...]

// Check access permission
$canAccess = PermissionRegistry::canAccess('settings.base.index', $user->role);

// Check view permission
$canView = PermissionRegistry::canView('settings.base.index', $user->role);

// Check plugin feature permission
$canAccessPlugin = PermissionRegistry::canAccessPlugin('DixlaseInquiry', 'settings.inquiry.index', $user->role);
```

### Permission Checks (via AdminHelper)

```php
use App\Helpers\AdminHelper;

// Menu access permission
if (AdminHelper::canAccessMenu('settings.base.index')) {
    // Access allowed
}

// Menu view permission
if (AdminHelper::canViewMenu('settings.base.index')) {
    // Viewing allowed
}

// Menu edit permission
if (AdminHelper::canEditMenu('settings.base.index')) {
    // Editing allowed
}

// Plugin menu permission
if (AdminHelper::canAccessPluginMenu('DixlaseInquiry', 'settings.inquiry.index')) {
    // Access allowed
}
```

### Managing Overrides

```php
use App\Models\RolePermissionOverride;
use App\Services\PermissionRegistry;

// Set a core feature override
RolePermissionOverride::setCoreOverride(
    'settings.base.index',
    MemberRole::ADMIN->value,  // access_roles
    MemberRole::ADMIN->value,  // view_roles
    auth()->id()               // updated_by
);

// Set a plugin feature override
RolePermissionOverride::setPluginOverride(
    'DixlaseInquiry',
    'settings.inquiry.index',
    MemberRole::EDITOR->value,
    MemberRole::EDITOR->value,
    auth()->id()
);

// Delete override (reset to default)
RolePermissionOverride::resetCoreOverride('settings.base.index');
RolePermissionOverride::resetPluginOverride('DixlaseInquiry', 'settings.inquiry.index');

// Clear cache
PermissionRegistry::clearCache();
```

## Plugin Developer Guide

### 1. Create config/roles.php

Create `config/roles.php` in your plugin directory and define the default permissions.

```php
<?php

use App\Enums\MemberRole;

return [
    'permissions' => [
        // Menu keys correspond to the nav structure in config/admin.php
        'settings.myplugin.index' => [
            'access_roles' => MemberRole::EDITOR->value,
            'view_roles' => MemberRole::EDITOR->value,
        ],
    ],
];
```

### 2. Menu Key Naming Convention

- Plugin menu keys correspond to the `nav` structure in `config/admin.php`
- Example: `settings.{pluginSlug}.index`, `settings.{pluginSlug}.create`

### 3. Registration in ServiceProvider (Optional)

To register permissions dynamically, use `PermissionRegistry::registerPlugin()` in your ServiceProvider.

```php
use App\Services\PermissionRegistry;

public function boot(): void
{
    PermissionRegistry::registerPlugin('MyPlugin', [
        'settings.myplugin.index' => [
            'access_roles' => MemberRole::EDITOR->value,
            'view_roles' => MemberRole::EDITOR->value,
        ],
    ]);
}
```

### 4. Alignment with the Capability-Declaration Model

With this approach, plugins **only declare default permissions** without modifying the database.
This means that having a permission settings feature no longer becomes a psychological barrier
in the defense-in-depth model's "explicit permission required" evaluation.

## Database Table

### role_permission_overrides

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| source_type | varchar(20) | `core` or `plugin` |
| source_id | varchar(100) | null for core, slug for plugin |
| menu_key | varchar(255) | Menu key |
| access_roles | tinyint | Edit permission |
| view_roles | tinyint | View permission |
| updated_by | bigint | member_id of the updater |
| created_at | timestamp | Created at |
| updated_at | timestamp | Updated at |

**Unique constraint**: `source_type` + `source_id` + `menu_key`

## Orphan Override Management

Overrides may remain after a plugin is uninstalled.

```php
// Detect orphan overrides
$activePlugins = ['DixlaseInquiry', 'DixlasePages'];
$orphans = RolePermissionOverride::findOrphanOverrides($activePlugins);

// Delete orphan overrides
$deletedCount = RolePermissionOverride::deleteOrphanOverrides($activePlugins);
```

## Related Documentation

- [RBAC / Permission Model](./rbac-permissions.md) - Role hierarchy, Permission Enum, permission check usage

## Related Source Files

- `config/roles.php` - Core default permission definitions
- `app/Models/RolePermissionOverride.php` - Override model
- `app/Services/PermissionRegistry.php` - Permission registry service
- `app/Services/PermissionService.php` - Permission service
- `app/Helpers/AdminHelper.php` - Admin helper
- `app/Http/Controllers/Admin/Members/AdminMemberRolesController.php` - Permission settings controller
- `resources/views/admin/members/settings/roles.blade.php` - Permission settings screen
