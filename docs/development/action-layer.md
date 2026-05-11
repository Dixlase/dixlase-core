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

## AuditableTrait vs Action audit responsibility

Both layers can produce `audit_logs` rows. To avoid duplicates, follow a
single rule:

> **Actions own the audit log for the operations they wrap.**
> When a model touched inside `handle()` also uses
> `App\Traits\AuditableTrait`, suppress the trait's automatic logging.

The trait fires `audit_logs` entries on model lifecycle events
(`created` / `updated` / `deleted`) via `bootAuditableTrait()`. The action's
`audit()` method writes a richer business-level entry afterwards. Without
suppression you get two rows for one operation, with overlapping context.

### Pattern

```php
protected function handle(Actor $actor, array $data): ActionResult
{
    $page = DixlasePagesPage::query()->findOrFail($data['page_id']);

    // Suppress AuditableTrait's automatic entry; AbstractAction::audit()
    // will write the canonical business-level entry afterwards.
    $page->withoutAudit(function () use ($page, $data) {
        $page->update(['status' => PageStatus::Published->value]);
    });

    return ActionResult::success($page, 'Page published', $page->title, [
        'before' => ['status' => PageStatus::Draft->value],
        'after'  => ['status' => PageStatus::Published->value],
        'diff'   => ['status' => ['from' => 'draft', 'to' => 'published']],
    ]);
}
```

`withoutAudit()` is part of the trait's public API. It is safe to call from
plugins.

### When AuditableTrait alone is correct

If a model is mutated outside any Action (legacy controller code, queued
jobs that write directly, plugin-internal background tasks), AuditableTrait
remains the right tool — it captures changes that would otherwise go
unrecorded.

### Choosing between the two strategies for a new model

| If the model is touched … | Use … |
|---|---|
| only inside Actions | AbstractAction's `audit()`; **do not** add AuditableTrait. |
| both inside and outside Actions | AuditableTrait + `withoutAudit()` inside `handle()`. |
| only outside Actions | AuditableTrait alone. |

## Plugin API surface

The following Action-layer symbols are `@api` (Plugin API):

- `App\Actions\AbstractAction`
- `App\DTO\Action\ActionResult`
- `App\Contracts\Action\ActionInterface`
- `App\Contracts\Action\Actor`
- `App\Traits\AuditableTrait`

Implementation classes (`App\Services\Audit\*`, `App\Models\AuditLog`,
`App\Facades\Audit`, etc.) are not part of the Plugin API; use the contracts
listed above.
