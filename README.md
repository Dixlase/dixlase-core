# Dixlase

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

### 1. Clone the repository

```bash
git clone https://github.com/Dixlase/dixlase-core.git
cd dixlase-core/docker
```

### 2. Start Docker containers

```bash
docker compose up -d
```

### 3. Prepare Laravel

```bash
docker compose exec -T -w /var/www/html laravel.test composer install
docker compose exec -T -w /var/www/html laravel.test php artisan key:generate
```

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

## 📚 Documentation

### Official Resources
- [Official Site (WIP)](https://dixlase.com)
- [Plugin Marketplace (Planned)](https://market.dixlase.com)
- [Developer Docs (WIP)](https://docs.dixlase.com)

### Development Guides
- [Composer Local Setup](docs/composer-local-setup.md) - Managing custom plugins and packages
- [Git Exclude Auto Management](docs/git-exclude-auto-management.md) - Automatic .git/info/exclude management for plugins
- [Password Dictionary Attack Protection](docs/password-dictionary-attack-protection-usage.md) - Have I Been Pwned API integration
- [Two-Factor Authentication](docs/two-factor-authentication-usage.md) - 2FA implementation guide

---

## 🤝 Contributing

1. Open an issue for bug reports or feature requests
2. Fork & submit a pull request
3. Join our community (Discord planned)

---

## 📖 Project Documents

- [Contributing Guide](./CONTRIBUTING.md) — How to contribute
- [Copyright Policy](./COPYRIGHT-POLICY.md) — Dual-license stance and CLA model overview
- [Individual CLA](./CLA-INDIVIDUAL.md) — For individual contributors
- [Corporate CLA](./CLA-CORPORATE.md) — For organizations contributing on behalf of employees
- [Security Policy](./SECURITY.md) — Reporting vulnerabilities
- [Plugin API](./PLUGIN-API.md) — Plugin API boundary definition

---

## 📜 License

Dixlase CMS is distributed under a **dual license**:

- **Open Source License**: [GNU Affero General Public License v3](./LICENSE) with the Dixlase Plugin and Theme Exception (see [LICENSE-EXCEPTIONS](./LICENSE-EXCEPTIONS)).
- **Commercial License**: For use cases where AGPL v3 compliance is not feasible (e.g., distributing modified versions in closed-source SaaS), a separate commercial license is available — see [LICENSE.commercial](./LICENSE.commercial) (currently a draft) or contact **office@exc-d.com**.

A short overview of how these files fit together is in [NOTICE](./NOTICE) ([日本語](./NOTICE.ja)).

### Plugins and Themes

Plugins and themes that interact with Dixlase CMS exclusively through the [Plugin API](./PLUGIN-API.md) are not considered derivative works and may be distributed under **any license of your choice, including proprietary licenses**. See [LICENSE-EXCEPTIONS](./LICENSE-EXCEPTIONS) for the full terms.

### Source Code Availability (AGPL §13)

If you run Dixlase CMS on a server and make it accessible to users over a network, AGPL §13 requires that users be able to obtain the source code of your running version. Ensure that the "Source" link in your admin footer (or equivalent) is accessible to users. The URL advertised there is configurable via the `DIXLASE_SOURCE_URL` environment variable.

### Contribution Licensing

> **Currently:** Dixlase is in early development and **does not accept external pull requests**. Bug reports via Issues and questions via Discussions are welcome — see [CONTRIBUTING.md](./CONTRIBUTING.md). Code-bearing contributions will reopen once the Contributor License Agreement (CLA)'s formal legal review is complete.

Contributions to the Dixlase core repository are governed by a Contributor License Agreement. The agreement texts ([CLA-INDIVIDUAL.md](./CLA-INDIVIDUAL.md) for individuals, [CLA-CORPORATE.md](./CLA-CORPORATE.md) for organizations) are included in the repository for transparency and review, but are **not currently in active use**. They will become operative once external code contributions reopen. See [COPYRIGHT-POLICY.md](./COPYRIGHT-POLICY.md) for the broader licensing structure and [CONTRIBUTING-FUTURE.md](./CONTRIBUTING-FUTURE.md) for the planned PR-based contribution flow.

---

## 🛡️ Vision

> Based on **Safety, Fairness, and Transparency**,
> cultivating **Extensibility** and **Sustainability**,
> Dixlase aims to deliver a CMS that everyone can use with true freedom.
