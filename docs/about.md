# What is Dixlase

Dixlase is an open-source content management system built with Laravel. It provides a modern, extensible platform for building and managing websites with a focus on security, simplicity, and plugin-based extensibility.

## Key Features

- **Two Admin Modes** — Simple Mode for everyday operations with safe defaults, and Advanced Mode for full control
- **Plugin System** — Extend functionality with installable plugins, each with security scanning and health scoring
- **Theme System** — Customize your site's appearance with installable themes
- **Multi-language** — Built-in support for multiple languages (English and Japanese included by default)
- **Security First** — CSP headers, two-factor authentication (TOTP and WebAuthn), audit logging, CAPTCHA, login notifications, and more
- **Modern Stack** — Laravel 13, Alpine.js 3, Tailwind CSS 4

## Architecture

Dixlase follows a modular architecture where core functionality is kept minimal and features are added through plugins and themes.

### Core

The core provides the foundation: authentication, member management, role-based access control, media management, front page editing, settings, and the admin panel. It handles plugin/theme loading, security infrastructure, and system maintenance tools.

### Plugins

Plugins are self-contained packages that live in the `plugins/` directory. Each plugin declares its own routes, views, migrations, configuration, and translations. Plugins interact with the core exclusively through defined contracts and APIs, allowing them to be installed or removed without affecting the rest of the system.

### Themes

Themes control the visual appearance of your site. They reside in the `themes/` directory and can be switched from the admin panel without modifying content.

## Who is Dixlase for?

- **Site owners** who want a CMS that is secure by default without sacrificing usability
- **Developers** who want to build on a modern Laravel foundation with a clean plugin architecture
- **Organizations** that need audit logging, access control, and compliance-friendly security features

## License

Dixlase is licensed under the [AGPL-3.0](license.md) with a special exception that allows plugins and themes using the Plugin API to be distributed under any license. See [License](license.md) for details.

## Links

- **Official Site**: [https://dixlase.com](https://dixlase.com)
- **Documentation**: [https://docs.dixlase.com](https://docs.dixlase.com)
- **GitHub**: [https://github.com/Dixlase/dixlase](https://github.com/Dixlase/dixlase)
- **Developer**: [exc-D inc.](https://exc-d.com)
