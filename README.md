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
git clone https://github.com/dixlase/dixlase.git
cd dixlase/docker
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

## 📜 License

- Core: **AGPL v3**
- Plugins: separate licenses (commercial/OSS)

---

## 🛡️ Vision

> Based on **Safety, Fairness, and Transparency**,
> cultivating **Extensibility** and **Sustainability**,
> Dixlase aims to deliver a CMS that everyone can use with true freedom.
