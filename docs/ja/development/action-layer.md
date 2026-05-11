# Action レイヤー

> **状態（v0.1）:** ライフサイクルと `ActionResult` メタデータ
> スキーマは Plugin API 契約の一部であり、`^0.1` の範囲で凍結されている。
> ライフサイクル順序（`authorize → validate → handle → audit → events`）は
> v1.0 までステップ間に新規ステップが挿入されたり並び替えられたりしない。

## 概要

Action は監査対象の業務操作 1 つを単位とするクラス。
`app/Actions/`（コア）または `plugins/{Name}/app/Actions/`（プラグイン）
配下に置き、`App\Actions\AbstractAction` を継承する。

`execute(Actor $actor, array $data)` を呼び出すごとに、以下のテンプレート
メソッド・ステップが順に実行される。

| # | ステップ     | フック                              | 既定動作                                                     |
|---|-------------|-------------------------------------|--------------------------------------------------------------|
| 1 | 認可        | `authorize()`                       | `requiredPermission()` に基づくパーミッション確認。           |
| 2 | バリデーション | `validate()`                     | no-op。サブクラスで業務ルール検証を上書き。                  |
| 3 | 実処理      | `handle()`（abstract）              | サブクラスが実装。既定で DB トランザクションに包む。          |
| 4 | 監査        | `audit()`                           | 成功時に `audit_logs` 行を 1 件書き込む。                     |
| 5 | ドメインイベント | `dispatchEvents()`               | no-op。サブクラスで `DixlaseEvents` 定数を発火。              |

途中のステップが例外を投げると、以降のステップは実行されない。トランザクション
（ステップ 3）は `useTransaction()` が `true`（既定）を返すときのみ開かれる。

## `validate()` フック

`validate()` は、Laravel Form Request では表現できない業務ルール検証
（フィールド間整合性、状態マシンの遷移、サイトスコープでの一意性など）の
正規の置き場所。Form Request は HTTP 形状の検証（型・必須・正規表現）を担当
し、Action がセマンティクスを担当する。

```php
namespace Plugins\DixlasePages\App\Actions\Page;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use Illuminate\Validation\ValidationException;

final class PublishPageAction extends AbstractAction
{
    protected function validate(Actor $actor, array $data): void
    {
        $page = DixlasePagesPage::query()->findOrFail($data['page_id']);

        if ($page->status === PageStatus::Trashed) {
            throw ValidationException::withMessages([
                'page_id' => __('plugin/dixlase-pages::pages.cannot_publish_trashed'),
            ]);
        }
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        // ...
    }
}
```

`Illuminate\Validation\ValidationException` を投げると、Form Request と同じ
422 系のエラー形状で中断される。その他の例外はそのまま伝播する。

## `ActionResult` メタデータスキーマ

`App\DTO\Action\ActionResult::$metadata` は `array<string, mixed>` のオープン
型だが、以下のキーは**予約**されており、`^0.1` の範囲で shape が変わらない。
プラグインは該当する場合これらのキーに書き込むべき。

| キー           | shape                                                                                | 用途                                                                          |
|----------------|--------------------------------------------------------------------------------------|-------------------------------------------------------------------------------|
| `diff`         | `array<string, array{from: mixed, to: mixed}>`                                       | UPDATE 系 Action のフィールド単位差分。                                       |
| `before`       | `array<string, mixed>`                                                               | 変更前の属性スナップショット。                                                |
| `after`        | `array<string, mixed>`                                                               | 変更後の属性スナップショット。                                                |
| `changes`      | `array<string, mixed>`                                                               | `after` のうち変更があった属性のみ。                                          |
| `warnings`     | `array<int, string>`                                                                 | `handle()` 中に発生した致命的でない問題（例:「無効な 2 行をスキップ」）。     |
| `side_effects` | `array<int, array{type: string, target: string, ...}>`                               | 副作用操作（キャッシュ無効化、通知送信、…）。                                 |

これら以外のキーも書き込めるが、将来コアが新しい予約キーを増やしたときの衝突を
避けるため、**プラグインスラッグでプレフィックスを付ける**こと
（例: `dixlase_pages.revision_id`）。

呼び出し側は、すべてのメタデータキーをオプショナルとして扱い、不在は「情報なし」
であって「変更なし」ではないと解釈すること。

## AuditableTrait と Action 監査責務の使い分け

両方のレイヤーが `audit_logs` 行を生成し得る。重複を避けるためのルールは 1 つ:

> **Action は、それがラップする操作の監査ログを所有する。**
> `handle()` 内で触る Eloquent モデルが
> `App\Traits\AuditableTrait` を使う場合、トレイトの自動ログ出力は抑制する。

トレイトは `bootAuditableTrait()` でモデルのライフサイクルイベント
（`created` / `updated` / `deleted`）から `audit_logs` 行を発火する。
Action の `audit()` メソッドはその後でより業務情報を載せたエントリを書き込む。
抑制しないと、1 つの操作に対し文脈が重複する 2 行が生成される。

### 推奨パターン

```php
protected function handle(Actor $actor, array $data): ActionResult
{
    $page = DixlasePagesPage::query()->findOrFail($data['page_id']);

    // AuditableTrait の自動エントリを抑制。後段で AbstractAction::audit()
    // が業務レベルの正本エントリを書き込む。
    $page->withoutAudit(function () use ($page, $data) {
        $page->update(['status' => PageStatus::Published->value]);
    });

    return ActionResult::success($page, 'Page published', $page->title, [
        'before' => ['status' => PageStatus::Draft->value],
        'after'  => ['status' => PageStatus::Published->value],
        'diff'   => ['status' => ['from' => 'draft', 'to' => 'published']],
    ]);
}
```

`withoutAudit()` はトレイトの公開 API なので、プラグインから呼んで安全。

### AuditableTrait 単独で正しいケース

モデルが Action の外側で変更される場合（レガシーなコントローラ、直接書き込む
キュージョブ、プラグイン内部のバックグラウンドタスクなど）は、AuditableTrait
がそのまま正しい選択。記録漏れを防ぐ。

### 新規モデルでの選び方

| モデルが触られる場所 | 採用する戦略 |
|---|---|
| Action の中だけ | AbstractAction の `audit()`。AuditableTrait は付けない。 |
| Action の中と外の両方 | AuditableTrait + `handle()` 内で `withoutAudit()`。 |
| Action の外だけ | AuditableTrait 単独。 |

## Plugin API サーフェス

以下の Action レイヤーシンボルは `@api`（Plugin API）:

- `App\Actions\AbstractAction`
- `App\DTO\Action\ActionResult`
- `App\Contracts\Action\ActionInterface`
- `App\Contracts\Action\Actor`
- `App\Traits\AuditableTrait`

実装クラス（`App\Services\Audit\*`、`App\Models\AuditLog`、`App\Facades\Audit`
など）は Plugin API ではない。上記のコントラクトを使うこと。
