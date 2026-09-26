# Changelog

All notable changes to Dixlase CMS Core are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and Plugin API stability is governed by the policy declared in [`PLUGIN-API.md`](./PLUGIN-API.md#stability-pledge).

This file is the **authoritative source** for Plugin API additions, deprecations, and removals.
Plugin and theme authors are encouraged to subscribe to the GitHub releases of the core
repository to be notified of changes.

## Versioning and the Plugin API

- Releases follow semantic versioning (`MAJOR.MINOR.PATCH`).
- During the 0.x beta series the Plugin API — the elements (interfaces, DTOs, services,
  events, Blade components, etc.) enumerated in `PLUGIN-API.md` — is **published but not
  frozen**. Elements may still change in backward-incompatible ways while the design settles.
- Breaking changes to the Plugin API ship only in a MINOR release (e.g. `0.1` → `0.2`),
  never in a PATCH release. An extension that declares `^0.1` is not affected by any
  `0.1.x` update.
- Every breaking change is listed in this file under a **"Plugin API — Breaking"** heading
  with migration notes. Where practical, the old symbol is kept for one release and marked
  `@deprecated`.
- Each breaking change also bumps the `dixlase_api` version
  (`App\Extension\ExtensionApi::CURRENT_VERSION`).
- The Plugin API will be frozen once it has settled. The freeze is announced here and
  marked with the `plugin-api-v1.0` tag. It is **not tied to a particular core version**,
  including 1.0. From that point on, breaking changes ship only in MAJOR releases, with at
  least one full MINOR cycle of deprecation.

---

## [Unreleased]

### Added

- **Removed:** `livewire/livewire` is no longer a dependency, and the two Livewire UI
  components leave the Plugin API surface (`x-ui-livewire-modal`,
  `x-ui-livewire-notification`). Nothing used them: the modal the admin panel renders is
  `x-ui-modal` (Alpine), no class extended `Livewire\Component`, no view called
  `@livewire(...)`, and no layout emitted `@livewireScripts`. They were left over from a
  2026-02-06 experiment reverted the next day. A plugin that wants Livewire can require it
  itself; core no longer ships it. Removed with them: `config/livewire.php`, the
  `@livewireScriptsWithoutNavigate` directive (its target view never existed, so calling it
  threw), and `livewire-notification.js` from the common bundle.
- `dls:install` installs Dixlase from the command line, running the same pipeline as
  the browser wizard. Settings come from options (`--site-name`, `--admin-email`,
  `--db=sqlite`, …), anything missing is prompted for, and passwords may be passed
  through `DIXLASE_ADMIN_PASSWORD` / `DIXLASE_DB_PASSWORD` / `DIXLASE_MAIL_PASSWORD`
  instead of the command line. A non-interactive run needs `--force`, because the
  default drops every table in the target database; `--preserve-data` migrates
  without dropping. The wizard's execution path moved to
  `App\Services\Install\InstallRunner` so both entry points share it. See
  *Installation Wizard*.
- `App\Multilingual\SiteTaglineProvider` joins the Plugin API surface. It supplies the
  primary-locale value for the core `site_tagline` setting, and extensions reference it by
  class name, so it is on the documented boundary for the plugin exception to cover that
  reference.
- `dls:schema:retire` drops schema objects that an earlier beta created and a later
  beta no longer uses: the `webauthn_credentials` table, and the table, column and
  `global_settings` marker rows left by the core-update verification fixtures. Only
  objects listed in the command are touched. It previews by default, applies with
  `--confirm`, warns when legacy passkey rows would be deleted, and writes an audit log
  entry (`schema_retired`). See *Upgrading* §4.5.1.

### Changed

- `create_members_passkeys_table` is renumbered from `0001_01_01_000048` to `000023`, so
  it sorts with the other `members_*` tables, and the migrations it now precedes shift
  by one (`000023`–`000047` → `000024`–`000048`). Its foreign key to `members` now
  lives in `add_foreign_key_constraints`, like every other `members_*` table. Existing
  sites run `dls:migration:resync --prune --confirm` before `migrate`.
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

- The official theme bundled in the release ZIP failed signature verification on every
  install: the release's exclude list, written for core, also stripped the theme's signed
  `CHANGELOG.md`, `CONTRIBUTING*`, `SECURITY.md`, `tests/`, `phpunit.xml` and its
  `.gitignore`. The release now copies each bundled theme as composer placed it, and
  fails the build if any file its signature lists is missing.
- `dls:theme:migrate`, `dls:theme:migrate:refresh`, `dls:theme:migrate:rollback` and
  `dls:theme:seed` accept the theme's slug again: they built the directory with
  `Str::studly()` alone, so `dixlase-onepage` looked for `DixlaseOnepage` instead of
  `DixlaseOnePage`. They now resolve the argument through the theme record first.
- A core update or rollback no longer replaces `public/` as a directory: its entries are
  swapped and the directory itself stays, so a built-in server (`php -S`, as the one-line
  installer runs it from `public/`) keeps working instead of failing every request with
  `Failed opening required '/index.php'` until it is restarted.
- A core update that removes a package failed on its first attempt: after `vendor/` was
  swapped, `view:cache` ran in the updater's own process, whose Blade extensions still
  came from the old `vendor/` (livewire/livewire), and failed on a file that was gone.
  After a `vendor/` swap the migrate, seed and cache steps now run in a new
  `php artisan` process; the core rollback clears its caches the same way.
- A core rollback that swapped `vendor/` died at the cache-clear step with
  `include(.../ArtisanProcess.php): Failed to open stream`, and its own recovery then left
  the site answering 500. The class that starts the post-swap subprocess is newer than any
  release a rollback can restore, so resolving it after the source had been replaced read
  a file that was no longer there; the recovery put source, vendor and schema back but
  kept `bootstrap/cache/packages.php` as written for the rejected `vendor/`, so every
  request died on a provider that was gone. The launcher is now resolved and exercised
  before anything on disk is touched, a recovery deletes and rebuilds the
  package-discovery manifests, and both the update and the rollback ask a new process to
  boot the application — on success before the site is let back in, and after a recovery
  before it reports one. The same combination recurs on any rollback from a release that
  changed the update code, including `0.1.1` back to `0.1.0`.
- A failed core update now reverses the migrations it applied, before restoring the
  source. Before, the database kept the new schema, the next update recorded that schema
  as its starting point, and `dls:core:rollback` then had nothing to reverse.
- The admin rollback button did nothing right after a core update: its confirm modal was
  only rendered while something was left to update.
- `dls:core:update` ran `npm install` and `vite build` on every update, because the
  prebuilt-assets check did not look at core's own output directory,
  `public/assets/build`. The prebuilt assets in the release ZIP are now used as shipped.
- Installing a signed plugin or theme that ships no prebuilt assets made its signature
  invalid before it could be activated: the asset build ran `npm install`, which rewrites
  the signed `package-lock.json` in the local npm's format. The build now uses `npm ci`
  when a lock file is shipped (`npm install` only when there is none), in both the
  extension install / update commands and `dls:theme:build`.
- A failed extension asset build was invisible when the install ran from the admin panel:
  the npm output was discarded and the install reported success, leaving the extension
  without its JS / CSS. The failed step is now logged with the tail of npm's output, and
  the plugin and theme install screens show a warning with the command to retry. The
  admin flash message component (`x-ui-flash-message`) now renders `warning` messages,
  which it previously dropped.
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

## [0.1.0] — 2026-10-01
The first public release of the Plugin API. This release defines the boundary that
plugins and themes can build on under the AGPL Plugin and Theme Exception (see `LICENSE`).
The boundary is published but not yet frozen — see *Versioning and the Plugin API* above.

### Requirements

- PHP `>= 8.3`
- Laravel 13

The bundled Docker runtime ships PHP 8.3. See `docs/requirements.md` for the full
environment matrix (database, web server, PHP extensions).

### Added

#### Plugin API surface (published)

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
  — versioned audit-event payload shape for SIEM subscribers.
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

- `PLUGIN-API.md` Stability Pledge section documenting the stability status, the
  change-notice process, the deprecation policy, and announcement channels.
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
