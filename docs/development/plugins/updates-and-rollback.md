# Migrations and Seeders in Updates and Rollbacks

What core does with a plugin's or theme's migrations and `UpdateSeeder` when an operator updates the extension or rolls the update back, and what that asks of extension authors.

The Japanese version of this page is [`docs/ja/development/plugins/updates-and-rollback.md`](../../ja/development/plugins/updates-and-rollback.md).

## Update

`dls:plugin:update` / `dls:theme:update` (and the admin updates screen, which runs them):

1. Take a backup of the installed tree. Its sidecar records the highest migration batch at that moment (`max_batch`).
2. Replace the files with the new release and scan them. A version that scans as Blocked is put back here, before anything below runs.
3. Run the new migrations. Every migration the release adds goes into **one new batch**.
4. Run `Database\Seeders\UpdateSeeder` if the extension has one.

## Rollback

`dls:plugin:rollback` / `dls:theme:rollback` (and the rollback button on the extension's page):

1. Reverse the migrations the update added: every ledger row in the batches above the backup's `max_batch`, each through its `down()`.
2. Restore the files from the backup.

**Nothing undoes what `UpdateSeeder` wrote.** Rows it added to a table that one of the update's migrations created disappear with that table. Rows it wrote into a table that already existed stay, and the previous version of the extension then runs with them.

## What this asks of extension authors

- **Give every migration a working `down()`.** It is what a rollback runs.
- **Keep `UpdateSeeder` additive.** Insert with `insertOrIgnore()` / `updateOrCreate()`, do not delete or rewrite existing rows, and make sure what it adds is harmless to the version before it: the previous code must still work if those rows are present.
- **A change that the previous version cannot live with belongs in a migration**, so that its `down()` takes it back.
- An operator who needs the database exactly as it was before the update restores the pre-update database backup (`dls:backup:restore <id> --targets=database`); a rollback does not do that.

## History

Until core v0.1.4 a rollback passed the number of *batches* added since the backup as `--step`, but `--step` counts migration *files*. An update with two or more migrations put them all in one batch, so only the newest was reversed ([dixlase-core#455](https://github.com/Dixlase/dixlase-core/issues/455)).
