# `custom/` — Site-specific overrides

For Japanese, see [README.ja.md](./README.ja.md).

This directory is the supported place to customize Dixlase **without
modifying core, plugin, or theme source files** — so cryptographic
signatures on those distributions remain intact.

The signer (`dls:signer:sign --force`) only hashes files inside
`plugins/{Plugin}/` and `themes/{Theme}/`. It never reaches into
`custom/`. Anything you place here is invisible to signature checks.

## What works today (v0.1.0)

| Surface | View override | Logic override (PHP) | Config / lang override |
|---|---|---|---|
| **Core** | ✅ `custom/resources/views/...` | ✅ `custom/app/...` via `Custom\App\` | ✅ `custom/lang/`, `custom/config/` |
| **Plugin** | ✅ `custom/plugins/{Plugin}/resources/views/` | ❌ deferred (see backlog) | ✅ `custom/plugins/{Plugin}/{config,lang}/` |
| **Theme** | ✅ `custom/themes/{Theme}/resources/views/` | ❌ deferred (see backlog) | partial |

Plugin / theme **logic** (PHP class) override is on the roadmap — see
`.backlog/custom-overrides-plugin-theme.md` for the design and rollout
plan. Until it lands, the path layout and PSR-4 namespaces listed below
are **already reserved** so that code you write now against them will
keep autoloading and will switch on automatically when the loader
arrives. No rename or migration will be required.

## Reserved layout

These paths are part of the public contract — they will not be renamed.

```
custom/
├── README.md / README.ja.md     # this file (tracked)
├── app/                          # core class overrides
│   └── Http/Controllers/…        # mirror app/ subtree
├── resources/
│   └── views/                    # core Blade view overrides
├── lang/{locale}/                # core translation overrides
├── config/                       # core config overrides
├── database/
│   ├── factories/                # custom factories
│   ├── migrations/               # custom migrations
│   └── seeders/                  # custom seeders
├── plugins/{Plugin}/             # per-plugin overrides
│   ├── app/                      # ← reserved for v0.2 logic override
│   ├── resources/views/          # plugin view overrides (works today)
│   ├── lang/{locale}/            # plugin translation overrides
│   └── config/                   # plugin config merge
├── themes/{Theme}/               # per-theme overrides
│   ├── app/                      # ← reserved for v0.2 logic override
│   ├── resources/views/          # theme view overrides (works today)
│   ├── lang/{locale}/            # theme translation overrides
│   └── config/                   # theme config merge
└── tests/                        # custom test suites
```

## Reserved PSR-4 namespaces

`composer.local.json` registers these unconditionally for every
detected plugin / theme, so you can place files now and they will
autoload immediately:

| Namespace | Path |
|---|---|
| `Custom\App\…` | `custom/app/…` |
| `Custom\Database\Factories\…` | `custom/database/factories/…` |
| `Custom\Database\Seeders\…` | `custom/database/seeders/…` |
| `Custom\Tests\…` | `custom/tests/…` |
| `Custom\Plugins\{Plugin}\App\…` | `custom/plugins/{Plugin}/app/…` |
| `Custom\Themes\{Theme}\App\…` | `custom/themes/{Theme}/app/…` |

## Example: overriding a core controller (works today)

```php
// custom/app/Http/Controllers/CspReportController.php
namespace Custom\App\Http\Controllers;

class CspReportController extends \App\Http\Controllers\CspReportController
{
    public function report(\Illuminate\Http\Request $request)
    {
        // your custom logic
        return parent::report($request);
    }
}
```

On application boot, `App\Traits\CustomFilesLoaderTrait` scans
`custom/app/Http/Controllers/`, sees this class, and binds the core
class to the custom subclass through the service container. Any
controller dispatch or `app()->make(...)` lookup will receive your
override.

## Plugin / theme overrides

Plugin and theme **view** overrides work today: put a Blade file under
the matching path and the namespaced view loader picks it up first.

Plugin and theme **logic** overrides (PHP classes) are not wired yet,
but the namespace and path are reserved. Code written now under
`Custom\Plugins\{Plugin}\App\…` will autoload; it just will not yet be
container-bound to the original plugin class. See the backlog file
for status.

## Notes

- This directory's contents are gitignored except for `README.md` and
  `README.ja.md`. Customizations are site-local by design.
- The signer never touches anything here. Modifications inside
  `custom/` cannot invalidate a plugin or theme signature.
- For test fixtures or throw-away PoC code, prefer
  `tests/Sandbox/` over polluting `custom/`.
