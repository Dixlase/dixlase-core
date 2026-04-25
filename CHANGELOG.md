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

## [0.1.0] — TBD

The first stable Plugin API freeze. This release establishes the public boundary that
plugins and themes can rely on under the AGPL Plugin and Theme Exception (see `LICENSE`).

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

#### Stability infrastructure

- `PLUGIN-API.md` Stability Pledge section documenting the freeze, supported version
  range, deprecation policy, and announcement channels.
- Service class naming convention table (`*Service` / `*Registry` / `*Manager` /
  `*Resolver`) in `PLUGIN-API.md` §9.
- `requires.dixlase: ^0.1.0` declared in all in-tree plugin `plugin.json` files.

### Notes for plugin authors

- Plugins targeting the v0.1 line should declare `"requires": {"dixlase": "^0.1.0"}`
  in their `plugin.json`.
- Internal classes not enumerated in `PLUGIN-API.md` (notably
  `App\Services\Plugin\PluginHealthScorer`, `App\DTO\Plugin\HealthScoreResult`, and
  similar implementation details) are **not** part of the Plugin API. They may
  change in any release without notice.
- `App\Services\Plugin\CoreSignatureVerifier` is intentionally left without `@api`;
  plugins should depend on `App\Contracts\Plugin\SignatureVerifierInterface` instead.

[Unreleased]: https://github.com/Dixlase/Core/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/Dixlase/Core/releases/tag/v0.1.0
