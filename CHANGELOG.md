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

### Fixed
- `dls:plugin:download --extract` installs into the directory the plugin's own manifest
  declares, instead of the studly form of its slug. For a plugin whose name carries an
  acronym the two differ — `dixlase-seo` became `plugins/DixlaseSeo` while the plugin
  declares `Plugins\DixlaseSEO` — and the directory name is what `composer.local.json`
  builds the PSR-4 prefix from. Composer matches a prefix case-sensitively, so the
  plugin's own classes resolved only through an optimized classmap, and the same release
  installed from the admin panel produced a different tree. On top of the download path:
  `dls:plugin:install` records the namespace the manifest declares rather than composing
  one from the argument; `dls:plugin:lint` reports a directory whose name is not the one
  its manifest asks for; the recovery command core prints when an autoload sync fails now
  carries `--optimize`, as does the regeneration `dls:app:uninstall` runs, because
  without it the advice broke the extension it was meant to rescue. Existing installs
  still carry the wrong directory name; correcting them needs its own change (#488)

- `dls:plugin:install` and `dls:theme:install` print the stale-download warning in the
  active locale. It was a literal English sentence, so in a Japanese run it was the one
  English line — and the one line that asks the operator to decide whether to install the
  older copy. The admin panel had the message translated all along (#487)

- Ten `dls:` commands describe themselves again in `php artisan list` and `--help`. Five
  showed a translation key that exists in no language file
  (`command.theme_install.description` and the like, also in their argument help), two
  showed the `make:command` placeholder `Command description`, and three showed nothing,
  because they set the description after Symfony had already read it. A feature test now
  fails on any `dls:` command whose description is empty, a placeholder or a key (#509)

### Plugin API — Deprecated
- `App\Traits\AuditableTrait` is deprecated and will be removed in v0.2.0. It wrote an
  audit entry from a model's `created` / `updated` / `deleted` events, and nothing in
  core, the official plugins or the themes ever used it — so two mechanisms appeared to
  audit a change while only one was wired up. Auditing goes through the Action layer:
  put the operation in an `App\Actions\AbstractAction` subclass and let `audit()` write
  the row, or call the `Audit` facade from a service an action invokes. A model that uses
  the trait and is also touched inside an action should wrap the save with
  `$model->withoutAudit(...)` until the trait is gone (#477)

### Documentation
- The plugin security pages now describe what the code does. `security.sandbox`,
  `verification.permission_policy` and the other reserved manifest fields are marked as not
  read by core; `permissions` are documented as declarations for scanning, the health score
  and the admin panel, checked at runtime only for mail capabilities and privacy
  export / erase. Enabling is Blocked only by the preset's signature requirement or its
  status limit — not by a score below 50. The deduction table lists the rules the scorers
  actually emit, and the unused `signature_mismatch` and `file_outside_scope` rules are
  removed from `PluginHealthStatus`. Plugin safe mode is described as blocking plugin
  controller routes for one session, not as disabling plugins (#495)

## [0.1.7] — 2026-10-08

### Fixed
- In the front-page editor, a textarea resized by hand keeps its size, and the CSS and JS
  fields show the resize handle. Auto-grow still fits a field to its content until the
  operator resizes it (#463)

## [0.1.6] — 2026-10-08

### License
- The user documentation is also available under Creative Commons Attribution 4.0 (code
  examples in it also under CC0 1.0), and the AI assistant instruction files (`CLAUDE.md`
  and similar) under CC0 1.0, as alternatives to the AGPL. The covered files are listed in
  the new Part 2 of `LICENSE-EXCEPTIONS` (version 1.2). Legal and policy documents, source
  code and configuration stay under their current terms. `COPYRIGHT-POLICY`, `CLA` and the
  README say so as well (#460)

### Changed
- The release ZIP no longer contains `CLAUDE.md` / `CLAUDE.ja.md` or the internal notes under
  `resources/src/_csp-build-dryrun/` (#460)

### Fixed
- A theme's own admin screens are reachable again. Their routes were registered outside
  Laravel's `web` group, so `auth:member` saw no session, treated a signed-in operator as a
  guest and bounced them to the login screen, which sent them on to the dashboard. A theme's
  front routes had the same gap, latent only because no bundled theme ships a `routes/web.php`
  (#465)

## [0.1.5] — 2026-10-06

### Fixed
Closes the gaps in how plugin and theme updates and rollbacks are recorded and undone
(#457 – #459).
- Every extension update and rollback writes a version-history row and an audit entry,
  whichever path ran it: System → Updates, the rollback button on the Plugins and Themes
  screens, or the CLI. A failed update or rollback is audited as well, and runs started from
  the admin screens record who started them
- After an extension or core rollback, the version that was rolled back from is offered as
  an update again. It used to disappear until the next update check
- The extension update log (`storage/logs/extension-update.log`) keeps earlier runs instead
  of being overwritten by the next one
- An extension rollback undoes every migration the update added. It passed the batch number
  where a step count was expected, so an update that added several migrations was only
  partly undone

### Added
- A plugin or theme that was downloaded but not installed, and is older than the latest
  release on its source, is flagged in the list, in the install confirmation and by
  `dls:plugin:install` / `dls:theme:install`. The lookup is cached for 15 minutes

### Documentation
- `docs/development/plugins/updates-and-rollback.md`: what an extension update and a
  rollback do, and why update seeders are not undone

## [0.1.4] — 2026-10-02

### Security
Fixes for the residual findings of the pre-release security review on how extensions are
installed and updated, how backups are restored, and how signatures are trusted
(#440 – #446).
- Themes go through the same health gate as plugins. With a scan-required preset a theme is
  scanned before it is installed and refused if the check resolves to Blocked; switching to
  a Blocked theme is refused under every preset
- npm installs an extension's packages with `--ignore-scripts`, so the lifecycle scripts of
  its dependency tree no longer run at install time. The scanner reports install-time
  scripts in an extension's `package.json` as a dangerous API
- An extension update is scanned right after the new files are in place and before its
  migrations, seeders and build run. A Blocked version, or one that cannot be scanned, is
  rolled back
- An update downloads only from the extension's linked source; a failure there no longer
  falls through to other sources. The per-item and "update all" admin routes, which
  bypassed the update command, were removed (updates run from System → Updates)
- Restoring core source, plugins or themes from the admin screen — and undoing such a
  restore — runs in the background under maintenance mode instead of inside the web
  request, and re-syncs the extension autoloader afterwards
- The Authority key that signs the official extensions is pinned in core. A pinned key is
  never replaced by what the Authority returns, only pinned keys earn the "official" badge,
  and a cached key is no longer re-keyed by a later fetch
- A core update no longer falls back to the default-branch archive when the release lookup
  fails. The downloaded archive is checked against the release's `checksums.sha256` (attached
  from this release on), and the extracted `VERSION` must match the requested version;
  extension updates check the extracted manifest version the same way

### Fixed
- Uploading a theme ZIP or adding a theme from a source extracts it into `themes/`, where the
  theme list and the installer look. It used to land in `resources/views/themes/` and never
  showed up as installable

### Changed
- Repositories without a tagged release are no longer offered as an update from their
  default branch
- `dls:backup:restore` takes `--rollback-of=<restore id>` to undo a restore
- Signing an official extension with a new Authority key requires a core release that pins
  that key first

## [0.1.3] — 2026-10-01

### Security
Fixes for the seven findings of the security re-review of v0.1.0 (#437).
- A plugin ZIP whose `plugin.json` declares no `slug` no longer skips the pre-install scan
  gate. The slug is resolved the way `dls:plugin:install` records it, so with a
  scan-required preset an unscanned plugin is refused whatever its manifest says. A failed
  health check now blocks the install as well, and a ZIP that claims the slug of another
  plugin is refused when it is added, so it can no longer clear that plugin's scan records
- The member-roles screen no longer loads the PHP config files of plugins that were added
  but not installed (or of move-aside copies). Only installed plugins are read
- Raw HTML inside Markdown can now be escaped by the caller. Core's own front-page Markdown,
  which only ADMIN can author, is unchanged. DixlasePages 0.1.2 uses the escaped form for
  its pages, which editors below ADMIN may write
- The member delete confirmation escapes the member's display name
- Password resets and login alerts go to the confirmed email address; only the
  confirmation mail for an address change goes to the new, unconfirmed address. A password
  reset also cancels a pending address change
- A password reset ends every existing session of the account
- Theme admin routes are registered once, behind the `settings.themes.settings`
  permission. A second, ungated registration from `routes/admin.php` used to override it

### Fixed
- Installing the latest release of a plugin or theme asks GitHub for the release once
  instead of twice (one fewer API request per extension without a token)
- The Japanese GitHub rate-limit message no longer has a space between its two sentences

### Added
- Plugin API: `ContentPreviewService::render()` and `renderFromSlug()` take an optional
  `$allowRawHtml` argument (default `true`, the previous behaviour). Pass `false` to render
  raw HTML inside Markdown as text
- Plugin API: `PermissionRegistry::installedPluginDirectories()` lists the directories of
  installed plugins whose config files may be loaded

### Changed
- In a plugin card, a plugin without a `slug` in `plugin.json` is now scanned under the
  slug it will be installed with (e.g. `DixlaseFoo` → `dixlase-foo`, previously
  `dixlasefoo`). Rescan such a plugin once before installing it

## [0.1.2] — 2026-10-01

### Fixed
- A core update or rollback no longer shows the administrator running it a one-off error
  page while dependencies are swapped. The maintenance bypass is suspended for that window,
  so they see the same maintenance page as every visitor (#435)
- Sites with an enabled plugin no longer report a file integrity warning after every core
  update. `resources/src/common/css/dixlase-tailwind-plugin-sources.css`, which core rewrites
  on every plugin change, is excluded from the integrity check — including for baselines
  created before this release — so the post-update baseline refresh (#426) now runs on
  those sites too (#435)
- A core update or rollback no longer leaves the plugin Tailwind sources list reset to the
  release placeholder. Both now regenerate it, and `dls:theme:build` regenerates it before
  every build, so a theme build no longer drops classes used only by plugin content (#435)
- The GitHub rate-limit message shows when the limit resets in the site's display timezone
  (with the date when it is another day), no longer recommends a token, and is no longer
  wrapped in an English "Failed to download … from all sources" line on the download path
  (#435)

Fixes 1 and 2 run in the update code of the installed core, so they apply from the update
after v0.1.2; the update to v0.1.2 itself still runs the previous version's code.

## [0.1.1] — 2026-10-01

### Security
- Bump `league/commonmark` from 2.10.1 to 2.10.3 for two advisories that affect Markdown
  rendering (Pages, Inquiry and the content preview): a quadratic-time denial of service in
  the GitHub Flavored Markdown table extension
  ([GHSA-3q6v-r5mr-hxv8](https://github.com/advisories/GHSA-3q6v-r5mr-hxv8), high), and a
  `DisallowedRawHtml` bypass when a disallowed tag name ends the raw-HTML literal
  ([GHSA-97jj-33gv-5xf9](https://github.com/advisories/GHSA-97jj-33gv-5xf9), medium) (#433)

### Fixed
- Sites without a GitHub token no longer run out of the anonymous API limit (60 requests
  an hour per IP) while adding plugins and themes from the admin panel. Installing the five
  official plugins used to take 60–70 requests, so the third download failed with a bare
  "HTTP 403". Without a token, manifests and thumbnails are now read from
  `raw.githubusercontent.com` and release assets from their download URL on `github.com`,
  neither of which counts against the limit; the plugin and theme lists are cached for
  15 minutes (`EXTENSION_GITHUB_LIST_CACHE_TTL`). When the limit is reached, the admin
  panel says so and shows when it resets, instead of "HTTP 403" or an empty list. Sites
  with a token keep using the API as before (#432)

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

#### Commands and installation

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

### Changes since the pre-release builds

Only relevant to a site that ran a pre-release build (the `v0.2.x` / `v0.3.x` verification builds, such as the brand and demo sites). A fresh v0.1.0 install already has everything below.

#### Changed

- **Removed:** `livewire/livewire` is no longer a dependency, and the two Livewire UI
  components leave the Plugin API surface (`x-ui-livewire-modal`,
  `x-ui-livewire-notification`). Nothing used them: the modal the admin panel renders is
  `x-ui-modal` (Alpine), no class extended `Livewire\Component`, no view called
  `@livewire(...)`, and no layout emitted `@livewireScripts`. They were left over from a
  2026-02-06 experiment reverted the next day. A plugin that wants Livewire can require it
  itself; core no longer ships it. Removed with them: `config/livewire.php`, the
  `@livewireScriptsWithoutNavigate` directive (its target view never existed, so calling it
  threw), and `livewire-notification.js` from the common bundle.
- The install wizard's completion screen puts the admin-panel button first and gives it
  the primary colour; both buttons are centred at the same width, and the URLs are not
  repeated under them (they are already on the screen in copy fields). Both buttons submit
  the same finalize form, so a mis-click finished the install and landed the operator on
  the front page — which reads exactly like the bug where an install completed but the
  admin panel was unreachable.
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

#### Fixed

- A successful core update or rollback now regenerates the file-integrity baseline, so the
  daily integrity scan no longer reports every file the release changed as tampering. It
  is regenerated only when the core files matched the baseline before the operation
  started; a tree that was already modified keeps its old baseline and keeps being
  reported. The regeneration is recorded as an `update` scan.
- `dls:core:verify` no longer calls an unsigned core a "development build": core releases
  are not signed yet, so it now says the core carries no signed manifest and cannot be
  checked against a signed release.
- Core updates and rollbacks now write to the audit log: `core_updated`,
  `core_update_failed` (including a preflight refusal), `core_rolled_back` and
  `core_rollback_failed`, with the member who started them, the from/to versions and the
  backup id. Before, only the version ledger recorded them — not the hash-chained log that
  `audit:integrity verify` protects.
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
- **`composer dump-autoload` never ran under PHP-FPM.** Core starts composer as
  `[PHP_BINARY, composer, …]`, and PHP_BINARY is only an interpreter when the running
  SAPI is CLI — under FPM it is the FPM binary, which ignores the script it is handed,
  prints its own usage on stdout and exits 64. Every autoload refresh core fires from a
  web request was therefore a silent no-op on FPM: installing a plugin or theme from the
  admin panel left the map untouched and its classes unresolvable, and a core update's
  re-sync did nothing. The interpreter is now resolved (`App\Support\Process\PhpBinary`)
  rather than assumed, and a failed run logs the command and stdout as well — composer
  and php-fpm both report there, so logging stderr alone said nothing.
- **A fresh install from the release ZIP booted without its extension autoload.**
  `composer.local.json` is generated and gitignored, so it was excluded from the ZIP;
  composer-merge-plugin reads it when Composer initialises, which is before core's
  pre-autoload-dump hook writes it, so the first `composer install` dumped an autoloader
  with no extension PSR-4 roots and nothing dumped again. The bundled theme's
  `ServiceProvider` was unresolvable and the front page answered 500 with
  `Call to undefined function dls_onepage_localized_setting()`. The ZIP now ships
  `composer.local.json`, and `scripts/verify-local-autoload.php` (wired into
  `post-autoload-dump`) dumps once more whenever the map is missing a root the manifest
  declares.
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
