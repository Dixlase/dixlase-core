# Dixlase CMS

<!-- DIXLASE_RULES_START -->

## Dixlase Coding Rules (Shared)

### Code Comment Language
- **Write all code comments and PHPDoc blocks in English.** This is the project default since v0.1.0.
- Exceptions where Japanese is allowed (and not subject to the migration below):
  - Test fixtures and inline test data containing Japanese strings (intentional — they exercise i18n)
  - `lang/ja/` translation arrays (Laravel translation mechanism)
  - `*.ja.md` documentation files (mirrored bilingual references)
  - Commit-message Japanese bullets when using the optional bilingual format
  - Entries inside `comment-translations/` (those are the source-of-truth Japanese keys)

### Comment Translation Data Maintenance
- When you touch a file that still has Japanese comments, follow the **lazy migration workflow** so the historical Japanese is preserved as translation data while the source flips to English:
  1. `docker exec -i dixlase-php php artisan dls:comment:extract --path=path/to/file.php` — capture the current Japanese comments into the translation file as **pending entries** (`'JP text' => ''`)
  2. `docker exec -i dixlase-php php artisan dls:comment:translate --file=plugins/DixlaseCoreDevKit/resources/comment-translations/path/to/file.php` — let Claude API translate. Each pending entry is **rotated** in place to the canonical `'EN canonical' => 'JP archive'` form and tagged `machine` (unreviewed) in `_review_status`
  3. Replace the Japanese comments in the source with the exact **EN keys** from the dictionary (the keys, not the values — keys are the canonical English that the JA release build will reverse back to JP)
  4. Stage both the source file and the translation file in the same commit
- Storage paths:
  - **Core** (`app/`, `config/`, etc.): `plugins/DixlaseCoreDevKit/resources/comment-translations/`
  - **Plugins**: `plugins/{PluginName}/resources/comment-translations/`
- Translation file mirrors the source path (e.g., `app/Http/Controllers/FooController.php` → `resources/comment-translations/app/Http/Controllers/FooController.php`)
- Dictionary format (Phase 2 onwards): **English-keyed**, JP archive as value
  ```php
  return [
      'Get CSP directives.' => 'CSPディレクティブを取得',     // translated
      '残った日本語の文' => '',                                  // pending (JP key, empty value)

      '_review_status' => [
          'Get CSP directives.' => 'machine',                  // status keyed by EN
      ],
  ];
  ```
- Empty value (`''`) means "pending translation"; the key is JP at this stage. `dls:comment:translate` rotates each pending entry to `'EN' => 'JP'` form
- Project-specific terminology lives in `plugins/DixlaseCoreDevKit/resources/comment-translations/_glossary.php` (JP→EN mapping) — injected into the Claude API prompt so translations stay consistent across files. Add entries when a new Dixlase-specific term emerges or AI translation drifts
- Each translation file may carry a `_review_status` metadata block tracking per-entry review state (`untranslated` / `machine` / `reviewed` / `human`), keyed by the **EN canonical**. `dls:comment:status --filter=machine` lists entries that are auto-translated but not yet human-reviewed; batch review can happen at any cadence
- If the translation file does not exist yet, the extract command creates it. Pre-Phase-2 dictionaries (legacy `'JP' => 'EN'` format) can be migrated in-place with the one-shot `dls:comment:rotate-keys` command (idempotent; pending entries are left untouched)

### License Header
- All new PHP classes and Blade views must include a license header at the top of the file
- **Core**: Use the AGPL v3 license header. Reference: `plugins/DixlaseCoreDevKit/app/Console/Commands/LicenseHeaderCommand.php` or `plugins/DixlaseDevKit/license-templates/license-agpl.txt`
- **Plugins/Themes**: Use the license header defined by each plugin/theme. Reference: `plugins/DixlaseDevKit/license-templates/` and `plugins/DixlaseDevKit/app/Console/Commands/UpdateLicenseHeaders.php`
- PHP files use PHPDoc block format (`/** ... */`), Blade files use Blade comment format (`{{-- ... --}}`)

### Migration Editing Policy

> Release model: the initial public release ships as **Beta 1** (version `0.1.0`); **GA**
> is the first production release, after a beta series (Beta 1 → … → GA). The migration
> lock lands at **GA**, not at Beta 1 — see "Phase transition gate" below.

**GA onward (production phase)**
- **Editing existing migration files is prohibited.** All table changes must be added as new migration files (`ALTER TABLE` style)
- Rationale: preserves the audit trail, enables safe `php artisan migrate --force` against production, keeps rollback possible, and guarantees cross-plugin consistency
- New migration files must use the standard Laravel `YYYY_MM_DD_HHMMSS_*` naming format
- Violations are detected by `php artisan dls:migration:lint` (compares current hashes against `database/migration-lock.json`)

**Up to and including the beta series — Beta 1 → GA (current development phase)**
- Directly editing existing migration files is allowed (schema churn is frequent during this phase)
- Filename prefix must be `0001_01_01_NNNNNN_*` (every file with this prefix is treated as an initial-release migration when the lockfile is generated)
- After schema changes, the developer's local Dixlase Core dev checkout (typically `dixlase-dev-app`) may be rebuilt with `php artisan migrate:fresh` by the operator. **This is a DEV-ONLY convenience** — it applies to the AI author's own working checkout, not to any other container.
- **Downstream sites** (site-local dev containers such as `dixlase-brand-app` / `dixlase-demo-*-app` / `dixlase-docs-app` / `dixlase-authority-app`, staging, or production) **must NEVER be given `migrate:fresh` / `db:wipe` / `migrate:reset` — neither in commands run against them, nor in task instructions sent to their operators / sessions**. For those sites, use `php artisan dls:migration:resync --prune` (dry-run default; `--confirm` to apply; only touches migration ledger rows, never data tables).
- Beta releases do **not** guarantee in-place data upgrades between beta versions **for the developer's local dev checkout**; the operator may `migrate:fresh` there. Downstream sites take the ledger-cleanup path instead.
- **A renumbered file is handled for you; an edited body is not.** Since core 0.3.47 every core `migrate` realigns the ledger with the filenames on disk (`CoreMigrator` → `MigrationLedgerReconciler`), so renaming or renumbering a migration no longer breaks an existing site. Changing what an already-released migration *does* is a different thing: `migrate` will never run that file again, so on every existing site the change silently does not happen — while a fresh install gets it. When you edit the body of a migration that a released version already ran, ship the change as a **new migration as well** (or name a repair step in the release notes). `0001_01_01_999999_add_foreign_key_constraints.php` is the usual place this bites, because new foreign keys are added to it. `dls:migration:lint` cannot catch it during the beta series: it compares against `database/migration-lock.json`, which does not exist yet.

**Phase transition gate**
- Immediately before tagging the **GA** release, run `php artisan dls:migration:lint --lock` and commit the resulting `database/migration-lock.json`
- From that point on, any edits to previously locked migration files will be caught by CI

### Destructive Database Operations Prohibited
- Do not automatically run `php artisan migrate:fresh`, `migrate:reset`, or `db:wipe` during task execution — regardless of which container is targeted, and regardless of which policy document might appear to green-light it.
- **Do not recommend or document destructive commands in task instructions written for other sessions / containers / operators** unless every non-destructive alternative has been exhausted AND the user has explicitly authorized the destructive path for that specific target. Task docs are executable when sent — treat their content as commands you are about to run.
- **Before writing any task doc that touches a database, verify whether a non-destructive Artisan command exists.** For migration-ledger cleanup after a schema-file rename or merge, that is `php artisan dls:migration:resync --prune --confirm` (see the command's `--help`). Reach for `migrate:fresh` only after confirming this and similar alternatives cannot solve the problem.
- **Recorded incident (2026-07-26)**: an AI session recommended `migrate:fresh` in a cross-site rollout doc after mis-reading "Beta 1 policy expects `migrate:fresh`" as applying to every environment. The doc was intercepted before being sent to downstream sites, but the dev checkout's data was lost. See `.backlog/destructive-db-command-incident-2026-07-26.md` for details.
- Creating or editing migration files is allowed, but actual migration execution should be left to the user.

### Test Execution Safety
- **NEVER run `php artisan test` or `vendor/bin/phpunit` against a container whose name does not include `dev` or `test`** (e.g. `dixlase-brand-app`, `dixlase-app` when it points at production data, `dixlase_keys-app`). Tests use `RefreshDatabase`, which runs `migrate:fresh` against the active connection — running against a production-data container DROPs every table.
- Allowed test container: `dixlase-dev-app` (the canonical Core dev environment). Run plugin / theme / core test verification there. If a separate disposable test container is required, give it a name containing `test` and a non-production database.
- Before invoking any `migrate:*`, `db:wipe`, `db:seed`, or test command in any container, state the intent explicitly and re-check the container name in your command.
- `tests/TestCase` ships a static MySQL/MariaDB/Postgres guard that throws when `DB_CONNECTION` points at a forbidden driver. Do NOT bypass it (`DLS_TESTS_ALLOW_NON_SQLITE=1`) against shared or production data — the escape hatch is only for a dedicated disposable test database you own.
- `phpunit.xml`'s `<env>` overrides require `force="true"` to actually overwrite values already present in the process env (otherwise `.env` wins). When you add new test-only env vars, include `force="true"`.
- Prefer `--testsuite=<Name>` over `php artisan test <path>`. Path-direct invocation can bypass `<env>` evaluation depending on the runner, which is how the original incident dropped production tables in May 2026.

### Tests Must Not Touch Tracked Working-Tree Files

The database is not the only thing a test run can destroy. Tests that exercise file-system features (backup, restore, install, import/export, cache warm-up) can delete **tracked repository files** while the suite still reports all green.

- **Never point a test at a real repository directory.** Do not use `base_path('custom')`, `base_path('plugins')`, `base_path('themes')`, `resource_path()`, or any other path inside the working tree as a test fixture target. Create a throwaway directory under `storage/framework/testing/` instead, and remove it in `tearDown()`.
- When the code under test resolves a path from config, **override the config in `setUp()`** so the test cannot reach the real location (e.g. `config(['custom.custom_files_dir' => 'storage/framework/testing/custom-'.uniqid()])`). If the code hardcodes the path, fix the code to read the config rather than pointing the test at the live directory.
- **`custom/README.md` and `custom/README.ja.md` must never be deleted.** They are tracked, they document the `custom/` override mechanism, and nothing in a test run has any business removing them. The same applies to `README.laravel.md` and every other tracked file at the repository root.
- **Run `git status` after any test run or code-generation command** (`dls:plugin-api:generate`, `dls:readme:generate`, `dls:governance:generate`, `dls:claude:setup`). If tracked files show as deleted or modified and you did not edit them, restore them with `git checkout -- <path>` and investigate before continuing. Never stage or commit such a deletion.
- Restore operations are especially dangerous: a restore typically **clears the destination directory before extracting**, so any tracked file the archive happens not to contain is destroyed. This is exactly how `custom/README.md` was repeatedly deleted from working checkouts while the suite stayed green — see the fix in `CoreBackupService` / `CoreRestoreService`, which now resolve the target through `config('custom.custom_files_dir')`.

### Admin Layout Protected Regions
- Do not modify CSS classes of: flex container (no top padding), page header (`pt-6 pb-6 px-8`), article (`mt-5`), sidebar (`md:top-12`) in `resources/views/layouts/admin.blade.php`
- These offsets align with the admin bar (`h-12`) — changes affect every admin page

### Issue First — Open an Issue Before Editing Source
- **Before editing the source of core or of an official plugin / theme, there must be a GitHub issue for the change.** Find the existing one, or ask the user whether to open one; do not start editing without it
- The branch and the pull request point at the issue: write `Fixes #N` (or `owner/repo#N` across repositories) in the PR description
- **Exceptions** — no issue needed; the PR description says so on its own line, e.g. `No issue: release`:
  - `security` — an unfixed vulnerability (fixed privately, published as a Security Advisory afterwards; never file it as a public issue)
  - `release` — version bumps and changelog entries for a release
  - `signing` — re-signing a plugin or theme
  - `dependencies` — dependency updates, including Dependabot PRs
  - `generated` — regenerating generated files with no hand edits
  - `typo` — a typo fix that changes no meaning
- **Something else turns up while working:** do not fix it in the same change — open (or propose) a separate issue. One PR answers one issue
- Write the issue per `docs/development/issues.md` in core (title `<area>: <English> / <日本語>`; known-issue body: all English sections, `---`, then all Japanese sections)
- CI (`pr-issue-link.yml`, reusable from `Dixlase/.github`) fails a PR whose description has neither an issue reference nor a `No issue: <reason>` line; HTML comments are ignored

### Branch Creation / Switching Requires Confirmation
- **Always confirm with the user before creating a new branch** — do not run `git checkout -b`, `git switch -c`, `git branch <new>`, etc. on your own
- Confirm: whether to branch at all, the branch name, and the base branch
- Switching to an existing branch (`git checkout <existing>`) also requires confirmation when uncommitted work could be affected
- Exception: when the user explicitly names the branch (e.g. "create branch `task-3-foo`")

### Sync Local Checkout Before Branching
- In long-lived development checkouts (e.g., `dixlase-core` under `html/`, shared multi-developer plugin/theme repos), **always sync with remote before cutting a feature branch**
- A clean working tree and "up to date" are different — `git status` showing `## main...origin/main` only means "local matches the origin pointer at the time you last fetched", not "local matches upstream right now"
- Required pre-branch check:
  ```bash
  git fetch origin
  git log --oneline HEAD..origin/<base-branch> | head   # empty output = OK to branch
  git checkout -b <new-branch>
  ```
- If `git log HEAD..origin/<base>` has unfetched commits, fast-forward / rebase the local base first, then branch
- Skipping this step is the #1 cause of duplicate-implementation PRs that re-do already-merged changes

### Pull Request Number in Merge / Squash Commits
- When a change lands through a pull request, the **PR number (`#N`) must appear in the resulting merge or squash commit subject** — every commit in `main`'s history should be traceable back to its PR by number.
  - GitHub's *Create a merge commit* (`Merge pull request #N from …`) and *Squash and merge* (`<subject> (#N)`) both include `#N` by default. Keep that default; never strip it or rewrite the subject without it.
  - When you merge a PR's branch locally instead (`git merge <branch>`), add the number to the message yourself — a trailing `(#N)` on the subject, or a `PR #N` / `Closes #N` line in the body.

### Core Version Bump Policy
- In Dixlase core, the on-disk version lives in **two** files that must always match: `VERSION` and the `version` field of `dixlase.json`
- Always bump both together with `php scripts/bump-version.php <x.y.z>` — never hand-edit only one of them
- CI (`tests/Unit/VersionManifestConsistencyTest.php`) fails when the two disagree
- The core update pipeline applies both files (`CoreSourceSnapshot::SOURCE_FILES`) and a rollback restores both; on a live instance, `VersionDriftService` reports a mismatch as `manifest_drifted`
- Plugins and themes carry the same kind of pair: the manifest (`plugin.json` / `theme.json`, which core reads first) and `composer.json` (top-level `version` for a plugin, `extra.dixlase.version` for a theme)
- Bump an extension with its own `php scripts/bump-version.php <x.y.z>`, run from the extension root — it also keeps `package.json` in step when one exists
- The extension's `tests/Unit/VersionManifestConsistencyTest.php` fails CI when the manifest and `composer.json` disagree (`package.json` is not checked — nothing reads its version)
- `dls:make:plugin` / `dls:make:theme` generate both files, so new extensions start with the guard
- A plugin signature covers `composer.json` and `package.json`, so re-sign a signed plugin after every bump

### Core Update vs. Theme Update (bundled themes are bootstrap-only)
- **A core update never changes an installed theme.** `CoreUpdater::applyBundledThemes()` copies a theme declared in the release manifest (`.dixlase-release.json`, produced by the `[bundle-theme]` release-notes marker) into `themes/<slug>` **only when that directory does not exist yet** (fresh install-from-release)
- When the theme is already installed, the updater skips it entirely: no file overwrite, no `Theme.version` change, no snapshot — even if the bundled copy is newer or older. Do not add a version comparison; the operator decides when a theme moves
- Theme changes go exclusively through `dls:theme:update` / `dls:theme:rollback` (independent, operator-controlled). Core and theme versions are independent
- Rollback of a failed core update only removes themes that this update bootstrapped (they did not exist before); it never restores or touches a pre-existing theme
- Tests for this path must use a throwaway tree under `storage/framework/testing/` (see `tests/Unit/Services/Core/CoreUpdaterBundledThemesTest.php`), never the real `themes/`

### Git Investigation Tools — `git log -S` does not detect in-file moves
- When investigating "who recently changed X", `git log -S '<term>'` (pickaxe) only catches commits where the **occurrence count** of the string changed
- If a config entry simply **moved within the same file** (e.g., an SPDX identifier moved from the refused list to the accepted list), `-S` shows nothing — the occurrence count is unchanged
- Use these alternatives instead:
  - `git log -- <path>` — full history of that file
  - `git log -G '<regex>'` — commits whose diff added/removed lines matching the regex (catches moves too)
  - `git log -p -- <path>` — read the actual diffs

### Git Operations Safety (Plugin/Theme Protection)
- Plugins and themes are **independent git repos** inside Core. From Core's perspective, their files are untracked
- **Never run `git stash --include-untracked` or `git clean` in Core** — deletes all plugin/theme files
- Recovery: `for dir in plugins/*/; do [ -d "$dir.git" ] && (cd "$dir" && git checkout -- . 2>/dev/null); done`

### Submodule (Plugin/Theme) Gitlink Management

#### Correct update sequence (required)
1. Make changes inside the plugin/theme → commit
2. **Push the plugin/theme to remote** (skipping this step causes CI to reference an unreachable commit and fail)
3. Return to Core and run `git add plugins/{Name}` to update the gitlink
4. Commit → push Core

#### Prohibited actions
- **Rewriting commits in plugin/theme via force-push, `git commit --amend`, or `git rebase`**
  (Core's gitlink then points to a commit that no longer exists on the remote, causing CI to fail with `not our ref`. This actually happened with DixlaseSEO.)
- Manually editing `.gitmodules` (hotbed of `.gitmodules` / gitlink mismatches)
- Running `git add` directly on an existing cloned `plugins/X/` (creates a gitlink without registering it in `.gitmodules`)

#### Correct add/remove commands
- Add: `git submodule add <url> plugins/{Name}` (keeps `.gitmodules` and gitlink in sync)
- Remove: `git submodule deinit plugins/{Name} && git rm plugins/{Name}`

#### In-development plugins/themes
- For in-development plugins/themes that must stay out of CI: **do not register them in `.gitmodules` and do not create a gitlink**
- Each developer clones them locally as needed
- Once stable enough to publish, register them properly via `git submodule add`

#### Consistency check before pushing to remote (recommended)
Run the following to verify every gitlink is reachable on its remote and that `.gitmodules` matches the recorded gitlinks:
```bash
# 1. Verify .gitmodules <-> gitlink consistency
diff \
  <(git ls-tree HEAD | awk '$2=="commit"{print $4}' | sort) \
  <(git config -f .gitmodules --get-regexp '^submodule\..*\.path$' | awk '{print $2}' | sort) \
  || echo "WARN: .gitmodules and gitlinks are inconsistent"

# 2. Verify each gitlink commit is reachable on its remote
git submodule foreach --quiet \
  'git fetch --depth=1 origin $sha1 2>/dev/null || echo "WARN: $name sha $sha1 not pushed to remote"'
```

### Autoload Regeneration After Manually Cloning a Plugin/Theme or Switching Its Branch
- When you place a plugin/theme by **cloning it from GitHub** (instead of installing via the admin panel or `artisan plugin:install`), you must regenerate the autoloader yourself before the plugin can run
- **Run the same command after checking out a different branch, tag or commit inside a plugin/theme.** `composer.local.json` is generated from each extension's `composer.json` at one point in time, so a branch switch silently leaves it describing the branch you left
- Plugin classes (`Plugins\{Name}\App\...`) are autoloaded via `composer.local.json`, which maps `Plugins\{Name}\App\` → `plugins/{Name}/app` and is merged into the root autoload by `wikimedia/composer-merge-plugin`. This file is auto-generated; a raw `git clone` does **not** update it, so the classes stay unresolvable
- Symptom: routes register fine (plugin route files are scanned from disk), but dispatching to the plugin's controller throws `BindingResolutionException: Target class [Plugins\...\SomeController] does not exist`
- The admin-panel / `plugin:install` / `plugin:delete` paths run this automatically (`ComposerLocalHelper::syncAutoload()`). A manual clone does not — run it yourself from the Core root (`html/`):
  ```bash
  php scripts/sync-local-autoload.php && composer dump-autoload --no-scripts
  ```
  - `sync-local-autoload.php` rewrites `composer.local.json` from the current `plugins/` + `themes/` dirs (framework-independent, no DB needed); `--no-scripts` skips the `package:discover` post-hook that requires a DB connection
- Caveat: a bare `composer dump-autoload` needs **two passes** — composer-merge-plugin reads the old `composer.local.json` before the `pre-autoload-dump` hook regenerates it, so the first pass misses the newly cloned plugin. The one-liner above avoids this by rewriting the file *before* composer starts
- A stale `autoload.files` entry is a **hard fatal, not a degraded feature**: composer `require`s those files unconditionally, so one missing file takes down every request and every `artisan` command with `Failed opening required '.../SomeHelper.php'` — a white screen, not a missing helper. Checking a plugin out at a branch that predates one of its helper files reproduces this exactly, and so does merging that file back in afterwards (the entry is then missing while the file exists, and the helper is undefined wherever it is called). Re-run the command above after either move
- Extensions can keep that blast radius inside themselves by `require_once`-ing their helper files from the ServiceProvider's `register()` instead of declaring them in `autoload.files`
- `composer.local.json` and `vendor/` are gitignored, so this produces no committable change — it is a local-environment step only

### CLAUDE.md Regeneration
- After editing shared rules (`resources/ai/shared-rules-*.md`) or core-specific rules, run `dls:claude:setup` to regenerate
- Do not edit CLAUDE.md directly — source of truth is each rule file

### Plugin/Theme Creation via Commands
- When creating a new plugin, always use `dls:make:plugin` command — do not manually create files
- When creating a new theme, always use `dls:make:theme` command — do not manually create files
- These commands generate the standard scaffold (plugin.json, ServiceProvider, composer.json, config, routes, lang, tests, vite.config, etc.)
- If the command has issues or lacks needed features, fix the command itself rather than working around it manually
- After scaffold generation, add custom files (Services, Controllers, views, etc.) on top of the generated structure

### Plugin Permission Declarations (plugin.json permissions) Sync
- **When adding code that uses core features, always update `plugin.json` `permissions` simultaneously**
  - Example: Adding mail (`Mail::to()`) → set `mail.send` to `true`
  - Example: Using `storage_path()` or `Storage::disk()` → set `storage.own_directory` to `true`
  - Example: Adding migrations → set `database.own_tables` to `true`
  - Example: Adding Artisan commands → set `system.register_commands` to `true`
- Mismatches between declared permissions and actual code are detected by the plugin scanner and reduce health scores

### Cross-Plugin/Theme Data Access
- **Never reference plugin internals directly** from other plugins or themes (`\Plugins\PluginName\*` models, services, etc.)
- Always access plugin data through core Contract+DTO (`App\Contracts\PluginIntegration\*` + `App\DTO\PluginIntegration\*`) or core APIs
- Plugins register capabilities via `plugin.capabilities` tag, resolved through `PluginServiceResolver`
- If a needed Contract doesn't exist, create it in core first, then implement in the plugin
- This ensures security scan compliance, loose coupling, and graceful fallback when plugins are uninstalled

### Tailwind CSS v4
- This project uses **Tailwind CSS v4** with CSS-first configuration (`@theme`, `@source`, `@variant` in CSS files)
- There is no `tailwind.config.js` — all configuration is in `resources/src/common/css/tailwind.css` (core) or `resources/src/front/css/tailwind.css` (theme)
- `@apply` is supported via `@reference "tailwindcss"` in SCSS entry files, but **prefer CSS variables** for new code:
  - Instead of `@apply text-gray-700 px-4`, use `color: var(--color-gray-700); padding-inline: calc(var(--spacing) * 4);`
- When using `@apply` in SCSS, the entry SCSS file must include `@reference "tailwindcss"` after all `@use` statements
- Dark mode uses class-based strategy: `@variant dark (&:where(.dark, .dark *))`
- SVG elements have `display: block` in v4 Preflight (changed from v3) — use `shrink-0` and explicit flex alignment when needed

### Blade Component Usage
- Use core Blade components from `resources/views/components/` instead of hardcoding form elements and UI parts
  - **Form**: `<x-form-text>`, `<x-form-textarea>`, `<x-form-select>`, `<x-form-toggle>`, `<x-form-radio-card-group>`, `<x-form-button>`, `<x-form-label>`, `<x-form-error>`, etc.
  - **UI**: `<x-ui-modal>`, `<x-ui-notification>`, `<x-ui-status-badge>`, `<x-ui-pagination>`, etc.
  - **Admin**: `<x-admin.save-button>`, `<x-admin.delete-button>`, `<x-admin.danger-zone>`, etc.
- **Checkbox / Toggle**: Use `<x-form-toggle>` for all boolean inputs. No inline HTML toggles
- **Radio buttons**: Prefer `<x-form-radio-card-group>`. Use `xModel` prop for Alpine.js binding
- Before creating a new component, check for reusable existing components

### Event Handlers and CSP Compliance
- Inline event handlers (`onclick`, `onchange`, `onsubmit`, `onpaste`, etc.) are **forbidden** — they violate CSP `script-src-attr 'none'`
- Use Alpine.js directives (`@click`, `@change`, `@submit`, `@paste.prevent`, etc.) instead
- Paste prevention: use `@paste.prevent` instead of `onpaste="return false;"`
- Back button: use `@click="history.back()"` instead of `onclick="history.back()"`
- Add `@cspNonce` to `<script>` tags (`<script @cspNonce>`)

### Alpine.js — strict-mode-ready coding (write for `@alpinejs/csp`)
- Dixlase will switch the Alpine bundle to `@alpinejs/csp` in a future major release. New code must already follow that build's restrictions so the eventual switch is mechanical.
- **Do not write new `x-data="{...}"` literals.** Register the data object with `Alpine.data('name', () => ({ ... }))` in a JS module and reference it by name: `x-data="name"`.
  - Page-specific data: alongside the page's JS entry (`resources/src/admin/{area}/js/{page}.js`).
  - Cross-page data: `resources/src/common/js/alpine-data/{name}.js`, imported from the area bootstrapper.
  - Prefer reusing core-provided shared registrations (`accordion`, `toggle`, `tabs`, `modal`) before defining new ones.
- **Do not write `x-init="...JS expression..."` with logic.** Define an `init()` method on the registered data object instead.
- **Avoid object literals in directive expressions.**
  - Bad: `:class="{ active: open }"`, `@click="$dispatch('foo', { bar: baz })"`
  - Good: `:class="classes()"`, `@click="emitFoo()"` (with the method on the registered data object)
- **No arrow functions, template strings, or chained complex expressions in directives.** Wrap them in methods.
- Existing literal-form `x-data` is migrated opportunistically when files are touched. Run `php artisan dls:csp:scan-alpine` to check the current inventory and per-area progress.
- This rule applies to core, plugins, and themes equally. The CSP build switch will be ecosystem-wide.

### Plugin API Registration and @api Tag
- When creating shared components (Blade components, contracts, services, etc.) in core that plugins or themes may use, **always register them in `PLUGIN-API.md`**
- Add the `@api` tag to the source file header to indicate it is part of the Plugin API:
  - PHP files: `@api` in the PHPDoc block
  - Blade files: `@api` inside the license comment block (`{{-- @api ... --}}`)
- Without registration in `PLUGIN-API.md`, plugins/themes using the component may not be covered by the AGPL license exception
- **`PLUGIN-API.md` is generated — do not edit it directly**
  - Edit instead: `plugins/DixlaseCoreDevKit/resources/ai/plugin-api/template-{en,ja}.blade.php`
  - Translation keys: `plugins/DixlaseCoreDevKit/lang/{en,ja}/plugin-api.php`
  - Regenerate with: `php artisan dls:plugin-api:generate --lang=en` and `--lang=ja`
- Run `dls:claude:setup` after updating `PLUGIN-API.md` to regenerate CLAUDE.md files

### Documentation Placement
- Default language is English. Core: `docs/`, Plugin: `plugins/{Name}/docs/`
- Japanese: add `docs/ja/` mirroring the same structure
- **Work plans**: Place Claude Code work plans in `.claude/plans/` (gitignored), NOT in `docs/`
- `docs/` is for official documentation only — do not mix with temporary work plans

### Sandbox Tests (Claude Code Working Tests)
- When writing test files during coding tasks, place them in `tests/Sandbox/` instead of `tests/Feature/` or `tests/Unit/`
- This applies to **all repositories** (core, plugins, themes) — do not modify the existing CI/CD test suite
- `tests/Sandbox/` is gitignored and excluded from CI/CD test suites
- Directory structure mirrors the standard test layout: `tests/Sandbox/Feature/`, `tests/Sandbox/Unit/`
- Run sandbox tests explicitly: `php artisan test tests/Sandbox/` or `--filter=testMethodName`
- If a sandbox test should become permanent, move it to `tests/Feature/` or `tests/Unit/` after review

### Artisan Cache Commands Execution Environment
- `php artisan config:cache`, `route:cache`, and `view:cache` must **always be run inside the Docker container**
- Running on the host caches host-side paths, which mismatch the container's paths and cause errors at runtime
- Correct pattern: `docker exec <your-php-container> php /var/www/html/artisan config:cache` (replace with the container name and base path used by your environment)
- GitHub Actions deploy workflows must also execute these commands inside the container

### Rebuild View Cache After Clearing
- When running `php artisan view:clear` or `optimize:clear` during a task, **immediately follow up with `php artisan view:cache` to rebuild**
- Reason: In development (`npm run dev`), Vite watches `storage/framework/views/`. If Laravel writes new compiled Blade files to that directory while a page navigation is in flight, Vite sends a `[vite] page reload` signal that cancels the navigation (this is the "clicking a menu re-shows the current page" symptom)
- Pre-compiling all views with `view:cache` prevents on-the-fly compilation during navigation
- Applies to: `php artisan view:clear`, `php artisan cache:clear` (also clears views), `php artisan optimize:clear`
- One-liner pattern: `docker exec <your-php-container> bash -c "php artisan view:clear && php artisan view:cache"`

<!-- DIXLASE_RULES_END -->
