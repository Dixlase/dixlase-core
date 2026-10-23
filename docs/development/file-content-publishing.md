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
| `file_save` | The file | Taken in as the published copy at once, recorded as an `auto` revision, and recorded in the audit log |

`App\Enums\ContentPublishMode` holds the two values.

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

## Detection

- **On render.** `resolve()` compares the files with the published copy whenever the content
  is read. The files are small and were already read on every request.
- **Daily.** `dls:integrity:scan --scheduled`, which runs daily when cron is enabled, checks
  every registered record, so a change to content nobody has viewed is found as well. The
  dashboard lists the number of records with changes waiting to be published. There is no
  separate setting: the scan runs or not with cron.

## Audit actions

| Action | Category | Written when |
|---|---|---|
| `{prefix}.file_changed` | content (warning) | A change made outside the admin is first seen. `context.published` tells whether the file save mode took it in |
| `{prefix}.published_from_file` | content | The changes are published from the edit screen |

Changing the mode is audited as a settings change (`settings_updated`, with the owner's
settings page as the target).
