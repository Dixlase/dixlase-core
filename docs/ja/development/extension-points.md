# 予約された拡張ポイント

> **状態**: Phase 1 で予約された項目は安定（Stable）。既定実装は意図的に no-op であり、本番動作はゼロトラストロードマップ（`.backlog/zero-trust-roadmap.ja.md`）の後続フェーズで提供される。
>
> **対象読者**: ゼロトラスト機能（マネージドシークレットストア、条件付きアクセス、ABAC、IAP 連携、mTLS / デバイストラスト）を提供するプラグイン作者・連携実装者。

## なぜ予約するのか

ゼロトラストロードマップは、初期リリースのスコープ外だが**公開面**は β からブレないままにしたい機能をいくつか抱える。インターフェースとミドルウェアエイリアスを先に予約しておくことで:

- プラグイン作者は今日から凍結された契約に対して開発できる
- Dixlase の既定動作は変わらない（既定実装はすべて意図的な no-op）
- Phase 2 / Phase 3 の実装は、新たな公開面を導入するのではなく、既存のフックに差し込まれる（プラグインが先行参照したものと競合しない）

各予約は、Plugin API の他と同じ互換ポリシーに従って、契約の**形（メソッドシグネチャ・戻り型・enum case）**を凍結する — [`PLUGIN-API.md`](../../../PLUGIN-API.md#stability-pledge) を参照。

## クイックリファレンス

| ID | 予約 | 既定 | 置換方法 |
|---|---|---|---|
| C-1 | [`App\Contracts\Security\SecretProviderInterface`](#c-1-secretproviderinterface) | `EnvSecretProvider`（`.env` から読む） | サービスコンテナでリバインド |
| C-2 | [`App\Contracts\Security\RiskEvaluatorInterface`](#c-2-riskevaluatorinterface) | `LowRiskEvaluator`（常に Low） | サービスコンテナでリバインド |
| C-3 | [`App\Contracts\Security\PolicyEvaluatorInterface`](#c-3-policyevaluatorinterface) | `NullPolicyEvaluator`（常に Defer） | サービスコンテナでリバインド |
| C-4 | `auth.iap` ミドルウェアエイリアス | `AuthenticateIap`（501 を abort） | `App\Http\Middleware\AuthenticateIap` を置換 |
| C-5 | `auth.mtls` ミドルウェアエイリアス | `AuthenticateMtls`（501 を abort） | `App\Http\Middleware\AuthenticateMtls` を置換 |

---

## C-1. SecretProviderInterface

**契約**: `App\Contracts\Security\SecretProviderInterface`
**既定実装**: `App\Services\Security\EnvSecretProvider`
**コンテナバインド**: `AppServiceProvider::register()`

### シグネチャ

```php
namespace App\Contracts\Security;

interface SecretProviderInterface
{
    public function get(string $key, ?string $default = null): ?string;
}
```

### 既定動作

Laravel の `env()` ヘルパー経由でローカル `.env` から読む。未知のキーには `null`（または与えられた `$default`）を返す。

### 存在意義

本番デプロイは最終的に、長期 `.env` 値ではなく**ローテート可能で KMS 裏付けされたシークレット**（DB 認証情報、署名鍵、API トークン）が必要になる。ZT ロードマップの Phase 3.9 で Vault / AWS KMS / GCP Secret Manager 連携が実装される。今インターフェースを予約しておけば、それらの連携は呼び出し側を一切触らずコンテナのリバインドで差し込める。

### 実装側の要件

- 未知のキーは throw せず `null` を返すこと
- `$default` が与えられたら、未知のキーに対してそれを返すこと
- キーは不透明として扱う（クォート・パース禁止）
- 繰り返し呼ばれても安全であること（呼び出し側はキャッシュしないかもしれない）

### 置換例（スケッチ）

```php
namespace MyPlugin\Services;

use App\Contracts\Security\SecretProviderInterface;

final class VaultSecretProvider implements SecretProviderInterface
{
    public function get(string $key, ?string $default = null): ?string
    {
        // Vault と通信して値 or $default を返す
    }
}

// プラグインのサービスプロバイダ内:
$this->app->bind(SecretProviderInterface::class, VaultSecretProvider::class);
```

---

## C-2. RiskEvaluatorInterface

**契約**: `App\Contracts\Security\RiskEvaluatorInterface`
**入力 DTO**: `App\DTO\Security\LoginContext`
**出力 DTO**: `App\DTO\Security\RiskScore`（`App\Enums\AccessRiskLevel` を保持）
**既定実装**: `App\Services\Security\LowRiskEvaluator`
**コンテナバインド**: `AppServiceProvider::register()`

### シグネチャ

```php
namespace App\Contracts\Security;

use App\DTO\Security\LoginContext;
use App\DTO\Security\RiskScore;

interface RiskEvaluatorInterface
{
    public function evaluate(LoginContext $context): RiskScore;
}
```

### 既定動作

無条件に `RiskScore::low()` を返す。条件付きアクセスポリシーは初期状態では強制されない。

### 存在意義

ZT ロードマップ Phase 3.1 で、`LoginBehaviorService` が既に収集しているシグナル（場所変更、新規デバイスフィンガープリント、時間外、ASN ドリフト）の上にリスクスコアリングが追加される。リスクスコアは step-up 認証要求、セッション寿命判定、ハード拒否ルールを駆動する。今契約を予約することで、呼び出し側は今日からこの interface に依存できる。既定の Low 評価が現状動作を保つ。

### 実装側の要件

- 同じ入力に対して決定論的であること
- 単一リクエスト内で繰り返し呼ばれても安全であること
- コンテキストフィールドの欠落で throw しないこと（欠落自体がシグナル）
- 読み取り専用扱い — DB 書き込みや監査ログ書き込みをしない（呼び出し側の責任）

### 前方互換性

`LoginContext` は今後のマイナー版でフィールドが追加される可能性がある（追加のみ）。実装側は公開プロパティを名前でアクセスし、`match` / `switch` で閉集合を扱う場合は未知の値を「情報量の少ないシグナル」として扱う fallthrough を必ず用意すること（throw 禁止）。

---

## C-3. PolicyEvaluatorInterface

**契約**: `App\Contracts\Security\PolicyEvaluatorInterface`
**判定 enum**: `App\Enums\PolicyDecision`（`Allow` / `Deny` / `Defer`）
**既定実装**: `App\Services\Security\NullPolicyEvaluator`
**コンテナバインド**: `AppServiceProvider::register()`

### シグネチャ

```php
namespace App\Contracts\Security;

use App\Enums\PolicyDecision;
use Illuminate\Contracts\Auth\Authenticatable;

interface PolicyEvaluatorInterface
{
    public function evaluate(
        ?Authenticatable $actor,
        string $action,
        ?object $resource,
        array $context,
    ): PolicyDecision;
}
```

### 既定動作

すべての入力に対して `PolicyDecision::Defer` を返す。認可は既存の `PermissionService` のロールベースチェックにそのまま委ねられる。

### 存在意義

Phase 3.6 で、RBAC の上に Attribute-Based Access Control を追加する（「編集者は信頼 IP レンジから業務時間中のみ公開可能」、「リスクエンジンが旗を立てた国からのアクセスでは管理者でもユーザー削除不可」、外部 OPA 委譲、等）。3 値判定 enum（`Allow` / `Deny` / `Defer`）により、ポリシは「意見がある時だけ短絡」「無い時は邪魔せず通す」を表現できる。

### 呼び出し側の意味論

```
PolicyDecision::Allow  → 呼び出し側は短絡的に allow
PolicyDecision::Deny   → 呼び出し側は短絡的に deny
PolicyDecision::Defer  → 呼び出し側は通常の RBAC チェックを継続
```

既定 `Defer` が ABAC 採用を非破壊にする鍵 — 既存の呼び出し点はすべて、連携プラグインが `Allow` / `Deny` を返し始めるまで完全に従来通りの動作になる。

### 実装側の要件

- 同じ入力に対して決定論的であること
- 単一リクエスト内で繰り返し呼ばれても安全であること
- コンテキストフィールド欠落で throw しないこと
- 読み取り専用 — DB 書き込みや監査ログ書き込みをしない（呼び出し側 — 通常 `PermissionService::evaluate()` — の責任）
- 該当ポリシが無い場合は `PolicyDecision::Defer` を返すこと

---

## C-4. `auth.iap` ミドルウェアエイリアス

**エイリアス**: `auth.iap`
**既定クラス**: `App\Http\Middleware\AuthenticateIap`
**エイリアス登録箇所**: `bootstrap/app.php`

### 既定動作

`abort(501, ...)` で、運用者に IAP 連携プラグインのインストールまたはクラスのリバインドを促すメッセージを返す。

### 存在意義

ZT ロードマップ Phase 3.7 で、Cloudflare Access の JWT 検証、Google IAP、Pomerium、その他類似の Identity-Aware Proxy 向け連携プラグインが提供される。`auth.iap` を今公開エイリアスとして予約することで、プラグイン作者は上流 IAP に関わらず `Route::middleware('auth.iap')` と書け、運用者はルートを書き換えずに IAP を切り替えられる。

### 置換パターン

プラグインは（典型的にはサービスコンテナでバインドするか設定をマージする形で）`App\Http\Middleware\AuthenticateIap` を以下を行う実装に置換する:

1. 該当ヘッダ（`Cf-Access-Jwt-Assertion`、`X-Goog-IAP-JWT-Assertion` など）から IAP 発行 JWT または署名済み assertion を読む
2. IAP の公開鍵で署名を検証
3. assert された ID を Dixlase メンバーに解決し、auth マネージャにバインド
4. ID が確立してから `$next($request)` を呼ぶ

### 運用者向け注意

IAP プラグインがインストールされていないビルドでルートに `auth.iap` を適用するのは設定ミス。501 abort はそのミスを早期に可視化（fail open ではなく fail fast）。

---

## C-5. `auth.mtls` ミドルウェアエイリアス

**エイリアス**: `auth.mtls`
**既定クラス**: `App\Http\Middleware\AuthenticateMtls`
**エイリアス登録箇所**: `bootstrap/app.php`

### 既定動作

`abort(501, ...)` で、運用者にクライアント証明書 / デバイストラストプラグインのインストールまたはクラスのリバインドを促すメッセージを返す。

### 存在意義

Phase 3.3（デバイス登録 / クライアント証明書認証）と Phase 3.8（送信側 Webhook の mTLS）がいずれも検証済みクライアント証明書フローに依存する。一部の運用者は運用者 CA 発行の証明書で admin ルートをゲートしたい、別の運用者はサービス間 mTLS が欲しい。`auth.mtls` エイリアスはルートレベルの入り口。

### 置換パターン

典型的な実装は、上流プロキシが populate するヘッダ（`X-SSL-Client-S-DN`、`X-SSL-Client-Verify`、`X-SSL-Client-Cert` など）から証明書 subject / issuer を読み、検証ステータスを確認し、信頼 CA に chain して登録済みデバイスにマッチしない限り拒否する。

### 運用者向け注意

`auth.iap` と同じ — 裏付け実装なしに `auth.mtls` を適用するのは設定ミスで、501 abort がそれを可視化する。

---

## 互換ポリシー

上記の各項目は Plugin API の安定化対象。同一メジャー版内では:

- 新メソッド追加可（追加のみ）
- 新オプション引数（既定値付き）追加可
- 新 enum case 追加可（利用側は常に fallthrough を持つこと）
- DTO への新プロパティ追加可

改名・型変更・削除は [Plugin API deprecation policy](../../../PLUGIN-API.md#deprecation-policy) に従う: 1 マイナー期間 deprecation、次メジャー版で削除。

## 命名規約

ここで使う識別子名は [`docs/development/naming.md`](naming.md) のルールに従う:

- `PolicyEvaluatorInterface::evaluate()` の `$action` 引数に渡す permission キー: dot パスの lower_snake_case。
- プラグイン提供実装の設定 env キー: `SCREAMING_SNAKE_CASE`。

## 関連

- [`PLUGIN-API.md`](../../../PLUGIN-API.md) — Plugin API stability pledge と deprecation policy
- `.backlog/zero-trust-roadmap.ja.md`（メンテナ専用）— 本予約を駆動するフェーズ計画
- [`SECURITY.md`](../../../SECURITY.md) — 運用者向けセキュリティガイダンス（IAP / TrustProxies）
- [`docs/development/naming.md`](naming.md) — 公開識別子の命名規約
