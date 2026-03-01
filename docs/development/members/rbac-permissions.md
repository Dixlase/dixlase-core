# RBAC / Permission Model

## Overview

Dixlase employs Role-Based Access Control (RBAC). Each member is assigned a role, and permissions are granted according to that role.

## Role Hierarchy

| Role | Value | Description |
|------|-------|-------------|
| SUPER_ADMIN | 10 | Super administrator (full permissions) |
| ADMIN | 9 | Administrator |
| EDITOR | 8 | Editor |
| AUTHOR | 7 | Author |
| CONTRIBUTOR | 6 | Contributor |
| RECEPTIONIST | 5 | Receptionist |
| GUEST | 1 | Guest |

Roles are hierarchical: higher roles inherit all permissions of lower roles.

## Permission List

### Dashboard
| Permission | Minimum Role | Description |
|------------|-------------|-------------|
| `dashboard.view` | GUEST | View dashboard |

### Member Management
| Permission | Minimum Role | Description |
|------------|-------------|-------------|
| `members.view` | EDITOR | View members |
| `members.create` | ADMIN | Create members |
| `members.update` | ADMIN | Edit members |
| `members.delete` | SUPER_ADMIN | Delete members |
| `members.manage_roles` | SUPER_ADMIN | Manage roles |

### Settings
| Permission | Minimum Role | Description |
|------------|-------------|-------------|
| `settings.view` | EDITOR | View settings |
| `settings.base` | ADMIN | General settings |
| `settings.security` | SUPER_ADMIN | Security settings |
| `settings.members` | ADMIN | Member settings |
| `settings.system` | SUPER_ADMIN | System settings |
| `settings.api` | SUPER_ADMIN | API settings |

### Plugins
| Permission | Minimum Role | Description |
|------------|-------------|-------------|
| `plugins.view` | EDITOR | View plugins |
| `plugins.install` | SUPER_ADMIN | Install |
| `plugins.uninstall` | SUPER_ADMIN | Uninstall |
| `plugins.enable` | ADMIN | Enable |
| `plugins.disable` | ADMIN | Disable |
| `plugins.settings` | ADMIN | Settings |

### Themes
| Permission | Minimum Role | Description |
|------------|-------------|-------------|
| `themes.view` | EDITOR | View themes |
| `themes.install` | SUPER_ADMIN | Install |
| `themes.uninstall` | SUPER_ADMIN | Uninstall |
| `themes.enable` | ADMIN | Enable |
| `themes.disable` | ADMIN | Disable |
| `themes.settings` | ADMIN | Settings |

### Media
| Permission | Minimum Role | Description |
|------------|-------------|-------------|
| `media.view` | CONTRIBUTOR | View media |
| `media.upload` | AUTHOR | Upload |
| `media.delete` | EDITOR | Delete |

### System
| Permission | Minimum Role | Description |
|------------|-------------|-------------|
| `system.logs_view` | EDITOR | View logs |
| `system.logs_delete` | SUPER_ADMIN | Delete logs |
| `system.cache_clear` | EDITOR | Clear cache |
| `system.maintenance` | SUPER_ADMIN | Maintenance mode |
| `system.backup` | SUPER_ADMIN | Backup |
| `system.restore` | SUPER_ADMIN | Restore |

## Usage

### Permission Checks in Controllers

```php
use App\Enums\Permission;
use App\Services\PermissionService;

class MemberController extends Controller
{
    public function index()
    {
        // Permission check (returns 403 on failure)
        PermissionService::authorize(Permission::MEMBERS_VIEW);

        // Or conditional branching
        if (PermissionService::can(Permission::MEMBERS_CREATE)) {
            // Show the create button
        }
    }
}
```

### Permission Checks via Middleware

```php
// routes/admin.php

// Single permission
Route::get('/members', [MemberController::class, 'index'])
    ->middleware('permission:members.view');

// Multiple permissions (any)
Route::post('/members', [MemberController::class, 'store'])
    ->middleware('permission:members.create,members.update');

// Role check
Route::get('/settings/security', [SecurityController::class, 'index'])
    ->middleware('role:super_admin');
```

### Permission Checks in Blade Templates

```blade
@if(auth()->user()->hasPermission(\App\Enums\Permission::MEMBERS_CREATE))
    <a href="{{ route('admin.members.create') }}">Create Member</a>
@endif

@if(auth()->user()->isSuperAdmin())
    <a href="{{ route('admin.settings.security') }}">Security Settings</a>
@endif
```

### Permission Checks on the Member Model

```php
$member = Member::find(1);

// Permission check
if ($member->hasPermission(Permission::MEMBERS_VIEW)) {
    // ...
}

// Role check
if ($member->isAdmin()) {
    // ...
}

// Get all permissions
$permissions = $member->getPermissions();
```

## Dangerous Permissions

The following permissions are flagged as "dangerous":

- `members.delete` - Delete members
- `members.manage_roles` - Manage roles
- `plugins.install` / `plugins.uninstall`
- `themes.install` / `themes.uninstall`
- `system.backup` / `system.restore`
- `system.maintenance`
- `system.logs_delete`
- `api.keys_delete`
- `webhooks.delete`

These permissions are recorded in the audit log, and mandatory re-authentication will be required for them starting from the beta release.

## Integration with Menu Permissions

> **Note:** The permission system has been refactored.
> See `docs/role-permission-system.md` for details.

The new approach uses the `PermissionRegistry` service to check permissions per menu item.
Default permissions are declared in `config/roles.php`, and only changes made through the admin panel are stored in the `role_permission_overrides` table.

```php
use App\Services\PermissionRegistry;

// Get effective permissions (defaults + overrides merged)
$effective = PermissionRegistry::getEffective('settings.security');

// Check if access is allowed
if (PermissionRegistry::canAccess('settings.security', $member->role)) {
    // ...
}
```

## Middleware Registration

Register middleware in `bootstrap/app.php` or `app/Http/Kernel.php`:

```php
// bootstrap/app.php (Laravel 11+)
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'permission' => \App\Http\Middleware\CheckPermission::class,
        'role' => \App\Http\Middleware\CheckRole::class,
    ]);
})
```

## Related Documentation

- [Permission Settings System](./role-permission-system.md) - Declaration/storage separation architecture, PermissionRegistry, plugin development guide

## Planned for Beta and Beyond

- Mandatory re-authentication for dangerous operations
- Audit logging for permission changes
