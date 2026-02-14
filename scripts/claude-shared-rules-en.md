## Dixlase Coding Rules (Shared)

### View Logic Separation
- Do not directly reference `\App\Enums\*`, `\App\Helpers\*`, `\App\Services\*`, `\App\Models\*` classes in Blade templates
- Prepare all enum values/labels/option arrays, helper results, and service results in controllers and pass them as view variables
- Blade components receive necessary data as props (injected from parent views/controllers)
- Exception: Helper calls in shared full-screen partials (e.g. sidebar) are allowed
- **`@php` blocks are forbidden in admin Blade views** (except shared full-screen partials like sidebar)
  - Business logic, data transformations, and config array building must be done in controllers or Presenters
  - Allowed exceptions: inline expressions (`{{ $var ?? 'default' }}`), `old()` helper, simple variable assignments

### Translation Key Scoping
- `lang/*/admin/navigation.php` is a sidebar-only translation file. Do not reference it from non-sidebar views
- Each view defines its translation keys in its own translation file (e.g. `lang/*/admin/settings/base/index.php`)
- Components define their translation keys in `lang/*/components/<component-name>.php`

### No Inline Scripts / Styles
- **Do not write inline code with `<script>` or `<style>` tags in Blade views**
- Separate JS/CSS into external files under `resources/src/`
- Directory structure by feature/section:
  - `resources/src/admin/` — Admin panel
  - `resources/src/auth/` — Auth pages
  - `resources/src/common/` — Common (shared across all pages)
  - `resources/src/components/` — Reusable components
  - `resources/src/front/` — Front (public-facing)
- JS goes in `js/` subdirectory, SCSS in `scss/` subdirectory
- File names in kebab-case (e.g. `form-color.js`, `ui-modal.scss`)
- Existing inline scripts are being migrated, no need to fix immediately — but **always externalize when creating new or modifying existing code**
