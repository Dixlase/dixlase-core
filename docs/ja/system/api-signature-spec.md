# Dixlase API 署名仕様 v1

## 概要

このドキュメントはDixlase APIおよびWebhook認証のためのHMAC-SHA256署名仕様を定義します。すべての外部APIリクエストとWebhook配信は、メッセージの整合性と真正性を保証するため、この仕様に準拠しなければなりません（MUST）。

## バージョン

- **仕様バージョン**: v1
- **アルゴリズム**: HMAC-SHA256
- **ステータス**: 安定版

## 1. 署名のコンポーネント

### 1.1 必須HTTPヘッダー

| ヘッダー名 | 説明 | 例 |
|-----------|------|-----|
| `X-Dixlase-Key` | APIキー識別子 | `dls_key_abc123...` |
| `X-Dixlase-Timestamp` | Unixタイムスタンプ（秒） | `1733500800` |
| `X-Dixlase-Signature` | バージョンプレフィックス付き署名 | `v1=a1b2c3d4...` |

### 1.2 署名文字列のフォーマット

署名文字列は以下のコンポーネントを改行文字（`\n`）で結合して構成します：

```
{timestamp}\n{http_method}\n{path}\n{body_hash}
```

| コンポーネント | 説明 | 例 |
|--------------|------|-----|
| `timestamp` | Unixタイムスタンプ（ヘッダーと同一） | `1733500800` |
| `http_method` | 大文字のHTTPメソッド | `POST` |
| `path` | クエリ文字列を含まないリクエストパス | `/api/v1/orders` |
| `body_hash` | リクエストボディのSHA-256ハッシュ（16進数） | `e3b0c44298fc...` |

### 1.3 ボディハッシュのルール

- **空のボディ**: 空文字列のSHA-256ハッシュを使用（`e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`）
- **空でないボディ**: リクエストボディのSHA-256ハッシュを16進数文字列として使用
- **エンコーディング**: ボディはそのまま（生バイト）ハッシュ化する必要があり、URLエンコードしない

## 2. 署名の生成

### 2.1 アルゴリズム

```
signature = HMAC-SHA256(secret, signing_string)
```

### 2.2 実装例（PHP）

```php
$timestamp = time();
$method = 'POST';
$path = '/api/v1/orders';
$body = '{"order_id": 123}';
$secret = 'your_api_secret';

// ステップ1: ボディハッシュを作成
$bodyHash = hash('sha256', $body);

// ステップ2: 署名文字列を構築
$signingString = implode("\n", [
    $timestamp,
    strtoupper($method),
    $path,
    $bodyHash,
]);

// ステップ3: 署名を生成
$signature = hash_hmac('sha256', $signingString, $secret);

// ステップ4: ヘッダーを設定
$headers = [
    'X-Dixlase-Key' => $apiKey,
    'X-Dixlase-Timestamp' => $timestamp,
    'X-Dixlase-Signature' => 'v1=' . $signature,
];
```

### 2.3 署名文字列の例

```
1733500800
POST
/api/v1/orders
bf718b6f653bebc184e1479f1935b8da974d701b893afcf49e701f3e2f9f9c5a
```

## 3. 署名の検証

### 3.1 検証手順

1. **ヘッダーの抽出**: `X-Dixlase-Key`、`X-Dixlase-Timestamp`、`X-Dixlase-Signature` を取得
2. **タイムスタンプの検証**: サーバー時刻との差が±300秒（5分）を超える場合は拒否
3. **シークレットの検索**: キーからAPIクライアントを特定し、シークレットを取得
4. **署名のパース**: `v1=xxxxx` 形式からバージョンと署名値を抽出
5. **署名文字列の再構築**: 送信側と同じルールで構築
6. **期待される署名の算出**: `HMAC-SHA256(secret, signing_string)`
7. **署名の比較**: 定数時間比較（`hash_equals`）を使用

### 3.2 エラーレスポンス

| HTTPステータス | エラーコード | 説明 |
|--------------|------------|------|
| 401 | `missing_headers` | 必須の署名ヘッダーが不足 |
| 401 | `timestamp_expired` | タイムスタンプが許容範囲外 |
| 401 | `invalid_api_key` | APIキーが見つからない、または無効化済み |
| 401 | `unsupported_version` | 署名バージョンが未対応 |
| 401 | `invalid_signature` | 署名の検証に失敗 |

### 3.3 エラーレスポンスのフォーマット

```json
{
    "error": {
        "code": "invalid_signature",
        "message": "Signature verification failed"
    }
}
```

## 4. セキュリティに関する考慮事項

### 4.1 タイムスタンプの許容範囲

- デフォルトの許容範囲: ±300秒（5分）
- これにより、クロックスキューを許容しつつリプレイ攻撃を防止
- サーバーは正確な時刻を維持するためにNTPを使用すべきです（SHOULD）

### 4.2 リプレイ攻撃の防止

高セキュリティのシナリオでは、サーバーは追加のリプレイ防御を実装してもよい（MAY）：

- `(timestamp, signature)` のペアを一時的に保存
- 以前に使用された組み合わせのリクエストを拒否
- キャッシュの保持期間はタイムスタンプの許容範囲と一致させる

### 4.3 シークレットの管理

- シークレットは最低32文字（256ビット）でなければならない（MUST）
- シークレットは暗号論的に安全な乱数生成器で生成すべきです（SHOULD）
- シークレットはデータベースに暗号化またはハッシュ化して保存しなければならない（MUST）
- シークレットをログに記録したりエラーメッセージに含めてはならない（MUST NOT）

### 4.4 定数時間比較

タイミング攻撃を防止するため、署名の検証には常に定数時間比較を使用してください：

```php
// 正しい方法
hash_equals($expected, $actual);

// 間違い（タイミング攻撃に脆弱）
$expected === $actual;
```

## 5. Webhook署名

DixlaseがWebhookを外部システムに送信する際も、同じ署名形式を使用します：

### 5.1 Webhookヘッダー

```
X-Dixlase-Timestamp: 1733500800
X-Dixlase-Signature: v1=a1b2c3d4e5f6...
```

注意: 受信側はどのDixlaseインスタンスからの送信かを既に把握しているため、Webhookリクエストには `X-Dixlase-Key` は含まれません。

### 5.2 Webhookの検証（受信側）

受信側は以下を行う必要があります：

1. 登録時に提供されたWebhookシークレットを保管する
2. 同じアルゴリズムで署名を検証する
3. 無効または期限切れの署名を持つリクエストを拒否する

## 6. バージョン移行

### 6.1 バージョンフォーマット

署名にはバージョンプレフィックスが含まれます: `v1=signature_hex`

### 6.2 将来のバージョン

仕様変更が必要な場合：

1. 新しいバージョン（例: `v2`）が導入される
2. 移行期間中は両方のバージョンがサポートされる
3. 非推奨通知は少なくとも6ヶ月前に提供される
4. 移行期間後に旧バージョンは無効化される

### 6.3 複数バージョンのサポート

サーバーは複数の署名バージョンを受け入れてもよい（MAY）：

```
X-Dixlase-Signature: v1=abc123,v2=def456
```

## 7. APIキーのフォーマット

### 7.1 キーの構造

```
dls_{type}_{random}
```

| コンポーネント | 説明 | 例 |
|--------------|------|-----|
| `dls` | Dixlaseプレフィックス | `dls` |
| `type` | キーの種別 | `key`, `wh`（webhook） |
| `random` | ランダム識別子（32文字） | `abc123def456...` |

### 7.2 例

- APIキー: `dls_key_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6`
- Webhookキー: `dls_wh_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6`

## 8. 実装チェックリスト

### APIクライアント向け

- [ ] リクエスト時にタイムスタンプを生成
- [ ] 正しいフォーマットで署名文字列を構築
- [ ] HMAC-SHA256署名を算出
- [ ] 必須ヘッダーをすべて含める
- [ ] 401エラーを適切にハンドリング

### APIサーバー（Dixlase）向け

- [ ] 必須ヘッダーがすべて存在することを検証
- [ ] タイムスタンプが許容範囲内かチェック
- [ ] APIキーを検索しシークレットを取得
- [ ] 定数時間比較で署名を検証
- [ ] 検証失敗をログに記録
- [ ] 適切なエラーコードを返却

### Webhook受信側向け

- [ ] Webhookシークレットを安全に保管
- [ ] 受信するすべてのWebhookの署名を検証
- [ ] タイムスタンプの鮮度をチェック
- [ ] 検証成功後にのみ200 OKを返却

## 付録A: テストベクター

### テストケース1: 基本的なPOSTリクエスト

**入力:**
- タイムスタンプ: `1733500800`
- メソッド: `POST`
- パス: `/api/v1/orders`
- ボディ: `{"order_id":123}`
- シークレット: `test_secret_key_32_characters_xx`

**期待値:**
- ボディハッシュ: `bf718b6f653bebc184e1479f1935b8da974d701b893afcf49e701f3e2f9f9c5a`
- 署名文字列:
  ```
  1733500800
  POST
  /api/v1/orders
  bf718b6f653bebc184e1479f1935b8da974d701b893afcf49e701f3e2f9f9c5a
  ```
- 署名: `v1=...`（実装で算出してください）

### テストケース2: GETリクエスト（ボディなし）

**入力:**
- タイムスタンプ: `1733500800`
- メソッド: `GET`
- パス: `/api/v1/users`
- ボディ:（空）
- シークレット: `test_secret_key_32_characters_xx`

**期待値:**
- ボディハッシュ: `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`

## 付録B: 言語別実装例

### JavaScript (Node.js)

```javascript
const crypto = require('crypto');

function signRequest(method, path, body, secret, timestamp = null) {
    timestamp = timestamp || Math.floor(Date.now() / 1000);
    const bodyHash = crypto.createHash('sha256').update(body || '').digest('hex');

    const signingString = [
        timestamp,
        method.toUpperCase(),
        path,
        bodyHash
    ].join('\n');

    const signature = crypto.createHmac('sha256', secret)
        .update(signingString)
        .digest('hex');

    return {
        'X-Dixlase-Timestamp': timestamp.toString(),
        'X-Dixlase-Signature': `v1=${signature}`
    };
}
```

### Python

```python
import hmac
import hashlib
import time

def sign_request(method, path, body, secret, timestamp=None):
    timestamp = timestamp or int(time.time())
    body_hash = hashlib.sha256((body or '').encode()).hexdigest()

    signing_string = '\n'.join([
        str(timestamp),
        method.upper(),
        path,
        body_hash
    ])

    signature = hmac.new(
        secret.encode(),
        signing_string.encode(),
        hashlib.sha256
    ).hexdigest()

    return {
        'X-Dixlase-Timestamp': str(timestamp),
        'X-Dixlase-Signature': f'v1={signature}'
    }
```

---

**ドキュメントバージョン**: 1.0.0
**最終更新日**: 2025-01-01
**著者**: Dixlase Development Team
