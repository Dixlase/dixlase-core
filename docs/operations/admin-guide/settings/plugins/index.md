# Plugin Management

Manage installed plugins, add new plugins, and enable or disable plugins.

## Plugin Master

View all installed and available plugins:

- **Installed plugins** — Plugins currently in the `plugins/` directory. You can enable, disable, or remove them.
- **Add new plugins** — Upload a plugin ZIP file or place it in the `plugins/` directory to install.

## Enabling and Disabling Plugins

- **Enable** — Activates the plugin, runs its migrations, and registers its routes and services.
- **Disable** — Deactivates the plugin without removing its data. Re-enable at any time.

## Plugin Information

Each plugin card displays:

| Field | Description |
|-------|-------------|
| Name | Plugin display name |
| Version | Current version number |
| Author | Plugin developer |
| License | License under which the plugin is distributed |
| Health Score | Security and quality score based on automated scanning |
| Status | Enabled, disabled, or available |

## Permissions and Security

Before enabling a new plugin, you can review its declared permissions — what system resources it accesses (database, storage, settings, mail, etc.). Dixlase also performs a security audit to check for potential issues.

## Health Scoring

Dixlase automatically scores each plugin based on:

- Proper permission declarations
- Correct file structure and naming conventions
- Consistency between declared and actual capabilities
