# Dixlase

For Japanese, see [README.ja.md](./README.ja.md).

**Dixlase** is a next-generation CMS built on Laravel.
From minimal landing pages to event management, reservations, and e-commerce, every feature is provided as an installable plugin.
Our mission is to create **the world's most secure CMS**, released as open-source under the AGPL license.

---

## 🚀 Features

- **Security First**
  Two-factor authentication, login notifications, and strong password policies are supported by default.
- **Minimal Core + Plugins**
  Core only provides front-page editing and admin login. Even blogs and pages are optional plugins.
- **Highly Customizable**
  `custom/` directory allows overrides and project-specific extensions.
- **Dual Licensing**
  Core is AGPL, while commercial plugins will be available under separate licenses.
- **Docker-based Development**
  Laravel, MySQL, Redis, Nginx, Vite, phpMyAdmin, and more—ready with one command.

---

## 📦 Setup

Pick the method that matches your environment:

### Docker installer

For Docker-equipped hosts, use the [Docker installer](https://github.com/Dixlase/dixlase-installer-docker), which brings up Dixlase via `docker compose`. See the installer repository's README for prerequisites and step-by-step instructions.

### Quick install script

For fresh VPS / bare-metal hosts with PHP 8.2+ and Composer, install with a single command:

```bash
curl -sS https://install.dixlase.net | php
```

The script checks PHP version and required extensions, downloads the latest release, runs `composer install`, generates an application key, sets directory permissions, and prints the URL to the Web-based installation wizard.

### Composer create-project

For Composer-friendly environments, scaffold a new install in one command:

```bash
composer create-project dixlase/dixlase-core dixlase
```

### Manual install (ZIP)

Download the latest release ZIP from [GitHub Releases](https://github.com/Dixlase/dixlase-core/releases), then:

```bash
unzip dixlase-*.zip
cd dixlase
composer install
```

After this, open the site URL in a browser — the installation wizard will guide you through database setup, the admin account, and initial settings.

---

For working on the Core itself (this repository), clone it and start the dev Docker stack — see [CONTRIBUTING.md](./CONTRIBUTING.md) for the local development environment.

---

## 🤖 MCP Server (Laravel Boost)

Dixlase supports [Laravel Boost](https://laravel-news.com/supercharge-your-laravel-projects-real-ai-coding-with-laravel-boost) as an MCP server for AI-assisted development.
This allows VSCode or Cursor to directly run Laravel commands and generate components.

### How to enable

1. Copy the example config:
   ```bash
   cp mcp.example.json .mcp.json
   ```

2. Edit `.mcp.json` for your environment:
   - `-p` → your Docker project name (e.g., `dixlase`)
   - `-f` → absolute path to `docker-compose.yml`
   - Service name → usually `laravel.test`

3. Enable MCP in VSCode / Cursor.
   Laravel Boost will start automatically, providing 16+ tools such as Livewire component scaffolding, running tests, and database migrations.

---

## 🌐 Comment Languages

The canonical Dixlase source ships with English comments. A per-locale comment archive lives at `resources/comment-translations/{locale}/` (and inside each plugin/theme) and can be applied to a development checkout in-place.

```bash
# Convert all source comments (core + plugins + themes) to Japanese
./convert-comments.sh ja

# Revert back to English
./convert-comments.sh ja --reverse

# Preview without writing
./convert-comments.sh ja --dry-run

# List available locales
./convert-comments.sh --list
```

Substitution is AST-aware — only PHP comment tokens are rewritten, never string literals — and the operation is idempotent (running it again on already-converted source is a safe no-op).

The dictionary is versioned alongside the source, so translation improvements are accepted as pull requests. New locales (e.g. `zh`, `ko`) can be added by creating `resources/comment-translations/{locale}/_glossary.php` plus per-file dictionaries that share the same English keys.

GitHub releases ship the canonical English source only; users (and the [Dixlase Docker Installer](https://github.com/Dixlase/dixlase-installer-docker) during setup) flip to a locale via this script after install.

---

## 📚 Documentation

### Official Resources
- [Project Site — dixlase.org](https://dixlase.org/)
- Developer Docs (WIP)

### Development Guides
- [Composer Local Setup](docs/composer-local-setup.md) - Managing custom plugins and packages
- [Git Exclude Auto Management](docs/git-exclude-auto-management.md) - Automatic .git/info/exclude management for plugins
- [Password Dictionary Attack Protection](docs/password-dictionary-attack-protection-usage.md) - Have I Been Pwned API integration
- [Two-Factor Authentication](docs/two-factor-authentication-usage.md) - 2FA implementation guide

---

## 🤝 Contributing

> **Currently:** Dixlase is in early development and **does not yet
> accept external code contributions**. The Contributor License
> Agreement (CLA) is under formal legal review; code Pull Requests
> will open once it is finalized.

Bug reports and feature proposals via **GitHub Issues** are welcome
in the meantime, as are questions via **GitHub Discussions**. See
[CONTRIBUTING.md](./CONTRIBUTING.md) for the current contribution
scope.

Once the CLA is finalized, contributions will open under the
[Dixlase Copyright Policy](./COPYRIGHT-POLICY.md) and the Dixlase
CLA (the full text will be published in [CLA.md](./CLA.md)). At
that point, [CONTRIBUTING.md](./CONTRIBUTING.md) will be replaced
with the full PR-based contribution guide.

---

## 📖 Project Documents

- [Contributing Guide](./CONTRIBUTING.md) — How to contribute
- [Copyright Policy](./COPYRIGHT-POLICY.md) — Dual-license stance and CLA model overview
- [Contributor License Agreement](./CLA.md) — In preparation; the full text will be published when external contributions reopen
- [Security Policy](./SECURITY.md) — Reporting vulnerabilities
- [Plugin API](./PLUGIN-API.md) — Plugin API boundary definition

---

## 📜 License

Dixlase CMS is distributed under a **dual license**:

- **Open Source License**: [GNU Affero General Public License v3](./LICENSE) with the Dixlase Plugin and Theme Exception (see [LICENSE-EXCEPTIONS](./LICENSE-EXCEPTIONS)).
- **Commercial License**: A separate commercial license is planned for use cases where AGPL v3 compliance is not feasible (e.g., distributing modified versions in closed-source SaaS). **It is not yet available** — only a draft of the eventual terms is present in [LICENSE-COMMERCIAL](./LICENSE-COMMERCIAL). For availability timing or other questions, contact **info@dixlase.org**.

A short overview of how these files fit together is in [NOTICE](./NOTICE) ([日本語](./NOTICE.ja)).

### Plugins and Themes

Plugins and themes that interact with Dixlase CMS exclusively through the [Plugin API](./PLUGIN-API.md) are not considered derivative works and may be distributed under **any license of your choice, including proprietary licenses**. See [LICENSE-EXCEPTIONS](./LICENSE-EXCEPTIONS) for the full terms.

### Source Code Availability (AGPL §13)

If you run Dixlase CMS on a server and make it accessible to users over a network, AGPL §13 requires that users be able to obtain the source code of your running version. Ensure that the "Source" link in your admin footer (or equivalent) is accessible to users. The URL advertised there is configurable via the `DIXLASE_SOURCE_URL` environment variable.

---

## 🛡️ Vision

> Based on **Safety, Fairness, and Transparency**,
> cultivating **Extensibility** and **Sustainability**,
> Dixlase aims to deliver a CMS that everyone can use with true freedom.
