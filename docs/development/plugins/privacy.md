# Privacy Data Provider Contract

## Overview

`App\Contracts\PluginIntegration\PrivacyDataProviderInterface` is the contract a plugin (or core subsystem) implements to declare which personal data it stores about a given user, and how that data should be exported or erased on request.

It is the data-portability counterpart to the existing `PrivacyPolicyProviderInterface`, which only deals with displaying a plain-text privacy policy URL.

Implementations are the engine behind GDPR / personal-information-protection workflows such as "subject access request" exports and "right to be forgotten" deletions.

| Concept | Class |
|---|---|
| Contract | `App\Contracts\PluginIntegration\PrivacyDataProviderInterface` |
| Export DTO | `App\DTO\PluginPrivacy\UserDataExportDTO` |
| Deletion DTO | `App\DTO\PluginPrivacy\UserDataDeletionDTO` |
| Deletion modes | `App\Enums\PluginPrivacy\DeletionMode` |
| Aggregator (export) | `App\Services\Privacy\UserPrivacyExporter` |
| Aggregator (delete) | `App\Services\Privacy\UserPrivacyEraser` |
| Reference implementation | `App\Services\Privacy\Providers\CoreMemberPrivacyProvider` |

---

## Lifecycle

```
Operator → Admin UI → Aggregator (Exporter / Eraser)
                          │
                          ├── Provider A (your plugin)
                          ├── Provider B (DixlaseUsers)
                          └── Provider C (CoreMember)
```

The aggregator dispatches each registered provider in turn:
- **Exporter** writes one directory per provider into a single ZIP archive plus a top-level `manifest.json`.
- **Eraser** collects one `UserDataDeletionDTO` per provider and continues on per-provider failure.

The aggregator never aborts on a single provider's failure; it records the failure in the manifest (export) or in `errors[]` (delete) so the operator can act on partial results.

---

## Implementing the contract

### 1. Add the provider class

Place a class implementing `PrivacyDataProviderInterface` somewhere under your plugin namespace. Conventional location:

```
plugins/{YourPlugin}/app/Privacy/YourPluginPrivacyProvider.php
```

Required methods (inherited from `PluginCapabilityInterface`):
- `getPluginSlug(): string` — return the plugin slug declared in `plugin.json`.
- `isCapabilityAvailable(): bool` — return `false` to opt out at runtime (e.g. when a config flag is off).

Required methods (this contract):
- `privacyProviderKey(): string` — stable identifier used as the root directory inside the export ZIP. Typically the plugin slug.
- `privacyDataDescription(): array` — `['en' => '...', 'ja' => '...']`. Operator-facing description shown in the admin UI.
- `exportUserData(int $userId, ?int $siteId = null): UserDataExportDTO`
- `deleteUserData(int $userId, DeletionMode $mode, ?int $siteId = null): UserDataDeletionDTO`

### 2. Register the provider in your ServiceProvider

```php
public function register(): void
{
    $this->app->tag(
        [\Plugins\YourPlugin\App\Privacy\YourPluginPrivacyProvider::class],
        \App\Services\Plugin\PluginServiceResolver::CAPABILITY_TAG,
    );
}
```

### 3. Declare the permissions in `plugin.json`

```json
"permissions": {
    "privacy": {
        "export": true,
        "delete": true
    }
}
```

The aggregator gates plugin providers through `PluginPermissionService::check($slug, 'privacy.export'|'privacy.delete')`. Core providers (slug `core` or prefixed `core-*`) bypass this gate because they have no `plugin.json`.

---

## Multisite scoping

`$siteId` controls the scope of the export or deletion:

| `$siteId` value | Meaning |
|---|---|
| `null` | Network-wide. Include every site's rows plus globally-scoped tables. |
| Concrete `int` | Only data tied to that site. Providers whose tables are not site-scoped MUST return an empty DTO with an explanatory entry in `UserDataExportDTO::$warnings`. |

Three patterns to handle:

### Pattern A: fully site-scoped provider
Every table you query has a `site_id` column. Filter by `$siteId` when supplied. When `$siteId` is `null`, include all sites.

### Pattern B: fully non-site-scoped provider (DixlaseUsers pattern)
Your tables have no `site_id`. When `$siteId !== null`, return an empty DTO with a warning:

```php
if ($siteId !== null) {
    return new UserDataExportDTO(
        providerKey: 'your-plugin',
        warnings: ['Your-plugin data is not site-scoped; re-run with siteId=null to include this provider.'],
    );
}
```

### Pattern C: mixed (CoreMember pattern)
Some tables are site-scoped (e.g. `audit_logs`), some are not (e.g. `members`). When `$siteId !== null`, omit the global tables and filter the site-scoped ones by `site_id`. Add a warning so the operator understands what was excluded.

---

## Deletion mode semantics

The contract defines three modes via `DeletionMode`:

| Mode | Strategy |
|---|---|
| `HardDelete` | Physically remove rows. Audit-trail rows (e.g. `audit_logs`, `security_events`) should be **anonymized in place** rather than deleted, so the append-only chain stays intact. |
| `SoftDelete` | Mark the parent row's `deleted_at`. Keep child rows so a future undelete can re-associate them. Tables without soft-delete support can fall back to `HardDelete` and report the limitation in `errors[]`. |
| `Anonymize` | Keep every row. Replace identifying fields (email, IP, user agent, name, phone) with HMAC-SHA256 of `app.key`-derived salt. The result is irreversible: even with the same `app.key`, you cannot recover the original input. |

### Anonymize hash recipe

The canonical derivation is:

```php
$salt = hash('sha256', config('app.key').'|dixlase-privacy-anonymize');
$hash = substr(hash_hmac('sha256', $value, $salt), 0, 32);
```

- HMAC-SHA256 with the derived salt.
- Truncated to **32 hex characters** (128 bits of collision resistance) so it fits in narrow PII columns such as `ip_address VARCHAR(45)`.
- For `null` or empty inputs, return `null` to avoid storing a "hash of empty string" placeholder.

### `app.key` rotation

If the operator rotates `app.key`, hashes generated before the rotation will not match hashes generated after. This is acceptable: anonymization is a one-way fence, and lack of correlation across rotations is the desired behavior.

---

## Skeleton example

```php
<?php

namespace Plugins\YourPlugin\App\Privacy;

use App\Contracts\PluginIntegration\PrivacyDataProviderInterface;
use App\DTO\PluginPrivacy\UserDataDeletionDTO;
use App\DTO\PluginPrivacy\UserDataExportDTO;
use App\Enums\PluginPrivacy\DeletionMode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class YourPluginPrivacyProvider implements PrivacyDataProviderInterface
{
    private const PLUGIN_SLUG = 'your-plugin';

    public function getPluginSlug(): string
    {
        return self::PLUGIN_SLUG;
    }

    public function isCapabilityAvailable(): bool
    {
        return true;
    }

    public function privacyProviderKey(): string
    {
        return self::PLUGIN_SLUG;
    }

    public function privacyDataDescription(): array
    {
        return [
            'en' => 'Stores comments authored by the user.',
            'ja' => 'ユーザーが投稿したコメントを保管します。',
        ];
    }

    public function exportUserData(int $userId, ?int $siteId = null): UserDataExportDTO
    {
        $query = DB::table('plg_your_plugin_comments')->where('author_id', $userId);

        if ($siteId !== null) {
            $query->where('site_id', $siteId);
        }

        return new UserDataExportDTO(
            providerKey: self::PLUGIN_SLUG,
            data: ['comments' => $query->get()->map(fn ($r) => (array) $r)->all()],
        );
    }

    public function deleteUserData(int $userId, DeletionMode $mode, ?int $siteId = null): UserDataDeletionDTO
    {
        $query = DB::table('plg_your_plugin_comments')->where('author_id', $userId);

        if ($siteId !== null) {
            $query->where('site_id', $siteId);
        }

        $deleted = 0;
        $anonymized = 0;

        if ($mode === DeletionMode::HardDelete) {
            $deleted = $query->delete();
        } elseif ($mode === DeletionMode::SoftDelete) {
            $deleted = $query->update(['deleted_at' => Carbon::now()]);
        } elseif ($mode === DeletionMode::Anonymize) {
            $salt = hash('sha256', (string) config('app.key').'|dixlase-privacy-anonymize');
            foreach ($query->get(['id', 'author_name']) as $row) {
                DB::table('plg_your_plugin_comments')->where('id', $row->id)->update([
                    'author_name' => substr(hash_hmac('sha256', (string) $row->author_name, $salt), 0, 32),
                ]);
                $anonymized++;
            }
        }

        return new UserDataDeletionDTO(
            providerKey: self::PLUGIN_SLUG,
            mode: $mode,
            deletedRecords: $deleted,
            anonymizedRecords: $anonymized,
        );
    }
}
```

---

## Operator-facing UI

Core ships a minimal admin page at `/admin/privacy/users` (super-admin only) where operators can:
- Look up a member by id, email, or account name.
- Export the subject's data as a ZIP, with a per-site or network-wide scope.
- Run a deletion across every registered provider with a chosen mode.

The page is intentionally a stub. Plugins can layer their own UI on top of the same aggregator services if a richer subject-access workflow is needed.

---

## Reference

- Contract: `app/Contracts/PluginIntegration/PrivacyDataProviderInterface.php`
- Reference impl: `app/Services/Privacy/Providers/CoreMemberPrivacyProvider.php`
- Plugin example: `plugins/DixlaseUsers/app/Privacy/DixlaseUsersPrivacyProvider.php`
- Aggregators: `app/Services/Privacy/UserPrivacyExporter.php`, `UserPrivacyEraser.php`
- Tests: `tests/Feature/PluginPrivacy/`, `plugins/DixlaseUsers/tests/Feature/Privacy/`
