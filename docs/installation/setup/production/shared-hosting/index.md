# Shared Hosting

Deploy Dixlase on shared rental servers. Choose a guide for your hosting provider.

## Requirements

- PHP 8.2 or higher
- MySQL 8.0 / MariaDB 10.6 or higher
- SSH access (recommended) or FTP access
- Composer (installed on the server or run locally)

## Hosting Provider Guides

### Japan

- [Xserver](xserver.md) — Japan's most popular shared hosting
- [ConoHa WING](conoha.md) — High performance shared hosting
- [Sakura Internet](sakura.md) — Long-established Japanese hosting
- [Lolipop!](lolipop.md) — Budget-friendly shared hosting

### International

- [cPanel Hosting](cpanel.md) — Generic guide for any cPanel-based hosting

## General Steps

If your hosting provider is not listed above, follow these general steps:

1. Create a MySQL database and user via your hosting control panel
2. Upload Dixlase files via FTP or SSH
3. Run `composer install` (via SSH or locally before upload)
4. Set up `.env` with your database credentials
5. Point your domain to the `public/` directory
6. Access your site to run the [Installation Wizard](../../wizard.md)
