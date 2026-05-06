# Comment Translation

Dixlase ships with **English source comments as the canonical form** and a per-locale comment archive that can be applied to a development checkout in-place. This document describes how the system works, how to use it, and how plugin and theme authors can plug their own dictionaries into the same pipeline.

The Japanese mirror of this page lives at [`docs/ja/development/comment-translation.md`](../ja/development/comment-translation.md).

---

## TL;DR

```bash
# Convert all source comments (core + plugins + themes) to Japanese
./convert-comments.sh ja

# Revert back to English
./convert-comments.sh ja --reverse

# List available locales
./convert-comments.sh --list
```

The substitution is AST-aware (only PHP comment tokens are rewritten, never string literals) and idempotent (running again on already-converted source is a safe no-op).

---

## Layout

Each "extension" (Core, a plugin, or a theme) carries its own per-locale dictionary alongside its source code:

```
resources/comment-translations/{locale}/...                 # Core
plugins/{Name}/resources/comment-translations/{locale}/...  # Plugin
themes/{Name}/resources/comment-translations/{locale}/...   # Theme
```

Inside a locale directory the structure mirrors the source it describes, plus one metadata file:

```
resources/comment-translations/ja/
├── _glossary.php                         # Project-wide terminology
├── app/
│   ├── Console/Commands/AppUninstall.php
│   ├── Traits/LoginTrait.php
│   └── ...
├── bootstrap/app.php
├── config/csp/base.php
├── database/migrations/...
└── routes/admin.php
```

Conventions:

- **Each `*.php` file mirrors the path of the source file it translates.** A dictionary at `ja/app/Models/Foo.php` translates comments in `app/Models/Foo.php`.
- **Files named `_glossary.php` (underscore-prefixed) are metadata, not per-source dictionaries.** They are skipped by the file walker. Reserve underscore-prefixed names for future metadata kinds (e.g. `_changelog.php`).
- **Locale directory names follow ISO 639-1 codes** (`ja`, `zh`, `ko`) so they can serve as the `--locale=` argument unchanged.

---

## Dictionary Format

A per-source dictionary is a plain PHP array of `'EN canonical' => 'archive in target locale'`:

```php
<?php

return [
    'Apply translated comments to PHP source.' => 'PHP ソースに翻訳済みコメントを適用します。',
    'Two output modes:' => '2 つの出力モード:',
    '- copy mode (default)' => '- コピーモード (既定)',

    // Pending entry: empty string means "JP captured but no canonical EN yet".
    // Pending entries are filtered out during build and reported by status --strict.
    '本番環境専用の動作' => '',

    // Metadata block. The keys here are EN canonicals (matching dictionary keys
    // above), the values are review-status labels.
    '_review_status' => [
        'Apply translated comments to PHP source.' => 'human',
        'Two output modes:' => 'machine',
    ],
];
```

Values are interpreted as follows:

| Value | Meaning |
|---|---|
| Non-empty string | Translated. Used by `dls:comment:build` to render the locale-flavoured copy. |
| Empty string `''` | Pending. The dictionary key is treated as raw source text awaiting translation; ignored at build time and counted by `dls:comment:status`. |

The `_review_status` block tracks per-entry review state without disturbing the translation array. Allowed values are:

- `untranslated` — pending, not yet sent for translation
- `machine` — auto-translated by Claude API, awaiting human review
- `reviewed` — human-confirmed (no edits needed)
- `human` — human-written from scratch

`dls:comment:status --filter=machine` lists entries needing review.

---

## The `_glossary.php` File

`{locale}/_glossary.php` injects project-specific terminology into the translation prompt. When `dls:comment:translate` calls Claude API, terms appearing in the input batch are accompanied by their preferred renderings, so the AI doesn't paraphrase them differently across files.

Format (mirrors the per-source format with EN canonicals as keys):

```php
return [
    'Core' => 'コア',
    'plugin' => 'プラグイン',
    'admin panel' => '管理画面',  // Not "管理ダッシュボード"
    'member' => 'メンバー',        // End user (NOT "user", which is system-level)
];
```

Keep the glossary lean: only Dixlase-specific terms or terms whose default AI translation drifts. Adding general programming vocabulary inflates the prompt without improving quality.

Per-language glossaries are independent — `ja/_glossary.php` and a future `zh/_glossary.php` both share the same EN keys but list locale-appropriate translations.

---

## Commands

The pipeline has four Artisan commands. Two ship in Core (user-facing); two live in the DixlaseCoreDevKit plugin (developer-only).

### `dls:comment:build` (Core)

Apply per-locale translations to PHP source.

```bash
# Copy mode: write translated source to dist/{locale}, leave canonical EN untouched.
php artisan dls:comment:build --locale=ja --output=dist/ja

# In-place mode: rewrite source files directly.
php artisan dls:comment:build --locale=ja --in-place

# Reverse: revert an in-place conversion (locale → EN).
php artisan dls:comment:build --locale=ja --in-place --reverse

# Walk plugins and themes too (default off).
php artisan dls:comment:build --locale=ja --in-place --include-plugins --include-themes

# Preview only.
php artisan dls:comment:build --locale=ja --in-place --dry-run
```

Substitution is AST-aware: only PHP comment tokens (`T_COMMENT`, `T_DOC_COMMENT`) are rewritten. String literals containing the same text are left intact, so user-facing strings that happen to match a dictionary key are safe.

### `dls:comment:status` (Core)

Display translation progress.

```bash
# Per-directory summary table + review-status breakdown.
php artisan dls:comment:status

# Strict mode: exit 1 if any pending entries exist (used by release CI).
php artisan dls:comment:status --strict

# Filter individual entries.
php artisan dls:comment:status --filter=machine
php artisan dls:comment:status --filter=untranslated --limit=20
php artisan dls:comment:status --filter=unreviewed   # alias: machine + unmarked-translated
```

### `dls:comment:extract` (DevKit)

Scan PHP source for Japanese comments and add them to the dictionary as pending entries (`'JP' => ''`). Used when authoring a new locale by populating it from existing JP source — not needed in normal day-to-day work now that core source is canonical English.

```bash
php artisan dls:comment:extract --path=app/Traits
```

### `dls:comment:translate` (DevKit)

Send pending entries to Claude API and rotate the dictionary to `'EN' => 'JP'` form. Requires `ANTHROPIC_API_KEY` in `.env`.

```bash
php artisan dls:comment:translate
php artisan dls:comment:translate --file=resources/comment-translations/ja/app/Models/Foo.php
php artisan dls:comment:translate --force   # re-translate already-translated entries
```

---

## The `convert-comments.sh` Wrapper

`./convert-comments.sh` is a thin host-side bash wrapper around `dls:comment:build --in-place`. It exists so non-developers don't need to know about Artisan, Docker, or container names.

```bash
./convert-comments.sh ja                      # EN → JA
./convert-comments.sh ja --reverse            # JA → EN
./convert-comments.sh ja --dry-run            # preview only
./convert-comments.sh ja --strict             # refuse if pending entries exist
./convert-comments.sh --list                  # show available locales
./convert-comments.sh ja --no-plugins         # core only
./convert-comments.sh ja --no-themes          # core only (combine with --no-plugins)
./convert-comments.sh ja --path=app/Models    # restrict scan to a sub-path
```

Defaults: `--include-plugins` and `--include-themes` are ON. The script preflights for Docker presence and the PHP container, then runs `docker exec <container> php artisan dls:comment:build ...`.

Override the container name via the `DIXLASE_PHP_CONTAINER` environment variable:

```bash
DIXLASE_PHP_CONTAINER=my-php convert-comments.sh ja
```

A `convert-comments.bat` is also provided for Windows.

---

## Authoring Translations for a Plugin or Theme

Plugins and themes can ship their own per-locale dictionaries. The pipeline picks them up automatically when `--include-plugins` / `--include-themes` is passed (which is the default in `convert-comments.sh`).

### Steps

1. **Decide which locales you support.** At minimum, ship one locale matching the source language of your comments.

2. **Create the dictionary directory** at the same relative path as Core uses:

   ```
   plugins/MyPlugin/resources/comment-translations/ja/
   ├── _glossary.php
   └── app/
       └── Models/
           └── Foo.php
   ```

3. **(Optional) Add a `_glossary.php`** with plugin-specific terminology:

   ```php
   <?php

   return [
       'webhook' => 'ウェブフック',
       'payload' => 'ペイロード',
   ];
   ```

   Plugin glossaries supplement the core glossary; both are merged at build time.

4. **For each source file with comments worth translating, create a dictionary file** mirroring the source path:

   ```
   plugins/MyPlugin/app/Models/Foo.php
   ↓
   plugins/MyPlugin/resources/comment-translations/ja/app/Models/Foo.php
   ```

   Format:

   ```php
   <?php

   return [
       'Webhook delivery state.' => 'Webhook 配送状態。',
       'Retry on failure.' => '失敗時に再試行する。',
   ];
   ```

5. **Verify locally** with `./convert-comments.sh ja --include-plugins --include-themes` and confirm `git status` shows your plugin's source updated.

6. **Round-trip check:** run with `--reverse` and confirm `git status` is clean again. If not, you have either a typo (search/replace not finding the text) or a many-to-one mapping that broke reversibility.

### Authoring Tips

- **Match comment text exactly.** The substitution is whole-string match per token. Trailing periods, smart quotes, and surrounding whitespace inside the comment all count.
- **Avoid one-EN-to-many-JP mappings within the same dictionary.** Reverse won't be deterministic.
- **For comments that span multiple lines in PHPDoc**, each physical line is a separate dictionary entry. Keep the entries aligned line-by-line so re-flowing the EN doesn't desynchronise the JA.
- **Don't put runtime translatable strings here.** Strings rendered to users belong in `lang/{locale}/...` (Laravel's translation system), not in comment-translations.

### What Not to Translate

- License headers (`// This file is part of...`) — stable across locales, conventionally English.
- Section dividers (`// ====`, `// ---`).
- Single-character comments and trivial markers.

---

## Adding a New Locale

To add `zh` (Chinese) for Core:

1. Create `resources/comment-translations/zh/_glossary.php` with EN keys and ZH renderings.
2. (Optional) Use the DevKit pipeline to seed dictionaries from the canonical EN source, asking the AI for ZH translations:

   ```bash
   # The translate command currently expects JP-keyed pending entries; for
   # forward translation from EN to a new locale you may need to author
   # dictionaries by hand or via a custom script that re-targets the API.
   ```

3. Verify with `./convert-comments.sh zh --dry-run`.
4. Submit a PR — the dictionary lives in the same repository, so improvements are reviewed alongside source changes.

The `dls:comment:extract` / `:translate` pipeline was originally designed for the JP→EN sweep that produced the canonical EN source. Forward-translation to non-JA locales currently uses the same direction-agnostic glossary infrastructure but may require small command-side adjustments depending on workflow. Track support for new locales as a follow-up if you hit friction.

---

## CI Enforcement

| Trigger | Workflow | Mode | Effect |
|---|---|---|---|
| PR / push | `.github/workflows/test.yml` (job: Comment Translation Coverage) | warning | Coverage printed to job log; PR not failed even if pending entries exist |
| `v*` tag | `.github/workflows/laravel-ci.yml` (step: Comment translation coverage) | strict | Release ZIP build fails if any pending entries exist |

So PRs may carry work-in-progress translations, but a tagged release cannot ship with translation gaps.

---

## Releases

Dixlase Core releases ship with **English source comments only**. The dictionaries are part of the source tree (so the JA build can be reproduced from any tag), but the release tarball is not pre-translated.

Two intended user paths:

1. **End users via `DixlaseDockerInstaller`**: the installer prompts for a locale during `setup.sh` and runs `convert-comments.sh ja` automatically after cloning Core. The user never sees the conversion step.

2. **Direct clone**: `git clone` then `./convert-comments.sh ja` if the user wants Japanese.

Either way, the canonical commit history stays in English and the locale flip is a derived, reversible artifact.

---

## Architecture Notes

- **Service classes** live in `plugins/DixlaseCoreDevKit/app/Services/CommentTranslation/` (DevKit must be installed for the pipeline to function):
  - `TranslationFileService` — read/write/merge dictionaries; locale state via `setLocale()` / `getLocale()`.
  - `CommentBuilderService` — apply substitutions via `applyToContent()` (token-AST aware) or `applyExtension()` (per-extension walker).
  - `ClaudeTranslationService` — Claude API client for `dls:comment:translate`.
  - `CommentExtractorService` — PHP-Parser-based comment extraction.

- **`App\Services\CommentTranslation\ExtensionDictionaryLocator`** (Core) returns the list of dictionary roots for a given locale across Core, plugins, and themes. Used by both `dls:comment:build` and `dls:comment:status`.

- **Storage path resolution**: `TranslationFileService::__construct()` reads `core-dev.comment_translation.storage_path` (default `resources/comment-translations`) from config and combines it with the active locale (default from `core-dev.comment_translation.default_locale`, default `ja`).

- **Idempotency**: substitution looks up exact strings. Once a comment is in the locale, the EN canonical is no longer in the source, so a second forward run finds nothing to substitute. Same for reverse from EN.
