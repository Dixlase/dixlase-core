# Publishing File-Stored Content

## Overview

Content can be stored as a file instead of in the database. Without a gate, editing that
file on the server would change the public site at once, with no audit entry and no
revision. Core provides one shared layer so that every owner of file-stored content — the
front page in core, and any extension that offers file storage — publishes it the same way.

The database always holds the **published copy**. The files are the **working copy**.

## Publish modes

Each owner sets its own mode in its own settings screen (the core front page: *Front Page →
Settings*). The default is `admin`.

| Mode | What the site shows | A change made to the file |
|---|---|---|
| `admin` (default) | The published copy in the database | Recorded once in the audit log, listed on the edit screen with a diff, and goes live when someone with edit permission presses **Publish** |
| `file_save` | The file, **except for the parts that can carry a script** | Taken in as the published copy at once, recorded as an `auto` revision, and recorded in the audit log. A script-capable part is held and waits for **Publish**, as under `admin` |

`App\Enums\ContentPublishMode` holds the two values.

### Parts that can carry a script

Writing a script into the site is an ADMIN-and-above capability in the admin panel. The
file save mode would otherwise be a second way in that does not pass that limit —
`storage/` is writable by the application by design, so it can be writable where the code
directories are not. So under `file_save` the shared code takes in only the parts that
cannot carry a script; the rest wait to be published, and `context.held` on the audit entry
names them.

Which parts those are is the owner's to say:

- **Without doing anything**, the answer comes from the part's name:
  `FileContentPublisher::DEFAULT_SCRIPT_CAPABLE_PARTS` covers the conventional `custom_css`
  and `custom_js`.
- **Implement `App\Contracts\Content\DeclaresScriptCapableParts`** when a part's name does
  not describe it — most often a body, where the editor type decides. It is a separate,
  optional interface, so `PublishesFileContent` did not change and an existing
  implementation keeps working. Returning an empty array is a statement that no part of the
  record can carry a script, and switches the name-based default off.

The core front page declares its body script-capable under the HTML editor, where
`<script>` is part of what the author writes. A Markdown or GUI body cannot carry one, so
it still goes live at once under `file_save`.

A production site whose publish mode is `file_save` is warned on the dashboard, because the
setting otherwise says nowhere that review before publishing has been given up.

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
| `publish_mode` | `admin` or `file_save` |
| `parts` | The parts whose files differ from the published copy |
| `held` | The parts kept back for review (see above). Empty when the whole change went live |
| `published` | True only when the whole change went live |

`published_from_file` carries `parts` and `published_hash` — the hash the record now publishes, which is the `file_hash` of the `file_changed` entry the approval answered. The approver is the entry's actor, and the publish is also recorded as a `manual` revision.

Changing the mode is audited as a settings change (`settings_updated`, with the owner's
settings page as the target).
