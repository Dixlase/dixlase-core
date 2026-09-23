# Changelog

All notable changes to Dixlase CMS Core are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and Plugin API stability is governed by the policy declared in [`PLUGIN-API.md`](./PLUGIN-API.md#stability-pledge).

This file is the **authoritative source** for Plugin API additions, deprecations, and removals.
Plugin and theme authors are encouraged to subscribe to the GitHub releases of the core
repository to be notified of changes.

## Versioning and the Plugin API

- Releases follow semantic versioning (`MAJOR.MINOR.PATCH`).
- The set of Plugin API elements (interfaces, DTOs, services, events, Blade components, etc.)
  enumerated in `PLUGIN-API.md` is **frozen** within a major version.
- Within `^0.1` (i.e. `>=0.1.0, <0.2.0`), no breaking changes will be introduced to the
  Plugin API. Plugins and themes built against `0.1.0` will continue to work on every
  `0.1.x` release without modification.
- Deprecation: when a Plugin API element must change incompatibly, a new symbol ships in
  the next minor release alongside the deprecated old symbol. The deprecated symbol is
  removed only in the next major release. This guarantees plugin authors at least one
  full minor cycle to migrate.

---

## [Unreleased]

### Changed

- **Passkeys now run on the official `laravel/passkeys` package.** `laragear/webauthn`
  was archived upstream (2026-05) and is removed, together with its composer patch and
  `cweagans/composer-patches`. Registration and login go through
  `App\Services\TwoFa\Passkeys\PasskeyCeremony`; `TwoFaPasskeyServiceInterface` keeps
  its method signatures, but the WebAuthn option arrays it returns are now the standard
  `PublicKeyCredential*OptionsJSON` shape (base64url binary fields).
- Admin member passkeys are stored in **`members_passkeys`** (replacing
  `webauthn_credentials`); the credential is kept as one JSON record with a COSE public
  key. Existing passkeys are not converted and must be registered again.
- `App\Models\WebAuthnCredential` is replaced by `App\Models\Passkey`
  (extends `Laravel\Passkeys\Passkey`). `Member` implements
  `Laravel\Passkeys\Contracts\PasskeyUser`; its Laragear methods
  (`webAuthnCredentials()`, `webAuthnId()`, `webAuthnData()`, `flushCredentials()`,
  `disableAllCredentials()`, `makeWebAuthnCredential()`) are removed — use
  `passkeys()` / `twoFaPasskeys()`.
- `config/webauthn.php` is removed. By default the RP ID is the request's host when that
  host is APP_URL's host or an active site's `host`, and APP_URL's host otherwise, so an
  arbitrary `Host` header can no longer choose the RP ID. Pin the RP ID and origins with
  `PASSKEYS_RP_ID` / `PASSKEYS_ALLOWED_ORIGINS` (see the `passkeys` section of
  `config/fortify.php`).

### Fixed

- Admin passkey login sent the assertion in plain base64; it now uses base64url as
  WebAuthn requires.
- The passkey cleanup rule aged passkeys by registration date, so a passkey registered
  more than a year ago was deleted even if it had just been used; it only stayed
  harmless because its extra condition referenced a `last_used_at` column the old table
  lacked, which made the cleanup fail with an SQL error. It now ages passkeys by
  `last_used_at` (updated on every sign-in) and leaves never-used passkeys alone.
- The member edit screen built its passkey and recovery-code delete URLs with a hard-coded
  `admin/` prefix, so every delete returned 404 on installs with a custom admin path.
  They are now built from route names. Missing passkey messages on the profile and member
  screens (for example `admin/profile/common.passkey_deleted_all`) were added in both
  languages.

## [0.1.0] — TBD

The first stable Plugin API freeze. This release establishes the public boundary that
plugins and themes can rely on under the AGPL Plugin and Theme Exception (see `LICENSE`).

### Requirements

- PHP `>= 8.3`
- Laravel 13

The bundled Docker runtime ships PHP 8.3. See `docs/requirements.md` for the full
environment matrix (database, web server, PHP extensions).

### Added

#### Plugin API surface (frozen)

- **Plugin Integration Contracts** under `App\Contracts\PluginIntegration\`:
  `LinkableInterface`, `LinkableProviderInterface`, `MenuProviderInterface`,
  `PreviewProviderInterface`, `PrivacyPolicyProviderInterface`,
  `SeoMetaProviderInterface`, `DashboardWidgetProviderInterface`,
  `DashboardNotificationProviderInterface`.
- **Plugin Integration DTOs** under `App\DTO\PluginIntegration\`:
  `LinkableDTO`, `MenuDTO`, `MenuItemDTO`, `PreviewDTO`, `PreviewFieldDTO`,
  `SearchQueryDTO`, `PaginatedResultDTO`, `SeoMetaDTO`, `DashboardWidgetDTO`,
  `DashboardNotificationDTO`.
- **Plugin Capability Contracts** under `App\Contracts\Plugin\`:
  `PluginCapabilityInterface`, `ApiResourceProviderInterface`,
  `ContentProviderCapableInterface`, `EditorCapableInterface`, `MailCapableInterface`,
  `SignatureVerifierInterface`, `PluginPermissionServiceInterface`.
- **Service Contracts** under `App\Contracts\` covering admin navigation, CSP policy,
  encryption, extension sources, file integrity, legal pages, logging, mail,
  route slugs, theme permissions, translations, two-factor authentication.
- **Repository Contracts** under `App\Contracts\Repositories\` for settings, media,
  plugins, themes.
- **Core event names**: `App\Events\DixlaseEvents` constants for backup, deploy,
  translation, plugin lifecycle, theme lifecycle, cache, maintenance, security,
  audit, and AI categories. The full list is enumerated in `PLUGIN-API.md` §9.8.
- **Blade components** for forms, UI, admin, content editor, security/auth,
  media, front-end. The full list is enumerated in `PLUGIN-API.md` §6.
- **Middleware groups** for plugin routes: `plugin`, `plugin.web`, `plugin.admin`,
  `plugin.api`, `plugin.api.public`.
- **Configuration structures** for plugin admin navigation, role permissions,
  database cleanup.

#### Zero-trust extension points (reserved with no-op defaults)

- `App\Contracts\Security\SecretProviderInterface` — pluggable secret-store backend
  (Vault / AWS KMS / GCP KMS). Default: `App\Services\Security\EnvSecretProvider`.
- `App\Contracts\Security\RiskEvaluatorInterface` — conditional-access risk scoring
  hook. Default: `App\Services\Security\LowRiskEvaluator` returns `RiskScore::low()`.
- `App\Contracts\Security\PolicyEvaluatorInterface` — ABAC hook for
  `PermissionService`. Default: `App\Services\Security\NullPolicyEvaluator` returns
  `null` and delegates to RBAC.
- `App\DTO\Security\LoginContext`, `App\DTO\Security\RiskScore` — support types.
- `App\Enums\AccessRiskLevel`, `App\Enums\PolicyDecision`.
- `auth.iap` / `auth.mtls` middleware aliases — reserved namespace; default
  implementations `abort(501)` until plugins replace them.

#### Audit, multisite, privacy, cache, and i18n foundations

- `App\Events\AuditLogCreated::SCHEMA_VERSION = 1` and `App\DTO\Audit\AuditLogPayload`
  — frozen audit-event payload shape for SIEM subscribers.
- `App\Contracts\Site\SiteContextInterface` and `App\Facades\SiteContext` —
  multisite resolution boundary; the `BelongsToSite` trait scopes models to the
  active site.
- `App\Contracts\PluginIntegration\PrivacyDataProviderInterface`,
  `App\DTO\PluginPrivacy\UserDataExportDTO`,
  `App\DTO\PluginPrivacy\UserDataDeletionDTO`, and
  `App\Enums\PluginPrivacy\DeletionMode` — GDPR right-to-access and
  right-to-be-forgotten contract for plugins that store personal data.
- `App\Support\Cache\CacheKey` and `App\Support\Cache\SiteScopedCacheKey` —
  conventionalised cache-key builders that prevent collisions across core /
  plugins / themes and partition by `site_id`.
- `App\Contracts\TranslationResolver`,
  `App\Contracts\I18n\MissingTranslationHandler`, and
  `App\Traits\TranslatableTrait` — content i18n hooks for plugins that supply
  translated rows.
- `App\Helpers\DateTimeHelper` and the `<x-ui-datetime>` Blade component —
  UTC-stored / `display_timezone`-rendered datetime API.
- `App\Events\DixlaseEvents::URL_SLUG_CHANGED` and `ADMIN_URL_CHANGED` —
  reserved hooks for redirect-class plugins; the `web` middleware group prepend
  slot is reserved exclusively for the same plugin class (see `PLUGIN-API.md` §7.5).

#### Stability infrastructure

- `PLUGIN-API.md` Stability Pledge section documenting the freeze, supported version
  range, deprecation policy, and announcement channels.
- Service class naming convention table (`*Service` / `*Registry` / `*Manager` /
  `*Resolver`) in `PLUGIN-API.md` §9.
- Public identifier naming conventions frozen in `docs/development/naming.md`
  (API scopes, permission keys, event names, webhook event types, audit log
  actions, plugin capabilities, env / config keys).
- Migration immutability gate scaffolding: `php artisan dls:migration:lint`
  wired as a Tier 1 CI job. The lock file (`database/migration-lock.json`)
  is intentionally **not** committed during the Beta 1 → GA beta series so
  schema churn stays unblocked; the command exits 0 when no lock is
  present. The lock will be generated and committed just before the GA
  release tag (see `docs/operations/upgrading.md` and CLAUDE.md
  "Migration Editing Policy").
- `requires.dixlase: ^0.1.0` declared in all in-tree plugin `plugin.json` files.

#### Audit log integrity (operations)

- `audit:integrity verify` now verifies every daily seal (O(1) per day) plus the
  hash chain from the last verified record; `--all` re-verifies every row.
  `--date` and `--from` / `--to` keep their previous meaning.
- Site Health gains an **Audit Log Integrity** item (critical on detected
  tampering, warning when the hourly build or the daily seal stops running,
  recommendation when nothing was verified for 30 days).
- The web installer builds the hash chain once at completion.
- The audit log detail screen shows chain sequence, record hash, verification
  status and last verification time.
- `AUDIT_LOG_SECRET` is wired to `config('app.audit_log_secret')` as an optional
  dedicated HMAC key for the daily seals (falls back to `APP_KEY`).

### Notes for plugin authors

- Plugins targeting the v0.1 line should declare
  `"requires": {"dixlase": "^0.1.0", "php": ">=8.3"}` in their `plugin.json`.
  `>=1.0.0` is not a valid Dixlase constraint for this line — it marks the
  extension incompatible with the 0.1.x releases.
- Internal classes not enumerated in `PLUGIN-API.md` (notably
  `App\Services\Plugin\PluginHealthScorer`, `App\DTO\Plugin\HealthScoreResult`, and
  similar implementation details) are **not** part of the Plugin API. They may
  change in any release without notice.
- `App\Services\Plugin\CoreSignatureVerifier` is intentionally left without `@api`;
  plugins should depend on `App\Contracts\Plugin\SignatureVerifierInterface` instead.

[Unreleased]: https://github.com/Dixlase/dixlase-core/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/Dixlase/dixlase-core/releases/tag/v0.1.0
