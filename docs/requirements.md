# System Requirements
## PHP

| Requirement | Details |
|------------|---------|
| Version | **8.3+** |
| SAPI | FPM or CLI |

### Required Extensions

| Extension | Purpose |
|-----------|---------|
| Ctype | Character type checking |
| cURL | HTTP requests |
| DOM | XML/HTML parsing |
| Fileinfo | MIME type detection |
| JSON | JSON encode/decode |
| Mbstring | Multibyte string handling |
| OpenSSL | Encryption and SSL |
| PCRE | Regular expressions |
| PDO | Database abstraction |
| Tokenizer | PHP tokenizer |
| XML | XML parsing |

### Optional Extensions

| Extension | Purpose |
|-----------|---------|
| BCMath | Arbitrary precision math |
| Redis (phpredis) | Redis cache driver |
| GD or Imagick | Image processing |

## Database

| Engine | Version |
|--------|---------|
| MariaDB | **10.6+** (recommended) |
| MySQL | **8.0+** |

The default table prefix is `dls_`. UTF-8 (`utf8mb4`) character set is required.

## Web Server

| Server | Version |
|--------|---------|
| Nginx | 1.18+ (recommended) |
| Apache | 2.4+ with `mod_rewrite` |

## Runtime Tools

| Tool | Version | Purpose |
|------|---------|---------|
| Composer | 2.x | PHP dependency management |
| Node.js | 18+ | Frontend asset compilation |
| npm | 9+ | Node package management |

## Docker Requirements (Development)

If using the Docker setup, you only need:

| Tool | Version |
|------|---------|
| Docker | 20.10+ |
| Docker Compose | v2+ |

All other requirements (PHP, MariaDB, Nginx, Node.js) are included in the Docker images.

## Directory Permissions

The web server user must have write access to:

| Directory | Purpose |
|-----------|---------|
| `storage/` | Logs, cache, sessions, uploads |
| `bootstrap/cache/` | Framework cache |
| `.env` | Environment configuration |

Recommended permission: `775` for directories, `664` for files, owned by the web server group.
