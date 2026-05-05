{{--
This file is part of Dixlase Core DevKit.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$bladePhp = '@' . 'php';
$bladeEndPhp = '@' . 'endphp';
$bladeEcho = '{{ $var ?? \'default\' }}';
@endphp
# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - {{ PHP_VERSION }}
@foreach (app(\Laravel\Roster\Roster::class)->packages()->unique(fn ($package) => $package->rawName()) as $package)
- {{ $package->rawName() }} ({{ $package->name() }}) - v{{ $package->majorVersion() }}
@endforeach

@if (! empty(config('boost.purpose')))
Application purpose: {!! config('boost.purpose') !!}

@endif

@if($assist->hasSkillsEnabled() && $assist->skills()->isNotEmpty())
## Activating Skills

This project has domain-specific skills available. Always activate the relevant skill when working in that domain.

@foreach($assist->skills() as $skill)
- `{{ $skill->name }}` — {{ $skill->description }}
@endforeach
@endif

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Comments
- **Code comments and PHPDoc descriptions must be in English** (project default since v0.1.0).
- Allowed exceptions where Japanese is fine:
  - Test fixtures and inline test data (intentional — exercises i18n)
  - `lang/ja/` translation arrays
  - `*.ja.md` documentation files
  - Bilingual commit-message bullets (optional, see "Git Commit Messages")
  - JP-archive values in `comment-translations/` dictionaries (the JP value of `'EN' => 'JP'` entries is the historical record)
- **When you touch a file that still has Japanese comments**, follow the lazy migration workflow:
  1. `dls:comment:extract --path=<file>` — capture the Japanese as **pending entries** (`'JP' => ''`) in the dictionary
  2. `dls:comment:translate --file=<dict>` — Claude API rotates each pending entry to canonical `'EN' => 'JP'` and tags it `machine` in `_review_status`
  3. Replace the Japanese in the source with the **EN keys** from the dictionary (the keys, not the values)
  4. Stage source and dictionary together
- Don't write new Japanese comments. If you find yourself wanting to, write the comment in English and (optionally) add the term to `comment-translations/_glossary.php` so future translations stay consistent.

## Git Commit Messages
- Do not include `Co-Authored-By` lines in commit messages.
- Use conventional commit format (e.g. `feat:`, `fix:`, `refactor:`).
- **English-only commits are accepted as the default.** A bilingual format (English + Japanese) is also accepted, primarily used by core maintainers to keep the project's bilingual history readable for Japanese-speaking contributors. See the Japanese guidelines (`CLAUDE.ja.md` / `guidelines-ja/foundation.blade.php`) for the bilingual layout if you choose that style.
- Example (English-only):
  ```
  feat: add user profile page

  - Add ProfileController with show/edit actions
  - Create profile Blade views with avatar upload
  ```

## View Logic Separation Rules
- Do not directly reference `\App\Enums\*`, `\App\Helpers\*`, `\App\Services\*`, `\App\Models\*` classes in Blade templates.
- Prepare all enum values/labels/option arrays, helper results, and service results in controllers and pass them as view variables.
- Blade components receive necessary data as props (injected from parent views/controllers).
- Exception: Helper calls in shared full-screen partials (e.g. sidebar) are allowed.
- **`{!! $bladePhp !!}` blocks are forbidden in admin Blade views** (except shared full-screen partials like sidebar).
  - Business logic, data transformations, and config array building must be done in controllers or Presenters.
  - Allowed exceptions: inline expressions like `{!! $bladeEcho !!}`, inline use of `old()` helper, simple variable assignments like `{!! $bladePhp !!} $appearanceValue = old('appearance', ...) {!! $bladeEndPhp !!}`.

## Translation Key Scoping
- `lang/*/admin/navigation.php` is a sidebar-only translation file. Do not reference it from non-sidebar views.
- Each view defines its translation keys in its own translation file (e.g. `lang/*/admin/settings/base/index.php`).
- Components define their translation keys in `lang/*/components/<component-name>.php`.

### Plugin Translation File Structure
- Plugins use a directory-based structure: `lang/{en,ja}/admin/{resource}/{page}.php`.
- `admin.php` should only contain plugin info (plugin, provider). Page-specific translations go in separate files.
- Each admin page translation file must have `heading` and `description` keys at the top:
  ```php
  return [
      'heading' => 'Page Master',
      'description' => 'Manage all pages. ...',
      // other keys
  ];
  ```
- `heading`: Auto-resolved by `resolvePluginHeadingKey()` (converts route name to directory path).
- `description`: A 1-2 sentence description of what can be done on that page.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `{{ $assist->nodePackageManagerCommand('run build') }}`, `{{ $assist->nodePackageManagerCommand('run dev') }}`, or `{{ $assist->composerCommand('run dev') }}`. Ask them.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.
