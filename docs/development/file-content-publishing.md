# Publishing File-Stored Content

## Overview

Content can be stored as a file instead of in the database. Without a gate, editing that
file on the server would change the public site at once, with no audit entry and no
revision. Core provides one shared layer so that every owner of file-stored content — the
front page in core, and any extension that offers file storage — publishes it the same way.

The database always holds the **published copy**. The files are the **working copy**.

> **Showing file changes at once works only in development (`APP_ENV=local`).**
> On staging and production, a changed file never goes live by itself, whatever the
> setting says — not when it is edited on the server, and not when it arrives by a deploy.
> It goes live only after someone publishes it: read its diff on the edit screen and press
> **Publish**. Until then the site keeps serving the published copy, and the admin's
> banner counts the change as waiting to be published.
>
> **In development too, a change waits to be published.** The site shows the files at once,
> but the published copy in the database moves only when someone presses **Publish**, and
> the banner counts the change until then — so a change nobody published is visible before
> a deploy (#808).

## Publish modes

Each owner sets its own mode in its own settings screen (the core front page: *Front Page →
Settings*). The default is `admin`.

| Mode | What the site shows | A change made to the file |
|---|---|---|
| `admin` (default) | The published copy in the database | Recorded once in the audit log, listed on the edit screen with a diff, and goes live when someone with edit permission presses **Publish** |
| `file_save` | The files — **only in a local environment** (`APP_ENV=local`) | Shown at once, every part included, but not taken in: recorded once in the audit log, listed on the edit screen with a diff, counted in the banner, and taken in with a `manual` revision when someone presses **Publish**, exactly as under `admin`. Anywhere else the mode does not take effect, and the site shows the published copy |

`App\Enums\ContentPublishMode` holds the two values.

### The file save mode works only in a local environment

Showing file changes without anyone publishing them is a development convenience. On
staging and production it would only mean that whoever can write the files can change the
site — `storage/` is writable by the application by design, so it can be writable where the
code directories are not. So (#786):

- `FileContentPublisher::fileSaveModeAvailable()` is true only when `APP_ENV` is `local`.
  The check is "is `local`", never "is not `production`", so staging and a mistyped value
  stay closed.
- `FileContentPublisher::effectivePublishMode($model)` is the owner's setting, except that
  `file_save` falls back to `admin` where it is not available. Everything in the layer —
  what `resolve()` returns, `FileContentRegistry::usesFileSaveMode()`, the audit entry —
  follows the mode in effect. The stored setting is kept as chosen, so a copy of the site in a
  local environment picks it up again.
- In a local environment `FileContentPublisher::resolve()` returns the files, every part
  included, script-capable parts too: the files there are the developer's own. Anywhere
  else it returns the published copy.
- **No mode takes a change in by itself** (#808). Before it, the file save mode wrote the
  files into the published copy at once with an `auto` revision, so nothing was pending
  and the banner went quiet exactly while files were being edited. Now the change counts
  towards the admin's banner of changes waiting to be published in every environment —
  a reminder before a deploy locally, and on the receiving site the sign that a deploy
  carried files nobody has published there yet. `takeableParts()` always returns `[]`
  and is deprecated.
- A settings screen that offers the mode says which applies on this site
  (`common.content_publish_mode.in_effect_local` / `not_in_effect`).

### Parts that can carry a script

Before #786, the file save mode took effect everywhere and took in only the parts that
cannot carry a script (#744). Which parts those are is still the owner's to say, and
`FileContentPublisher::scriptCapableParts()` still answers it, but it no longer changes what
goes live:

- **Without doing anything**, the answer comes from the part's name:
  `FileContentPublisher::DEFAULT_SCRIPT_CAPABLE_PARTS` covers the conventional `custom_css`
  and `custom_js`.
- **Implement `App\Contracts\Content\DeclaresScriptCapableParts`** when a part's name does
  not describe it — most often a body, where the editor type decides. It is optional, and
  an existing implementation keeps working.

## What is not covered

- **Blade.** Blade content cannot be created, edited or restored, and stored Blade bodies
  are not executed on render, so the layer does not handle it.
- **Translations.** A translation overlay keeps priority over the file. The comparison and
  **Publish** apply to the source-locale content.

## Adopting it in an extension

1. Add the hash columns with a new migration:

   ```php
   use App\Support\Content\FileContentColumns;

   Schema::table('plg_example_pages', function (Blueprint $table) {
       FileContentColumns::add($table);
   });
   ```

2. Implement `App\Contracts\Content\PublishesFileContent` on the model: whether the record
   uses file storage, the mode from your own setting, the parts and the column holding each
   part's published copy, each part's file path and disk, the audit action prefix, and a
   query over the file-stored records. Implement `App\Contracts\Revisionable` as well to
   have a publish recorded as a revision.

3. Register the model so that the scheduled scan and the dashboard see it:

   ```php
   app(\App\Services\Content\FileContentRegistry::class)->register(ExamplePage::class);
   ```

4. Render through `FileContentPublisher::resolve($model, $part)` instead of reading the file.
   It checks the files first, so a change is recorded on the first read that sees it.

5. After an admin save that writes the database and the files together, call
   `FileContentPublisher::markSaved($model)`. After a revision restore, call
   `FileContentPublisher::writeBack($model)`: a restore changes only the database.

6. On the edit screen, show the pending changes and a Publish button:

   ```blade
   <x-content.file-changes
       :changes="$fileChanges"
       :publishUrl="route('example.pages.publish-file', $page)"
       :canPublish="$canEdit"
   />
   ```

   `$fileChanges` is `FileContentPublisher::pendingChanges()` with each part passed through
   `RevisionDiffPresenter::buildSideBySide($change['published'], $change['file'])`. The
   publish route runs `App\Actions\Content\PublishFileContentAction` with the permission your
   edit screen requires.

7. Implement `App\Contracts\Content\ProvidesFileContentEditLink` so that the dashboard can
   list the record by name and link it to that edit screen. It is optional and separate from
   `PublishesFileContent`; without it the record is listed by its model and key, without a
   link. Return the URL regardless of who is asking: the dashboard links it only for members
   who pass the route's `check.menu.access` middleware.

8. Where your extension sets its publish mode, say when the scheduler is not running:

   ```blade
   <x-content.scheduler-notice :running="$schedulerRunning" />
   ```

   with `$schedulerRunning = app(FileContentPublisher::class)->schedulerRunning()` from the
   controller. It renders nothing while the scheduler runs.

## Detection

- **On render.** `resolve()` compares the files with the published copy whenever the content
  is read. The files are small and were already read on every request.
- **Daily.** `dls:integrity:scan --scheduled`, which runs daily when cron is enabled, checks
  every registered record, so a change to content nobody has viewed is found as well. There
  is no separate setting: the scan runs or not with cron.

Without cron, a change is found only when a page or an edit screen that shows the record is
read. The dashboard item and `<x-content.scheduler-notice>` say so when the scheduler's
heartbeat is missing.

## Being told

- **Dashboard.** While changes wait to be published, the site health item lists the records
  (the first five, then a count), each linking to its edit screen through
  `ProvidesFileContentEditLink`. The item's title leads to the `file_changed` entries in the
  audit log.
- **Banner and mail.** When the first change is recorded, an admin-wide banner is raised and
  one notification mail is sent, through the same monitor as the scheduled security checks.
  The banner stays, with the current count, until nothing is pending; no further mail is
  sent while it stays. Members who can open the front page editor see it. Everything that
  moves the hashes — a recorded change, a publish, an admin save, a restore, the daily scan —
  updates it, so it follows the records rather than a schedule.

## Publishing from the command line

`dls:content:publish-files` is the command-line counterpart of the **Publish** button, for a
site whose content files arrive by deployment (#805). It looks at every model registered
with `FileContentRegistry`, so an extension's records are covered without code of its own.

```bash
php artisan dls:content:publish-files                    # list what is waiting; publish nothing
php artisan dls:content:publish-files --publish          # publish everything that is waiting
php artisan dls:content:publish-files --fail-if-pending  # exit non-zero if anything is waiting
php artisan dls:content:publish-files --model=FrontPage --id=1 --publish
```

- It first checks each record as the scheduled scan does, so a change nobody has seen is
  recorded (`file_changed`, `detected_by: scan`) before it is listed or published.
- `--publish` runs `PublishFileContentAction` for each record: the same `manual` revision and
  `published_from_file` audit entry as the button, with `console` as the actor.
- `--model` takes a registered class name, with or without its namespace; `--id` needs it.
- `--fail-if-pending` makes a deploy stop on content nobody published. With `--publish` it
  fails only when a publish failed.

> **On production, do not use `--publish`.** It publishes everything that is waiting
> without showing it — a file changed on the server by someone else included. Use it only
> where every file is known to come from your own repository (a development or staging
> copy). On production, list what is waiting, check that each record is one you deployed,
> read its diff on the edit screen and publish it there. A record you did not deploy means
> a file was changed on the server: do not publish it; read its `file_changed` audit entry.

## Audit actions

| Action | Category | Written when |
|---|---|---|
| `{prefix}.file_changed` | content (warning) | A change made outside the admin is first seen |
| `{prefix}.published_from_file` | content | The changes are published from the edit screen |

### What `file_changed` carries

| Key | Value |
|---|---|
| `source` | Always `outside_admin`. The entry's actor is the system because the application cannot know who wrote a file outside it — not because Dixlase made the change. The audit log view says so next to the actor |
| `detected_by` | `render` (a page or an edit screen was read) or `scan` (the scheduled integrity scan). With cron off, only `render` ever happens |
| `published_hash` | The hash of the published copy **before** this change |
| `file_hash` | The hash of the files **as found** |
| `publish_mode` | The mode in effect: `admin`, or `file_save` in a local environment |
| `selected_publish_mode` | The mode the owner chose, which outside a local environment may be `file_save` while `admin` is in effect |
| `parts` | The parts whose files differ from the published copy |
| `held` | The parts waiting to be published: every changed part since #808. Before it, empty when the file save mode took the change in |
| `published` | Whether the change went live without being published: always false since #808 |

`published_from_file` carries `parts` and `published_hash` — the hash the record now publishes, which is the `file_hash` of the `file_changed` entry the approval answered. The approver is the entry's actor, and the publish is also recorded as a `manual` revision.

Changing the mode is audited as a settings change (`settings_updated`, with the owner's
settings page as the target).
