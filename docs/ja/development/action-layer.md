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

## 監査はどこで書くか

> **監査ログを書くのは Action だけ。**`audit_logs` に行を残すべきものは、
> Action を通すか、Action が呼ぶ共有サービスを通す。

監査の単位はテーブルへの書き込みではなく**操作**であり、操作が置かれる場所が
Action である。`AbstractAction::execute()` は `handle()` の成功後に `audit()` を
呼ぶので、成功した `ActionResult` を返した Action は既に行を書いている。その
`ActionResult` のメタデータ（`before` / `after` / `diff`）が、そのままエントリの
内容になる。

### やり方が 1 つだけである理由

`App\Traits\AuditableTrait` は、モデル層にもう 1 つの仕組みを提供していた
（`created` / `updated` / `deleted` から行を書く）。**これは v0.2.0 で削除した**
（0.1.8 から非推奨）。コア・公式プラグイン・テーマのどこからも一度も使われておらず、
コードベースが守っていない約束として読めてしまっていた — このトレイトを見つけた
読み手は「モデルへの書き込みは自動で監査される」と考えるのが自然だが、そうは
なっていない。

かわりに全面採用しても安くはならない。Action の内と外の両方で触られるモデルは、
1 つの操作に対して 2 行を書いてしまう。だからこそ旧来の規約には `withoutAudit()`
の手当てと責務の表が必要だった。守る側にとって、答えは 2 つより 1 つのほうが安い。
このトレイトをまだ使っている拡張機能は v0.2.0 では読み込めない。監査する操作を、
下のように Action に移すこと。

### ではどう書くか

| 監査したいもの | やること |
|---|---|
| 利用者や運営者が行う操作 | `AbstractAction` のサブクラスに置く。`audit()` が行を書く |
| ジョブやコマンドが行うこと | そこにも Action を用意する。または、コマンドが呼ぶサービスから `Audit` ファサードを呼ぶ |
| サービスの奥にある書き込み | サービスではなく、その操作を所有する Action に説明させる |

短い例 — 監査のための配線は Action だけで済む:

```php
final class PublishPageAction extends AbstractAction
{
    protected function handle(Actor $actor, array $data): ActionResult
    {
        $page = DixlasePagesPage::query()->findOrFail($data['page_id']);
        $before = $page->only('status');
        $page->update(['status' => PageStatus::Published->value]);

        // これが返ったあと execute() が audit() を呼び、行を書く
        return ActionResult::success($page, metadata: [
            'before' => $before,
            'after' => $page->only('status'),
        ]);
    }
}
```

### 唯一の例外: サインイン

サインインは Action ではない。すべての認証の経路が合流するのは Laravel の `Login`
イベントだけなので、`AuditAuthEventSubscriber::handleLogin()` がそこから `login` の
行を書く。イベントはメンバーが*何で*認証したかを持たないので、各経路はメンバーを
ログインさせる直前に、その要素を監査サービスに渡す:

```php
Audit::setAuthFactor(AuditLog::AUTH_FACTOR_PASSKEY);
Auth::guard('member')->login($member, $remember);
```

サブスクライバーはその要素を読んで消す。この呼び出しが Action ではなく認証の
トレイト（`LoginTrait`、`PasskeyLoginTrait`、`TwoFaAuthenticationTrait`）にあるのは
そのためである。`Auth::login()` より後のどこかへ移すと、行を書く時点で要素はもう
消えている。

### 行に入るもの

`AbstractAction::audit()` はアクターをモデル（`toAuditMorph()`）として、対象を
`ActionResult::$model` として渡す。そのため行には `actor_type` / `actor_id` と
`target_type` / `target_id` が入る。`actor_id` と対象の組はハッシュチェーンの対象で、
`actor_name` と `target_label` はその外にある表示用のスナップショットである。

Eloquent モデルでない対象も名指しできる。モデル無しで `targetType` と `targetId` を
持つ `ActionResult` を返すか、`audit()` を上書きするなら `Audit::log()` に
`target_type` / `target_id` を渡す。id の列は符号なし整数なので、数値でない id は
挿入を失敗させる代わりに落とされる（ラベルは残る）。

**0.1.15 より前に書かれた行には、アクターと対象の id が無い。** そのリリースまで
`AuditLog::log()` は `audit()` が渡す列名のキーを無視していたので、Action が書いた行は
すべて `actor_type` / `actor_id` / `target_type` / `target_id` が NULL で、誰が・何をを
示すのは `actor_name` / `target_label` だけである。これらの行は遡って埋めない。
`actor_name` からアカウントを確実に引き当てることはできず、id を書き換えれば行の
ハッシュが壊れるためである（#546）。

## Plugin API サーフェス

以下の Action レイヤーシンボルは `@api`（Plugin API）:

- `App\Actions\AbstractAction`
- `App\DTO\Action\ActionResult`
- `App\Contracts\Action\ActionInterface`
- `App\Contracts\Action\Actor`

実装クラス（`App\Services\Audit\*`、`App\Models\AuditLog`、`App\Facades\Audit`
など）は Plugin API ではない。上記のコントラクトを使うこと。
