# Backup Commands

Backups can be created from the command line as well as from the admin panel. This page covers the `dls:backup:create` command and the machine-readable output a script can rely on.

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
