# リビジョン API

## 概要

Dixlase はコア/プラグインの任意のコンテンツモデルが利用できる共通リビジョン機能を
提供します。保存ごとにスナップショットを取得し、差分がなければスキップ、
保持件数超過時の自動削除、リビジョンごとの保護フラグ、管理画面向けの
サイドバイサイド差分表示をサポートします。

## バージョン

- **仕様バージョン**: 1.0
- **ステータス**: Stable

## 1. アーキテクチャ

```
┌─────────────────┐  use HasRevisions   ┌─────────────────┐
│ コンテンツモデル │ ─────────────────▶ │  リビジョン     │
│ implements      │                     │  モデル         │
│  Revisionable   │                     │ (自前のテーブル)│
└────────┬────────┘                     └─────────────────┘
         │
         │ record/restore
         ▼
┌─────────────────┐
│ RevisionService │  （汎用・@api）
└─────────────────┘
```

設計方針:

- **コンテンツタイプごとに専用テーブル** — プラグインは自前のリビジョン
  テーブルを持つ（例: `dls_plg_dixlase_pages_post_revisions`）。
  「プラグインは自分のテーブルのみアクセス可能」というルールに整合
- **Contract ドリブン** — `App\Contracts\Revisionable` が 3 つの情報を
  宣言するのみ。継承は不要
- **プラグイン別ファサードは任意** — コアの `FrontPageRevisionService` は
  既存呼び出し元との互換のための薄いラッパー

## 2. 公開 API

以下は全て `@api` タグ付きで、AGPL のプラグイン/テーマ例外条項の下で
プラグイン・テーマから安全に依存可能です。

### 2.1 Contract

| FQCN | 用途 |
|------|------|
| `App\Contracts\Revisionable` | 履歴を持つコンテンツモデルが実装 |

メソッド:

| メソッド | 戻り値 | 説明 |
|---------|-------|------|
| `revisionModel()` | `class-string<Model>` | リビジョンモデルの FQCN |
| `revisionForeignKey()` | `string` | リビジョンテーブル上の親 FK カラム名 |
| `revisionableFields()` | `list<string>` | スナップショット対象属性名 |

### 2.2 Trait

| FQCN | 用途 |
|------|------|
| `App\Traits\HasRevisions` | `revisions()` HasMany リレーションを提供 |

### 2.3 Service

| FQCN | 用途 |
|------|------|
| `App\Services\RevisionService` | 汎用の記録/復元/削除/比較エンジン |

主要定数: `TYPE_AUTO`, `TYPE_MANUAL`, `TYPE_RESTORE_BACKUP`,
`SETTING_KEY_RETENTION`, `DEFAULT_RETENTION`, `MAX_RETENTION`。

主要メソッド:

| メソッド | シグネチャ | 備考 |
|---------|----------|------|
| `record` | `record(Revisionable, string $type, ?int $userId, ?string $note): ?Model` | 直前と同一内容ならスキップ |
| `restore` | `restore(Model $revision, ?int $userId): Revisionable` | 現状が直前と差分がある時のみ `restore_backup` を生成 |
| `buildSnapshot` | `buildSnapshot(Revisionable): array<string,mixed>` | 正規化済みスナップショット |
| `countProtected` | `countProtected(Revisionable): int` | `is_protected = true` の件数 |
| `getRetentionCount` | `getRetentionCount(): int` | `content.revision.retention_count` を読み、`[0, MAX_RETENTION]` にクランプ |

プラグイン側で別ルール（例: Legal プラグインでの無制限保持）が必要な場合は、
サブクラスで `getRetentionCount()` 等を上書きしてください。

### 2.4 差分プレゼンター

| FQCN | 用途 |
|------|------|
| `App\Presenters\Admin\RevisionDiffPresenter` | 2 つの文字列をサイドバイサイド行に変換 |

### 2.5 Blade コンポーネント

| コンポーネント | 用途 |
|---------------|------|
| `<x-revision.list>` | ページネーション付きリビジョン一覧表（保護・復元ボタンとサマリー付き） |
| `<x-revision.diff>` | 差分ビューア（メタデータ表、メモ編集、保護・復元ボタン付き） |

## 3. コンテンツモデルへのリビジョン追加

### 3.1 必須スキーマ

リビジョンテーブルは以下のカラムを持つこと:

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

テーブル命名規則:

- コア: `{entity}_revisions`（例: `front_page_revisions`）
- プラグイン: `dls_plg_{slug}_{entity}_revisions`
- テーマ: `dls_thm_{slug}_{entity}_revisions`

### 3.2 リビジョンモデル

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

### 3.3 親モデル

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

### 3.4 親の逆引き

`RevisionService::restore()` は以下のいずれかの名前のリレーションメソッドを
リビジョンモデルから探して親を取得します:

`content`, `target`, `revisionable`, `frontPage`, `page`, `post`, `legal`,
`article`, `entry`

リビジョンモデルの `belongsTo` リレーションはこのいずれかで命名するか、
`RevisionService::loadTarget()` をサブクラスで上書きしてください。

## 4. 保存フローへの組み込み

保存系 Action に `RevisionService` を DI して、`update()` 直後に呼び出します:

```php
$this->revisionService->record(
    $page->fresh(),
    type: RevisionService::TYPE_MANUAL,
    userId: $actor->getActorId(),
);
```

ユーザー明示保存は全て `TYPE_MANUAL` を使用してください。`TYPE_AUTO` は
将来のバックグラウンド自動保存用に予約されています。`TYPE_RESTORE_BACKUP` は
サービス内部のみが生成します。

## 5. 監査ログ

管理画面からの操作は**必ず Action 経由**にして監査ログに残るようにしてください。
コアの FrontPage の 3 Action をコピーしてプラグイン用に作成します:

- `RestoreXxxRevisionAction` — 監査アクション `xxx.revision.restored`
- `ToggleXxxRevisionProtectionAction` — 監査アクション `xxx.revision.protection_toggled`
- `UpdateXxxRevisionNoteAction` — 監査アクション `xxx.revision.note_updated`

監査エントリはカテゴリ `content` で既存の監査ログテーブルに記録され、
監査ログ画面とフィルタドロップダウンに自動的に表示されます。

## 6. 管理画面 UI コンポーネント

どちらのコンポーネントも URL ではなくルート名を受け取り、深くネストした
ルート構造にも対応します:

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

`$parentParams` は生成される全 URL の先頭に付加されます。

## 7. 設定

設定キー `content.revision.retention_count` は全コンテンツタイプ共通です。
有効範囲: `0`（機能無効）〜 `500`（最大）。デフォルト `50`。

UI: **管理画面 > 設定 > 基本設定 > コンテンツ**（詳細モード限定）

保持件数は非保護リビジョンにのみ適用されます。保護されたリビジョンは
自動削除されないため、結果的に総件数が保持件数を超える場合があります
（意図された挙動）。

## 8. テスト

リファレンス実装のテストは以下を参照:

- `tests/Unit/FrontPageRevisionServiceTest.php`
- `tests/Unit/RevisionProtectionTest.php`
- `tests/Feature/Admin/Front/RevisionActionAuditTest.php`

## 9. 非対応事項

以下は現在のリリースで意図的に対応していません:

- ソフトデリート（自動削除はハードデリートで実装されている）
- 手動「リビジョン作成」ボタン（明示保存自体が既に手動リビジョン）
- ブランチ機能（複数編集系統の並行管理）
- リビジョンのエクスポート/インポート
- 期間ベースの保持（件数ベースのみ）
