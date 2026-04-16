# Revision API

## Overview

Dixlase ships a shared revision system that any content model (core or plugin) can
opt into. It captures a snapshot on every save, skips unchanged saves, prunes older
entries beyond the configured retention, supports per-revision protection, and
offers side-by-side diff rendering for the admin UI.

## Version

- **Specification Version**: 1.0
- **Status**: Stable

## 1. Architecture

```
┌─────────────────┐  use HasRevisions   ┌─────────────────┐
│  Content Model  │ ─────────────────▶ │   Revision      │
│ implements      │                     │   Model         │
│  Revisionable   │                     │  (own table)    │
└────────┬────────┘                     └─────────────────┘
         │
         │ record/restore
         ▼
┌─────────────────┐
│ RevisionService │  (generic, @api)
└─────────────────┘
```

Design principles:

- **One table per content type** — plugins own their revisions table (e.g.
  `dls_plg_dixlase_pages_post_revisions`). This aligns with the "plugins access
  only their own tables" rule.
- **Contract-driven** — `App\Contracts\Revisionable` declares the three pieces of
  information the service needs; no inheritance required.
- **Thin per-model facades are optional** — core's `FrontPageRevisionService` is
  just a typed wrapper around `RevisionService` for existing callers.

## 2. Public API Surface

All items below carry `@api` and are therefore safe for plugins and themes to
depend on under the AGPL plugin/theme exception.

### 2.1 Contract

| FQCN | Purpose |
|------|---------|
| `App\Contracts\Revisionable` | Implemented by content models that want history |

Methods:

| Method | Return | Description |
|--------|--------|-------------|
| `revisionModel()` | `class-string<Model>` | FQCN of the revision Eloquent model |
| `revisionForeignKey()` | `string` | FK column on the revision table pointing to the parent |
| `revisionableFields()` | `list<string>` | Attributes included in every snapshot |

### 2.2 Trait

| FQCN | Purpose |
|------|---------|
| `App\Traits\HasRevisions` | Provides the `revisions()` HasMany relation |

### 2.3 Service

| FQCN | Purpose |
|------|---------|
| `App\Services\RevisionService` | Generic record/restore/prune/compare engine |

Key constants: `TYPE_AUTO`, `TYPE_MANUAL`, `TYPE_RESTORE_BACKUP`,
`SETTING_KEY_RETENTION`, `DEFAULT_RETENTION`, `MAX_RETENTION`.

Key methods:

| Method | Signature | Notes |
|--------|-----------|-------|
| `record` | `record(Revisionable, string $type, ?int $userId, ?string $note): ?Model` | Skips when snapshot matches latest |
| `restore` | `restore(Model $revision, ?int $userId): Revisionable` | Creates a `restore_backup` only if current differs from latest |
| `buildSnapshot` | `buildSnapshot(Revisionable): array<string,mixed>` | Normalised attribute snapshot |
| `countProtected` | `countProtected(Revisionable): int` | Number of `is_protected = true` rows |
| `getRetentionCount` | `getRetentionCount(): int` | Reads `content.revision.retention_count`, clamps to `[0, MAX_RETENTION]` |

Override `getRetentionCount()` (and other methods) in a subclass when a plugin
needs different rules — e.g. Legal plugins may want unlimited retention.

### 2.4 Diff Presenter

| FQCN | Purpose |
|------|---------|
| `App\Presenters\Admin\RevisionDiffPresenter` | Converts two strings into side-by-side diff rows |

### 2.5 Blade Components

| Component | Purpose |
|-----------|---------|
| `<x-revision.list>` | Paginated revision table with protect/restore actions and retention summary |
| `<x-revision.diff>` | Diff viewer with metadata table, note editor and protect/restore buttons |

## 3. Adding Revisions to a Content Model

### 3.1 Required Schema

Each revision table must provide these columns:

```php
$table->id();
$table->foreignId('{parent_fk}')->constrained('{parent_table}')->cascadeOnDelete();
$table->json('snapshot');
$table->string('type', 20)->default('auto');
$table->string('note')->nullable();
$table->boolean('is_protected')->default(false);
$table->foreignId('created_by')->nullable()->constrained('members')->nullOnDelete();
$table->timestamp('created_at')->nullable();
$table->index(['{parent_fk}', 'created_at']);
```

Table naming:

- Core: `{entity}_revisions` (e.g. `front_page_revisions`)
- Plugin: `dls_plg_{slug}_{entity}_revisions`
- Theme: `dls_thm_{slug}_{entity}_revisions`

### 3.2 Revision Model

```php
class PageRevision extends Model
{
    protected $table = 'dls_plg_dixlase_pages_page_revisions';

    public const TYPE_AUTO = RevisionService::TYPE_AUTO;
    public const TYPE_MANUAL = RevisionService::TYPE_MANUAL;
    public const TYPE_RESTORE_BACKUP = RevisionService::TYPE_RESTORE_BACKUP;

    protected $fillable = ['page_id', 'snapshot', 'type', 'note', 'is_protected', 'created_by'];
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'is_protected' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo { return $this->belongsTo(Page::class); }
    public function creator(): BelongsTo { return $this->belongsTo(Member::class, 'created_by'); }

    protected static function booted(): void
    {
        static::creating(function (self $revision): void {
            if (empty($revision->created_at)) {
                $revision->created_at = now();
            }
        });
    }
}
```

### 3.3 Parent Model

```php
class Page extends Model implements Revisionable
{
    use HasRevisions;

    public function revisionModel(): string { return PageRevision::class; }
    public function revisionForeignKey(): string { return 'page_id'; }

    public function revisionableFields(): array
    {
        return ['title', 'slug', 'content', 'status'];
    }
}
```

### 3.4 Parent Resolution

`RevisionService::restore()` locates the parent by looking for one of these
relation methods on the revision model:

`content`, `target`, `revisionable`, `frontPage`, `page`, `post`, `legal`,
`article`, `entry`.

Name the `belongsTo` relation on the revision model with one of these, or
override `RevisionService::loadTarget()` in a subclass.

## 4. Hooking Into Save Flows

Inject `RevisionService` into your save Action and record after the update:

```php
$this->revisionService->record(
    $page->fresh(),
    type: RevisionService::TYPE_MANUAL,
    userId: $actor->getActorId(),
);
```

Every explicit user save should use `TYPE_MANUAL`. `TYPE_AUTO` is reserved for
future background autosave. `TYPE_RESTORE_BACKUP` is produced only by the
service itself.

## 5. Audit Logging

Operations performed from the admin UI **must** be routed through Actions so
they produce audit entries. Mirror the core FrontPage set for your plugin:

- `RestoreXxxRevisionAction` — audit action `xxx.revision.restored`
- `ToggleXxxRevisionProtectionAction` — audit action `xxx.revision.protection_toggled`
- `UpdateXxxRevisionNoteAction` — audit action `xxx.revision.note_updated`

Audit entries land in the existing audit log table with category `content`, so
they appear in the audit log UI and filter dropdown automatically.

## 6. Admin UI Components

Both components accept route names rather than URLs so they work with deeply
nested routes:

```blade
<x-revision.list
    :revisions="$revisions"
    :typeLabels="$typeLabels"
    :retention="$retention"
    :protectedCount="$protectedCount"
    :backRoute="route('...edit')"
    showRouteName="...revisions.show"
    restoreRouteName="...revisions.restore"
    protectRouteName="...revisions.protect"
    :parentParams="[$page->id]"
    translationPrefix="dixlase-pages::admin/pages/revisions"
/>
```

```blade
<x-revision.diff
    :revision="$revision"
    :typeLabels="$typeLabels"
    :diffs="$diffs"
    :metaDiffs="$metaDiffs"
    :hasChanges="$hasChanges"
    :backRoute="route('...revisions.index')"
    restoreRouteName="...revisions.restore"
    noteRouteName="...revisions.note"
    protectRouteName="...revisions.protect"
    :parentParams="[$page->id]"
    translationPrefix="dixlase-pages::admin/pages/revisions"
/>
```

`$parentParams` is prepended to the revision id for every generated URL.

## 7. Configuration

Setting key `content.revision.retention_count` is shared across all content
types. Valid range: `0` (feature disabled) to `500` (max). Default: `50`.

UI: **Admin > Settings > Base > Content** (advanced mode only).

Retention applies to non-protected revisions only. Protected revisions are
never pruned automatically, which may cause the total to exceed the retention
value by design.

## 8. Testing

See `tests/Unit/FrontPageRevisionServiceTest.php`,
`tests/Unit/RevisionProtectionTest.php`, and
`tests/Feature/Admin/Front/RevisionActionAuditTest.php` for reference coverage.

## 9. Non-Scope

These were intentionally left out of the current release:

- Soft delete for auto-pruned revisions (hard delete is intentional)
- Manual "create revision" button (every explicit save is already a manual
  revision)
- Branching (multiple concurrent editing timelines)
- Revision export/import
- Time-based retention (count-based only)
