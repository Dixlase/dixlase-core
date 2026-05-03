# プライバシーデータプロバイダー Contract

## 概要

`App\Contracts\PluginIntegration\PrivacyDataProviderInterface` は、プラグイン（またはコアサブシステム）が「指定ユーザーに紐づく個人データの保有内容」と「そのデータをエクスポート・削除する方法」を申告するための Contract です。

既存の `PrivacyPolicyProviderInterface`（プライバシーポリシー URL を表示するためだけの Contract）に対する**データポータビリティ版**の対応物です。

GDPR / 個人情報保護法における「ユーザーごとの全データエクスポート」「忘れられる権利による削除・匿名化」のワークフローを支えるエンジン部分です。

| 概念 | クラス |
|---|---|
| Contract | `App\Contracts\PluginIntegration\PrivacyDataProviderInterface` |
| Export DTO | `App\DTO\PluginPrivacy\UserDataExportDTO` |
| Deletion DTO | `App\DTO\PluginPrivacy\UserDataDeletionDTO` |
| 削除モード | `App\Enums\PluginPrivacy\DeletionMode` |
| 集約器（エクスポート） | `App\Services\Privacy\UserPrivacyExporter` |
| 集約器（削除） | `App\Services\Privacy\UserPrivacyEraser` |
| 参考実装 | `App\Services\Privacy\Providers\CoreMemberPrivacyProvider` |

---

## ライフサイクル

```
運用者 → 管理画面 → 集約器 (Exporter / Eraser)
                       │
                       ├── Provider A (あなたのプラグイン)
                       ├── Provider B (DixlaseUsers)
                       └── Provider C (CoreMember)
```

集約器は登録された各 Provider を順に呼び出します:
- **Exporter** はプロバイダごとにディレクトリを切った 1 つの ZIP と、トップレベルの `manifest.json` を生成します。
- **Eraser** はプロバイダごとに `UserDataDeletionDTO` を 1 件ずつ集約し、個別失敗時も継続します。

集約器は単一プロバイダの失敗で全体を中断しません。失敗内容は manifest（エクスポート）または `errors[]`（削除）に記録され、運用者が部分結果に対処できます。

---

## Contract の実装手順

### 1. Provider クラスを追加する

プラグインの名前空間配下に `PrivacyDataProviderInterface` 実装クラスを置きます。慣例の配置場所:

```
plugins/{YourPlugin}/app/Privacy/YourPluginPrivacyProvider.php
```

`PluginCapabilityInterface` から継承される必須メソッド:
- `getPluginSlug(): string` — `plugin.json` で宣言したスラッグを返します。
- `isCapabilityAvailable(): bool` — 実行時に無効化したい場合は `false` を返します（設定で機能 OFF のときなど）。

本 Contract の必須メソッド:
- `privacyProviderKey(): string` — エクスポート ZIP のルートディレクトリ名となる安定 ID。通常はプラグインスラッグ。
- `privacyDataDescription(): array` — `['en' => '...', 'ja' => '...']`。管理画面に表示される運用者向け説明。
- `exportUserData(int $userId, ?int $siteId = null): UserDataExportDTO`
- `deleteUserData(int $userId, DeletionMode $mode, ?int $siteId = null): UserDataDeletionDTO`

### 2. ServiceProvider で Provider をタグ登録する

```php
public function register(): void
{
    $this->app->tag(
        [\Plugins\YourPlugin\App\Privacy\YourPluginPrivacyProvider::class],
        \App\Services\Plugin\PluginServiceResolver::CAPABILITY_TAG,
    );
}
```

### 3. `plugin.json` で権限を宣言する

```json
"permissions": {
    "privacy": {
        "export": true,
        "delete": true
    }
}
```

集約器は `PluginPermissionService::check($slug, 'privacy.export'|'privacy.delete')` でプラグイン Provider をゲートします。コア Provider（slug `core` または `core-*` で始まるもの）は `plugin.json` を持たないためこのゲートをバイパスします。

---

## マルチサイトスコープ

`$siteId` でエクスポート・削除のスコープを制御します:

| `$siteId` の値 | 意味 |
|---|---|
| `null` | ネットワーク全体。全サイトの行 + グローバルテーブル。 |
| 具体的な `int` | そのサイトに紐づくデータのみ。サイトスコープ列を持たない Provider は **空 DTO + `UserDataExportDTO::$warnings` での説明**を返さなければなりません。 |

3 つの典型パターン:

### パターン A: 完全サイトスコープ Provider
すべての対象テーブルが `site_id` 列を持つ。`$siteId` 指定時はそれで絞り、`null` 時は全サイト含めます。

### パターン B: 完全非サイトスコープ Provider（DixlaseUsers パターン）
対象テーブルに `site_id` 列がない。`$siteId !== null` のときは空 DTO + warning を返します:

```php
if ($siteId !== null) {
    return new UserDataExportDTO(
        providerKey: 'your-plugin',
        warnings: ['Your-plugin data is not site-scoped; siteId=null で再実行してください。'],
    );
}
```

### パターン C: 混在（CoreMember パターン）
一部テーブルはサイトスコープ（例: `audit_logs`）、一部は非サイトスコープ（例: `members`）。`$siteId !== null` のときはグローバルテーブルを除外し、サイトスコープテーブルだけ `site_id` で絞ります。warning で運用者に除外内容を伝えます。

---

## 削除モードの意味論

Contract では `DeletionMode` で 3 つのモードを定義しています:

| モード | 戦略 |
|---|---|
| `HardDelete` | 行を物理削除。監査ログ系（`audit_logs`, `security_events`）は **行は残し PII のみ匿名化** すること（追加専用ハッシュチェーンを保つため）。 |
| `SoftDelete` | 親行の `deleted_at` をセット。子行は復元のために残します。soft-delete 非対応のテーブルは `HardDelete` にフォールバックし、その制約を `errors[]` に記録します。 |
| `Anonymize` | 全行を残し、識別フィールド（メール、IP、UA、氏名、電話）を `app.key` 由来の salt による HMAC-SHA256 で置換します。元値は復元不能（同じ `app.key` でも復号できません）。 |

### Anonymize ハッシュの計算式

正準的な導出は次のとおり:

```php
$salt = hash('sha256', config('app.key').'|dixlase-privacy-anonymize');
$hash = substr(hash_hmac('sha256', $value, $salt), 0, 32);
```

- 派生 salt を使った HMAC-SHA256。
- **32 桁 hex** に切り詰め（128 bit の衝突耐性。`ip_address VARCHAR(45)` 等の狭い PII 列に収めるため）。
- `null` や空文字入力は `null` を返し、「空文字のハッシュ」プレースホルダを保存しないようにします。

### `app.key` ローテーション

運用者が `app.key` をローテーションすると、ローテ前と後の同じ入力に対するハッシュは一致しなくなります。これは仕様通り: 匿名化は「一方向フェンス」が目的で、ローテをまたいだ相関がとれないことが望ましい挙動です。

---

## スケルトン例

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

## 運用者向け管理画面

コアには `/admin/privacy/users`（SUPER_ADMIN 限定）の最小スタブが含まれます。運用者ができること:
- メンバーを ID・メール・アカウント名で検索。
- 対象メンバーのデータを ZIP でエクスポート（現サイトのみ / ネットワーク全体）。
- 全 Provider に対して指定モードで削除を実行。

このページは意図的に最小実装です。より高機能な subject access ワークフローが必要な場合、プラグインは同じ集約サービスを使って自前 UI を組み立てられます。

---

## 参照

- Contract: `app/Contracts/PluginIntegration/PrivacyDataProviderInterface.php`
- 参考実装: `app/Services/Privacy/Providers/CoreMemberPrivacyProvider.php`
- プラグイン実装例: `plugins/DixlaseUsers/app/Privacy/DixlaseUsersPrivacyProvider.php`
- 集約器: `app/Services/Privacy/UserPrivacyExporter.php`, `UserPrivacyEraser.php`
- テスト: `tests/Feature/PluginPrivacy/`, `plugins/DixlaseUsers/tests/Feature/Privacy/`
