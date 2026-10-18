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
- An audit row written through an action records who did it and to what. `AbstractAction`
  passed the actor and the target under the column names, which `AuditLog::log()` ignored,
  so every action-produced row had NULL `actor_type`, `actor_id`, `target_type` and
  `target_id`; only the display name was kept, and the actor's identity was missing from the
  hash chain. `AuditLog::log()` now also takes those four keys when no model is given, so a
  plugin whose target is not an Eloquent model is not dropped the same way. Rows written
  before this release are not backfilled (#546)
- An administrator signed in to the admin panel is let past the front IP allow list.
  `FrontIpFilter` tested `$member->is_admin`, which `Member` does not have, so the
  bypass never fired and an administrator off the list was refused like any visitor. It now
  lets a member signed in to the admin panel as ADMIN or SUPER_ADMIN past the allow list. The
  block list is no longer bypassed: it applies to everyone, administrators included (#588)
- Erasing a member's personal data no longer makes their audit rows fail verification. The
  erasure rewrote `ip_address`, which the record hash covers, so every chained row of that
  member was reported as tampered after a routine privacy request, and the erasure left no
  row of its own to explain it. The erasure now clears the address instead of hashing it,
  writes a `privacy_data_erased` row naming the operator and the subject by id, and marks
  each row it changed. Verification reports those rows as redacted — still checked for
  their place in the chain and the daily seal — and treats a row marked redacted with no
  recorded erasure behind it as tampering. If the erasure cannot be recorded, nothing is
  erased. Rows anonymised before this release still fail and are not backfilled (#478)

## [0.1.14] — 2026-10-17

### Plugin API — Added
- Ten elements that plugins and themes already used are now listed in `PLUGIN-API.md`, so a
  reference to them is covered by the Plugin and Theme Exception: `AdminHelper`,
  `RevisionService`, `RevisionDiffPresenter`, `ContentEditorPresenter`, `load_plugin_assets()`,
  `@cspNonce`, the `front.ip` middleware, `custom.custom_files_dir`, the `plugin.capabilities` /
  `linkable.providers` / `localized-url.providers` tags, and the `themes::layouts.app` /
  `themes::layouts.preview` layouts. `ContentEditorPresenter`, `load_plugin_assets()` and
  `render_x_cloak_style()` gain their `@api` tags. Core's own translation keys and the
  `multilingual_content` block of `plugin.json` are recorded as not promised (#603, fixes #594)

### Fixed
- The media selector pages through the whole library. It showed only the 20 newest items, so
  "Insert image" in the content editor and every `x-media.picker` could not reach older media.
  It now has previous / next buttons and the page position, and the selection is kept while
  moving between pages. `openMediaSelector()` and the `media-selected` event are unchanged
  (#601, fixes #600)

## [0.1.13] — 2026-10-16

### Added
- The content editor has an "Insert image" button (#570). It opens the media library and puts
  the chosen item at the cursor: `![alt](path)` in Markdown, `<img … loading="lazy">` with its
  width and height in HTML and Blade, and a link for a file that is not an image. Paths are
  root-relative, so content survives a change of domain. `insertImage` turns it off

### Changed
- `<x-form-content-editor>` is the one content editor (#569). It can now carry a split-pane
  live preview, the CSS / JS tabs of the HTML editor, a fixed list of editor types, starter
  templates per language and file storage under one directory, each optional, and its Alpine
  data is the registered `contentEditor` component (CSP-ready). The Markdown, HTML and Blade
  editors share one field. The front page create and edit screens use it; their own
  `splitPaneEditor` and `frontPageCreate` components are gone. `<x-content-editor.storage-info>`
  talks to the editor through window events, so it works from a sidebar. The editor type cards
  fill the row whatever their number. Existing props keep working; `inlineMarkdownPreview`
  turns off the side-by-side Markdown view for a page with a preview of its own
- A single line break in Markdown renders as a line break (`<br>`) on the page, in the server
  preview and in the editor's browser preview (#584). Japanese text is written a clause per line,
  and CommonMark joined such lines into one run of text. Existing Markdown pages, front pages and
  plugin content now show their single line breaks

### Fixed
- Supported locale codes (`ja`, `en`) are reserved route slugs. They are the first segment of
  localized URLs, but a plugin's URL prefix or the admin URL could be set to one. A plugin
  that refused them on its own no longer needs to (#551)
- Database cleanup reads a plugin's entries from `config/admin/database-cleanup.php`, the
  place the documentation, `DeclaresVerifier` and `dls:plugin:permissions` all use. It read
  `config/database-cleanup.php`, so no plugin that followed the rules had its old rows
  cleaned up, and nothing said so. The old path is still read as a fallback (#550)
- Changing a role's access to an admin screen is recorded in the audit log. The role /
  permission matrix and the per-menu reset button wrote their overrides straight from the
  controller, so one of the few settings that changes what another account can do left no
  `audit_logs` row. Both paths now go through an action and record the effective values
  before and after, keyed by menu key, under `role_changed`. A save that changes nothing
  writes no row. The matrix is also validated before anything is written, so a form
  rejected for giving edit permission below view permission no longer leaves the values it
  rejected saved (#474)
- Creating, revoking and regenerating an API key is recorded in the audit log. The three
  operations wrote only to the `admin_activity` file log, which reads as "recorded" while
  the tamper-evident trail had nothing, and a key is a credential that can act on the site
  without a login. They now go through actions and write `api_key_created`,
  `api_key_revoked` and `api_key_regenerated`, naming the key by id and name and — for a
  regeneration — both the old and the new id. No key material (the plain key, its hash or
  its prefix) is ever put in the row. The file log stays as the request trace it always
  was. The unrouted duplicate `AdminApiSettingsController`, which carried the same gap, is
  deleted (#476)
- A confirmation modal that submits a form no longer stays in its saving state when the browser
  refuses the form, such as an empty required field (#580). The modal closes and the browser
  points at the field; a field in the collapsed right sidebar opens the sidebar first
- The editor type cards of the content editor stay on one row when the right sidebar narrows
  the editor (#582)
- A front page can see the member signed in to the admin panel (#585). The guard-aware session
  driver keeps the admin login in `members_sessions` and a front request's session in `sessions`
  under one cookie, so `auth('member')` was false on the front: the admin bar never appeared on a
  theme page, the maintenance banner never appeared, and the lockdown did not recognise them. The
  new `App\Services\Auth\SignedInMemberResolver` reads the member behind the shared session id.
  It is read-only, so the two session rows stay separate and the CSRF token still belongs to the
  row the page was rendered against

### Internal
- A core deploy that moves the bundled theme's lock no longer leaves the theme without its
  built assets. Composer installs the theme from an archive carrying neither `.git` nor the
  build output, and step 4-b restores only the `.git` half: `resources/assets/` is gitignored,
  so neither the archive nor the git checkout holds it. The re-adopt therefore reported
  success while every site served pages with no theme stylesheet or script tag at all — at
  200, and within a few hundred bytes of the normal size, so neither a status check nor a size
  check noticed. The deploy now restores the directory from the copy the theme's own deploy
  already keeps under `_shared/themes/`, the same way step 5 restores core's own build, and
  when there is no copy to restore from it says so and the run goes red rather than reporting
  a clean deploy (#539)
- The bundled theme moves to `v0.4.1`, which adds a CSP self-audit test and a manual
  trigger for its deploy workflow and changes no runtime code. `composer.lock` has to
  name the theme's newest release or the release build refuses to produce the ZIP

## [0.1.12] — 2026-10-14

### Fixed
- `.env.example` ends with a newline, so a setting appended to a copy of it with `>>`
  lands on its own line. Without it the setting continued the last line
  (`HSTS_PRELOAD=falseAPP_KEY=base64:…`). `CheckInstallationReady` reads the key with
  `/^APP_KEY=(.+)$/m`, so it found none — the file's own `APP_KEY=` line is empty and
  does not match either — generated a key and redirected the request to its own URL.
  Every official plugin and theme builds its CI `.env` that way, so the first HTTP
  request of each run was answered with a 302 to itself and checked nothing. An
  operator script that appends a setting to a fresh `.env` is affected the same way,
  and not only for `APP_KEY`. A unit test now pins the newline: `.editorconfig` has
  asked for it all along and did not keep it (#538)

### Internal
- A pull request that changes only Markdown or only `docs/` can be merged again. Two
  workflows produced required status checks from behind a `pull_request` path filter —
  `test.yml` (`Core Tests (PHP 8.3)`) and `generated-docs-drift.yml`
  (`Regenerate and compare`) — and GitHub matches required checks by name against what was
  reported for the head commit. A workflow a filter skips reports nothing at all, not
  "skipped, therefore passing", so the requirement was never satisfied and the pull request
  stayed pending for ever with every check that did run green. Both `pull_request` triggers
  now run unconditionally; their `push` triggers keep their filters, because no check is
  required there. The cost is one suite run on a documentation-only pull request (#561)

- The bundled-theme check now blocks before a release is published instead of failing after
  it. It used to run only inside the release build, which `release: published` triggers, so
  the earliest it could refuse was once the release was already public — and archives are
  attached only at that moment, so a refusal left a release that could never be given any.
  `v0.1.10` went out that way: no ZIP, a 404 on the static
  `releases/latest/download/dixlase-core.zip` link, and a one-line installer falling back to
  its Composer path. A new `Bundled Theme Lock` check now fails any pull request that changes
  `VERSION` while `composer.lock` names a theme release that is not the newest, and the
  release build warns and builds instead of refusing, because a theme one release behind is
  installable while no archive at all is not repairable. The check runs on every pull request
  with no path filter on purpose: a required check whose workflow a filter skips never
  reports, and branch protection then holds the pull request pending for ever. The release
  procedure gains the last-moment re-check and the theme-then-core ordering, because the
  window this does not close is small — 53 seconds, on the day it bit (#557)

- A deploy that moves the bundled theme's locked version no longer fails on a site whose
  theme directory is not group-writable, and a Composer failure no longer skips the rest
  of that site's deploy. Step 1 sets the group write bit on `themes/` after the ownership
  fix: the chown put the web group on the tree but never touched the mode, and the theme's
  own deploy rsyncs its built assets back to mode 755 every time it runs — so this is
  corrected on every deploy rather than once. When Composer fails anyway, the deploy
  records the failure and carries on through the theme re-adoption, the asset copy, the
  permission fixes and the cache clears, instead of aborting mid-replacement and leaving
  the site serving a half-deleted theme as an HTTP 200 with a tiny body. The cache clears
  tolerate a failure for the same reason: with a broken autoload they would otherwise take
  every site after this one down with them (#537)

## [0.1.11] — 2026-10-13

### Fixed
- The release carries its distribution archives again, and a new installation gets the
  theme's current release. The theme published `v0.4.0` 53 seconds before 0.1.10 was
  published, so `composer.lock` still named the theme's `v0.3.0` and the release build
  refused to ship a theme that is not the newest (#517). 0.1.10 was therefore published
  with no assets at all: the versioned ZIP, the `dixlase-core.zip` alias that the static
  `releases/latest/download/` link resolves to, and `checksums.sha256` all answered 404,
  and the one-line installer fell back to its Composer path, which fails outright on a
  host without Composer. `composer.lock` now names the theme's `v0.4.0`. 0.1.10 cannot be
  repaired in place, because archives are attached only when a release is published — so
  install this release instead of 0.1.10. Where that check runs, after publication rather
  than before the tag, is filed as #557

## [0.1.10] — 2026-10-13

### Fixed
- A theme can be linked to an extension source again after the one it came from was
  removed. Removing a source leaves its extensions unlinked, and `dls:theme:update` then
  told the operator to run `dls:theme:download`, which does not exist; a registered theme
  had no way back to a source. `dls:theme:install <directory> --force --source=<id>` now
  re-links it, and the update message names that command. `dls:theme:update` and
  `dls:plugin:update` also refuse with that advice, instead of stopping with a
  `TypeError`, when `source_id` names a source row that is gone, as it can after
  `extension_sources` was rebuilt outside its foreign key (#510)

- Ten `dls:` commands describe themselves again in `php artisan list` and `--help`. Five
  showed a translation key that exists in no language file
  (`command.theme_install.description` and the like, also in their argument help), two
  showed the `make:command` placeholder `Command description`, and three showed nothing,
  because they set the description after Symfony had already read it. A feature test now
  fails on any `dls:` command whose description is empty, a placeholder or a key (#509)

- `dls:theme:list` lists the installed themes again. It read `theme_settings`, the
  key/value store that holds the active theme id, instead of `themes`, so it stopped with
  `Undefined property: stdClass::$name` on any site with an active theme, and reported
  "no themes" on a site without one. It now shows each theme's directory, version and
  whether it is the active one (#508)

### Internal
- Every admin `GET` route core registers is opened in a feature test and asserted not to
  return 5xx, as both an ADMIN and an EDITOR. A page can pass its route and permission
  tests and still crash on open, because those do not run the request lifecycle —
  middleware, controller, view resolution, Blade compile — that a release regression
  surfaces in (#524)

## [0.1.9] — 2026-10-12

### Security
- Extension verification compares the manifest's `files` hashes before it resolves the
  signer's public key. The two halves answer different questions and only the signature
  needs a key, so a result reached without one now still reflects the files on disk, and
  `pending` narrows to its proper meaning: the files are intact but the signer could not
  be confirmed

### Fixed
- A new installation gets the theme's current release. Every delivery path — the one-line
  installer, the ZIP download, `composer create-project` — installs the version
  `composer.lock` records, and that lock had never moved since the theme's first release,
  so 0.1.7 and 0.1.8 shipped a theme from 2026-09-26. The lock now points at the theme's
  latest release, and a release build stops rather than ship an outdated one (#517)

### Internal
- `scripts/check-bundled-theme.php` compares the locked theme version against the theme's
  newest stable tag; `release.yml` runs it, with an opt-out for deliberately rebuilding an
  older tag. `docs/development/releasing.md` and its Japanese mirror describe the
  pre-release step (#517)

## [0.1.8] — 2026-10-12

### Added
- The extension rescan and the audit-log integrity check run on a schedule, and a status
  that gets worse — a health score dropping, a signature that stopped verifying, an audit
  chain that no longer validates — raises a banner in the admin that clears once it is
  resolved (#500)
- `dls:extensions:reconcile-versions` brings the version recorded for each installed
  plugin and theme back in line with its manifest on disk, and reports which ones moved.
  `--dry-run` reports the drift and exits non-zero, so it can be used as a check (#522)

### Changed
- **A production site is deployed from a published release, not from `main`.** The deploy
  workflow triggers on `release: published` and moves each site to that release's tag; it
  also accepts a manual run with a tag, which is how a specific version is placed
  deliberately. A merge to `main` no longer deploys anything (#529)
- Core carries one release workflow again. The second one triggered on every `v*` tag,
  failed on every tag it ran for, and would have created the GitHub release itself with
  `GITHUB_TOKEN` — which raises no `release: published` for anything else — so it had to go
  before the deploy could key on a release (#528)
- An extension installs into the directory its manifest names, instead of a name rebuilt
  from the slug. `dixlase-seo` reaching for `DixlaseSeo` on a case-sensitive filesystem is
  what this fixes (#503)
- `AuditableTrait` is deprecated. Audit entries are produced in the action layer only, so
  one operation can no longer be recorded twice or, worse, once from each of two places
  that disagree (#484)

### Plugin API — Changed
- `CspPolicyRegistry::addDirective()` and `addDirectives()` — and the directives a
  `CspPolicyProvider` returns — now drop sources that would weaken the site's policy
  (`'unsafe-inline'`, `'unsafe-eval'`, a bare `*`, `data:` in script-src, directives such as
  `frame-ancestors`, …) instead of adding them. Dropped values are logged and returned by
  the new `CspPolicyRegistry::getRejected()`. An extension that relied on one of them must
  load the resource from a concrete host or ask the operator to allow it in the CSP
  settings (#494)

### Plugin API — Deprecated
- `App\Traits\AuditableTrait` is deprecated and will be removed in v0.2.0. It wrote an
  audit entry from a model's `created` / `updated` / `deleted` events, and nothing in
  core, the official plugins or the themes ever used it — so two mechanisms appeared to
  audit a change while only one was wired up. Auditing goes through the Action layer:
  put the operation in an `App\Actions\AbstractAction` subclass and let `audit()` write
  the row, or call the `Audit` facade from a service an action invokes. A model that uses
  the trait and is also touched inside an action should wrap the save with
  `$model->withoutAudit(...)` until the trait is gone (#477)

### Fixed
- The version an extension records in the database is reconciled with its manifest. A deploy
  places files but installs nothing, so the recorded version stayed at whatever the install
  wrote — and that value decides whether the admin panel offers an update. A stale row
  therefore offered an update to a version that was already installed, and accepting it
  deletes the extension directory along with the nested `.git` a deploy owns. The
  reconciliation happens during a rescan, so the admin *Rescan* button, the daily schedule
  and a deploy all repair it, and a version that moves without an install is recorded in the
  audit log as a warning (#521, #522, #525)
- The deploy visits every production site and fails instead of skipping one it cannot
  resolve. Each site's layout — Docker installer or shared stack — is now resolved per site,
  which is what let a site go undeployed without the run going red (#516)
- A CSP source declared by an extension is validated before it reaches the policy, and a
  plugin's web routes are registered only on the sites where that plugin is enabled (#501)
- The health and signature gate that guards the admin screens applies to the CLI as well, so
  `dls:plugin:*` cannot enable or update an extension the admin panel would refuse (#498)
- A plugin's `autoload.files` entries are loaded only while the plugin is enabled. A disabled
  plugin's helper file was still required on every request, which turned a removed file into
  a fatal rather than a missing helper (#499)
- Every dangerous API call counts toward the health score, not only the first one found in a
  file (#497)
- The CLI's stale-download warning is translated instead of being emitted in English (#489)

### Documentation
- The plugin security documentation describes the checks the code actually performs (#496)

### Internal
- Core's test suite can build a usable `Member` on its own, without a plugin providing one
  (#512)
- CI no longer passes a secret as the checkout token (#506), the dead `COMPOSER_AUTH`
  plumbing is gone (#491), and the deploy no longer targets the signing-key site (#502)
- `source-map-js` 1.2.1 → 1.2.2 (#504)

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
