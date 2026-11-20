# Backup Commands

Backups can be created from the command line as well as from the admin panel. This page covers the scheduled backup, the `dls:backup:create` command, signed backups and the machine-readable output a script can rely on.

## Creating a Backup

```bash
php artisan dls:backup:create
php artisan dls:backup:create --targets=database
php artisan dls:backup:create --targets=database,media,private --retention=30
```

| Option | Meaning |
| --- | --- |
| `--targets=` | Comma-separated targets: `database`, `media`, `private`, `custom`, `logs`. Defaults to all except `logs`. |
| `--retention=` | Retention period in days. Leave empty to keep the backup indefinitely. |
| `--format=` | `text` (the default) or `json`. |

`dls:backup:list` shows the backups that have been recorded.

## Scheduled Backups

A site takes backups on its own once the scheduler is running (cron calling `php artisan schedule:run` every minute). Nothing has to be set up on the site itself; a fresh install starts with:

| Setting | Default |
| --- | --- |
| Scheduled backup | On |
| Frequency | Daily (weekly runs on Sunday) |
| Time | 02:30, server time |
| Targets | Database only |
| Kept for | The default retention period of the backup settings; 14 days when that is blank |

The database alone is the default so that a nightly copy of a large media library does not fill the disk. Add other targets on the settings screen when the disk allows it: a daily backup is kept once per day of retention.

Change or turn it off at **Global Settings > System > Backup > Backup Settings**. The scheduler re-reads the settings on every run, so cron never needs changing.

- **Cleanup.** `dls:backup:cleanup` runs every day at 02:15, whether or not the scheduled backup is on, and deletes the backups whose retention has run out. Retention is fixed when a backup is created, so changing the default retention period affects only the backups created afterwards. A manual backup created without a retention period is never deleted by the cleanup.
- **When a scheduled backup fails**, the administrator notification mail is sent once (when it is configured), and a warning banner stays on the admin pages of the members who can edit the backup settings until a scheduled backup succeeds again. The output of both commands goes to `storage/logs/backup.log`.
- **Site Health** on the dashboard warns when the last scheduled backup failed, when no backup has been taken for one interval plus a day (2 days for daily, 8 days for weekly), and when the scheduler is not running. It recommends turning the schedule on when it is off.
- `dls:backup:create --scheduled` is the command the scheduler runs. It takes the targets and retention from these settings unless `--targets` or `--retention` are given, and marks the backup as scheduled (`metadata.trigger`).

## Signed Backups

When the site has an integrity key (`INTEGRITY_KEY` in `.env`, see [Audit Log Integrity](audit-log-integrity.md#integrity-key)), every backup is signed as it is created, and a restore checks the signature before it changes anything.

**What it protects.** A backup's record holds the archive's SHA-256, and a restore compares the two. That catches a damaged archive, but the record sits in the database the restore is about to overwrite: someone who can write both the backup storage and the database can put another archive in place and make its record agree. The signature is an HMAC-SHA256, keyed with the integrity key, over the archive's SHA-256 and the record's file name and targets. The key is in `.env`, which no backup target collects, so a replaced archive cannot be given a signature that verifies. Like the rest of the integrity key's uses, it does not protect against code running inside the application, which reads the same `.env`.

**Keep the key out of the backups.** A backup that carries the key that signed it proves nothing. Store a copy of `.env` (or of the key line) somewhere other than the backup storage, so that a server rebuilt from backups can still verify them.

**What a restore does with it:**

| Signature | Restore |
| --- | --- |
| Valid | Goes ahead |
| No key on this site | Goes ahead after the hash check, as before signing existed |
| Missing (taken before the site had a key) | Refused unless explicitly accepted |
| Does not match | Refused unless explicitly accepted |
| Signed by a key version that is no longer configured | Refused unless explicitly accepted |

Rotating the key keeps old backups verifying for as long as the old key stays in `INTEGRITY_PREVIOUS_KEYS`; keep it there while backups it signed are kept.

**Accepting an unverified backup.** Do this only when you know where the archive came from, for example a backup taken before the key was created. Every refusal and every acceptance is written to the audit log (`backup_signature_rejected`, `backup_signature_overridden`).

- On the command line: `php artisan dls:backup:restore <id> --allow-unverified-signature`. The same option applies to `--rollback-of=<id>`.
- In the admin panel: the backup list and the restore history say when a signature does not verify, and their confirmation asks you to restore or roll back anyway. The backup detail screen shows the signature state.

## Machine-Readable Output (`--format=json`)

A tool that runs the command non-interactively, for example over SSH right before it changes a site, can ask for the result as JSON instead of reading the text:

```bash
php artisan dls:backup:create --targets=database --format=json
```

In this mode the command writes **exactly one JSON object, on one line, to stdout**, and nothing else. Progress messages go to stderr. The object is written for failures too, so a script can always parse stdout.

```json
{"success":true,"backup_record_id":42,"file_path":"/var/www/html/storage/app/private/backups/dixlase-backup-20261117-093000-database.zip","file_name":"dixlase-backup-20261117-093000-database.zip","file_size":2048,"duration":1.23,"targets":["database"],"hash":"9f86d08...","error":null}
```

### Fields

The field names below are a contract: they are not renamed or removed without a breaking-change note in the release. New fields may be added, so a script should ignore fields it does not know.

| Field | Type | Meaning |
| --- | --- | --- |
| `success` | boolean | `true` when the backup was created. |
| `backup_record_id` | integer or `null` | The id of the backup record. Use it to find this backup again in the admin panel or in `dls:backup:list`. `null` on failure. |
| `file_path` | string or `null` | Absolute path of the backup archive. `null` on failure. |
| `file_name` | string or `null` | File name of the archive. `null` on failure. |
| `file_size` | integer or `null` | Size of the archive in bytes. `null` on failure. |
| `duration` | number or `null` | Time taken, in seconds, rounded to two decimals. `null` on failure. |
| `targets` | array of strings | The targets included. On failure, the targets that were requested. |
| `hash` | string or `null` | Hash of the archive, as recorded for verification. `null` on failure. |
| `error` | string or `null` | Why the backup failed. `null` on success. |

### Exit Codes

The exit codes are the same in both formats:

| Code | Meaning |
| --- | --- |
| `0` | The backup was created. |
| `1` | The backup failed. The JSON object carries the reason in `error`. |
| `2` | The options were invalid: an unknown target, or an unknown `--format`. An unknown target still prints the JSON object; an unknown format prints only an error, because no format was agreed on. |

### Example

```bash
out=$(php artisan dls:backup:create --targets=database --format=json) || {
  echo "backup failed: $(printf '%s' "$out" | jq -r .error)" >&2
  exit 1
}
backup_id=$(printf '%s' "$out" | jq -r .backup_record_id)
```
