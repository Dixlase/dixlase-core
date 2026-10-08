# Action Layer

> **Status (v0.1):** lifecycle and `ActionResult` metadata schema are part of
> the Plugin API contract and are frozen within `^0.1`. The lifecycle order
> (`authorize → validate → handle → audit → events`) will not be reordered
> or have new steps inserted between existing ones until v1.0.

## Overview

Actions encapsulate a single auditable business operation. They live at
`app/Actions/` (core) or `plugins/{Name}/app/Actions/` (plugins) and extend
`App\Actions\AbstractAction`.

Each invocation of `execute(Actor $actor, array $data)` runs the following
template method steps in order:

| # | Step                  | Hook                              | Default behaviour                                              |
|---|-----------------------|-----------------------------------|----------------------------------------------------------------|
| 1 | Authorize             | `authorize()`                     | Permission check based on `requiredPermission()`.              |
| 2 | Validate              | `validate()`                      | No-op. Subclasses override for business-rule validation.       |
| 3 | Handle                | `handle()` (abstract)             | Subclass implementation. Wrapped in a DB transaction by default. |
| 4 | Audit                 | `audit()`                         | Writes one `audit_logs` row on success.                        |
| 5 | Domain events         | `dispatchEvents()`                | No-op. Subclasses fire `DixlaseEvents` constants.              |

If any step throws, subsequent steps do not run. The transaction (step 3) is
opened only if `useTransaction()` returns `true` (the default).

## The `validate()` hook

`validate()` is the canonical place for business-rule validation that cannot
be expressed in a Laravel Form Request — cross-field consistency, state-machine
transitions, site-scoped uniqueness, and so on. The Form Request stays
responsible for HTTP-shape validation (types, required fields, regexes); the
action handles semantics.

```php
namespace Plugins\DixlasePages\App\Actions\Page;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use Illuminate\Validation\ValidationException;

final class PublishPageAction extends AbstractAction
{
    protected function validate(Actor $actor, array $data): void
    {
        $page = DixlasePagesPage::query()->findOrFail($data['page_id']);

        if ($page->status === PageStatus::Trashed) {
            throw ValidationException::withMessages([
                'page_id' => __('plugin/dixlase-pages::pages.cannot_publish_trashed'),
            ]);
        }
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        // ...
    }
}
```

Throw `Illuminate\Validation\ValidationException` to abort with the same
422-style error shape that Form Requests produce. Other exceptions propagate
unchanged.

## `ActionResult` metadata schema

`App\DTO\Action\ActionResult::$metadata` is an open `array<string, mixed>`,
but the keys below are **reserved**: their shape will not change within
`^0.1`. Plugins should write to them when applicable.

| Key            | Shape                                                                                | Purpose                                                                       |
|----------------|--------------------------------------------------------------------------------------|-------------------------------------------------------------------------------|
| `diff`         | `array<string, array{from: mixed, to: mixed}>`                                       | Field-level diff for UPDATE actions.                                          |
| `before`       | `array<string, mixed>`                                                               | Pre-change attribute snapshot.                                                |
| `after`        | `array<string, mixed>`                                                               | Post-change attribute snapshot.                                               |
| `changes`      | `array<string, mixed>`                                                               | Subset of `after` limited to the changed attributes.                          |
| `warnings`     | `array<int, string>`                                                                 | Non-fatal issues raised during `handle()` (e.g. "skipped 2 invalid rows").    |
| `side_effects` | `array<int, array{type: string, target: string, ...}>`                               | Secondary operations performed (cache invalidations, notifications, …).       |

Additional keys are allowed but **must be prefixed with the plugin slug** —
for example `dixlase_pages.revision_id` — to avoid future collisions when
core introduces new reserved keys.

Callers should treat all metadata keys as optional and absence as "no
information available" rather than "no change".

## Where auditing happens

> **Actions are the only audit producer.** Anything that should leave an
> `audit_logs` row goes through an action, or through a shared service an
> action calls.

The operation — not the table write — is the unit that gets audited, and an
action is where an operation lives. `AbstractAction::execute()` calls
`audit()` after `handle()` succeeds, so an action that returns a successful
`ActionResult` has already written its row; the metadata in that result
(`before` / `after` / `diff`) is what the entry carries.

### Why there is only one way

`App\Traits\AuditableTrait` offered a second, model-level mechanism: it wrote
a row from `created` / `updated` / `deleted`. **It is deprecated and will be
removed in v0.2.0.** Nothing in core, the official plugins or the themes ever
used it, so it read as a promise the codebase did not keep — a reader who
found it reasonably concluded that model writes are audited automatically.

Adopting it instead would not have been cheaper: a model touched both inside
and outside an action produces two rows for one operation, which is why the
old convention needed a `withoutAudit()` call and a responsibility table. One
answer is cheaper to follow than two.

### What to do instead

| If you want to audit … | Do … |
|---|---|
| an operation a user or an operator performs | put it in an `AbstractAction` subclass; `audit()` writes the row |
| something a job or a command does | give it an action too, or call the `Audit` facade from the service the command invokes |
| a write deep inside a service | have the action that owns the operation describe it, not the service |

If a model in your plugin still uses the deprecated trait and is also touched
inside `handle()`, wrap the save so only the action's entry survives:

```php
$page->withoutAudit(fn () => $page->update([
    'status' => PageStatus::Published->value,
]));
```

## Plugin API surface

The following Action-layer symbols are `@api` (Plugin API):

- `App\Actions\AbstractAction`
- `App\DTO\Action\ActionResult`
- `App\Contracts\Action\ActionInterface`
- `App\Contracts\Action\Actor`
- `App\Traits\AuditableTrait` — **deprecated**, removed in v0.2.0

Implementation classes (`App\Services\Audit\*`, `App\Models\AuditLog`,
`App\Facades\Audit`, etc.) are not part of the Plugin API; use the contracts
listed above.
