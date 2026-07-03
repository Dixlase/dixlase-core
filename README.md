# Dixlase

For Japanese, see [README.ja.md](./README.ja.md).

**Dixlase** is a Laravel-based open-source CMS from Japan, developed by [exc-D inc.](https://exc-d.com)
- Defense hardened in the core, expression free through plugins and themes
  Security, tamper detection, permissions, and signature verification are the core's responsibility, leaving content creation and editing maximally free. Because the core handles security, operators and creators can focus on producing, editing, and running content — across corporate sites, internal deployments, client projects, and personal sites alike.
- A minimal core, maximal extensibility through plugins
  The core sticks to security, front-page editing, user and permission management, and the plugin API; everything else ships as a plugin — and Dixlase's own built-in features run on the same API third-party plugins use.
- Built to last, and ready for AI
  Rather than optimizing for a quick first setup, Dixlase favors a design built to last — stable data structures, backward compatibility, and explicit deprecation — and keeps room for AI, so structured logs and new integration protocols like MCP work without rebuilding the CMS itself.

---

## 🚀 Features

- **Multi-layered Security** — two-factor auth via passkeys / email codes, IP restrictions, login notifications, and role-based permissions out of the box.
- **Plugin-based Architecture** — a minimal core; features are added as plugins, and much of the core itself runs on the same plugin API.
- **On-Premise Ready** — your data on your server: shared hosting, VPS, or dedicated — wherever you like.
- **Detailed Audit Logs** — every admin action recorded in a structured form for operational transparency and accountability.
- **Site Health Check** — periodic diagnosis of security settings, configuration, and operational state, output in a machine-readable form.
- **Reliable Setup** — an install wizard guides environment config, database connection, admin registration, and initial security.
- **CSP Support** — a Content Security Policy header is sent by default, protecting visitors from XSS and content tampering.
- **Plugin Safety** — permission declarations, static analysis, signature verification, and a health score catch risky or tampered plugins at install time.

---

## 🕹️ Live Demo

No installation required — try Dixlase's admin panel and operational experience right in your browser. A disposable instance is generated for each visitor from a SQLite template and auto-discarded after a set period, so you can reset and try as many times as you like. Admin panel, content editing, plugin installation — everything feels exactly like production.

See the [official site](https://dixlase.org/) for the live demo.

---

## 📦 Setup

Pick the method that matches your environment:

### Manual install (ZIP)

Download the latest release ZIP from [GitHub Releases](https://github.com/Dixlase/dixlase-core/releases), then:

```bash
unzip dixlase-*.zip
cd dixlase
composer install
```

After this, open the site URL in a browser — the installation wizard will guide you through database setup, the admin account, and initial settings.

### Quick install script

For fresh VPS / bare-metal hosts with PHP 8.2+ and Composer, install with a single command:

```bash
curl -sS https://install.dixlase.net | php
```

The script checks PHP version and required extensions, downloads the latest release, runs `composer install`, generates an application key, sets directory permissions, and prints the URL to the Web-based installation wizard.

### Docker installer

For Docker-equipped hosts, use the [Docker installer](https://github.com/Dixlase/dixlase-installer-docker), which brings up Dixlase via `docker compose`. See the installer repository's README for prerequisites and step-by-step instructions.

### Composer create-project

For Composer-friendly environments, scaffold a new install in one command:

```bash
composer create-project dixlase/dixlase-core dixlase
```

---

To work on the Core itself, clone this repository and bring up the development Docker stack provided by the [Docker installer](https://github.com/Dixlase/dixlase-installer-docker). Dedicated developer documentation is in preparation.

---

## 🧩 Official Plugins

Dixlase ships a minimal core, and the following official plugins are maintained alongside it. Install only the features your site needs — directly from the plugin list in the admin panel, with no command-line steps or manual file placement.

| Plugin | What it does |
| --- | --- |
| **Dixlase Pages** | Static pages and the essential content-creation features. Blog posts and custom post types are planned as separate plugins. |
| **Dixlase Inquiry** | Contact form with auto-reply email, spam protection, and admin-side message management. |
| **Dixlase Menus** | Visually edit header / footer / sidebar menu structures by drag and drop. |
| **Dixlase SEO** | Manage meta tags, sitemaps, OGP, and structured data per page or site-wide. |
| **Dixlase Cookie** | Cookie-consent display and recording, category-based consent, and conditional script firing. |

More official plugins are on the way. The full lineup is also viewable on [GitHub](https://github.com/Dixlase).

---

## 🎨 Official Themes

Themes shape a site's look and structure, and you can swap between them as needed.

- **Dixlase OnePage** — the default theme, optimized for single-page layouts (hero, content builder, contact form, footer). Well suited to corporate sites and landing pages.

More themes are planned for future releases.

---

## 📚 Documentation

- [Project Site — dixlase.org](https://dixlase.org/) — overview, live demo, and news
- Documentation — installation, operations, and developer guides (in preparation)
- Development Guide — architecture, API references, and coding conventions (in preparation)

### Developer Guides

- Plugin API — the plugin / theme API boundary definition (in preparation)
- Source Comment Localization — comment locale switching with `convert-comments.sh` (in preparation)
- MCP Server (Laravel Boost) — AI-assisted development setup for VSCode / Cursor (in preparation)

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

## 📖 Governance & Policies

- [Contributing Guide](./CONTRIBUTING.md) — How to contribute
- [Copyright Policy](./COPYRIGHT-POLICY.md) — Dual-license stance and CLA model overview
- [Contributor License Agreement](./CLA.md) — In preparation; the full text will be published when external contributions reopen
- [Security Policy](./SECURITY.md) — Reporting vulnerabilities

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

> With **Safety, Fairness, Transparency, Extensibility, and Sustainability** as the foundation,
> **true freedom** blooms on top of them all.
> Dixlase aims to be a CMS everyone can use freely and with confidence.
