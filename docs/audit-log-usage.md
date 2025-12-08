# 監査ログ（Audit Log）使用ガイド

## 概要

Dixlaseの監査ログシステムは、「誰が / いつ / どこから / 何に対して / 何をしたか」を記録するための統一APIを提供します。

## 基本的な使い方

### Audit Facadeを使用

```php
use App\Facades\Audit;
use App\Models\AuditLog;

// 基本的なログ記録
Audit::log([
    'category' => AuditLog::CATEGORY_AUTH,
    'action' => AuditLog::ACTION_LOGIN,
    'outcome' => AuditLog::OUTCOME_SUCCESS,
    'actor' => $user,
    'context' => [
        'message' => 'ログインしました',
    ],
]);
```

### ショートカットメソッド

```php
// 認証ログ
Audit::logAuth(AuditLog::ACTION_LOGIN, [
    'actor' => $user,
    'outcome' => AuditLog::OUTCOME_SUCCESS,
]);

// セキュリティログ
Audit::logSecurity(AuditLog::ACTION_IP_BLOCKED, [
    'severity' => AuditLog::SEVERITY_WARNING,
    'context' => ['blocked_ip' => '192.168.1.1'],
]);

// アカウントログ
Audit::logAccount(AuditLog::ACTION_PASSWORD_CHANGED, [
    'actor' => $user,
    'target' => $user,
]);

// 拡張機能ログ
Audit::logExtension(AuditLog::ACTION_PLUGIN_INSTALLED, [
    'actor' => $admin,
    'target_label' => 'DixlaseBlog',
]);

// システムログ
Audit::logSystem(AuditLog::ACTION_SETTINGS_UPDATED, [
    'actor' => $admin,
    'target_label' => 'security_settings',
]);

// コンテンツログ
Audit::logContent('post_published', [
    'actor' => $author,
    'target' => $post,
]);

// プラグインログ
Audit::logPlugin('custom_action', [
    'plugin_name' => 'my-plugin',
    'context' => ['custom_data' => 'value'],
]);
```

## プラグインからの使用

### 基本的な使い方

```php
use App\Facades\Audit;
use App\Models\AuditLog;

class MyPluginController extends Controller
{
    public function store(Request $request)
    {
        $inquiry = Inquiry::create($request->validated());
        
        // プラグインからのログ記録
        Audit::log([
            'category' => AuditLog::CATEGORY_PLUGIN,
            'action' => 'inquiry_submitted',
            'plugin_name' => 'dixlase-inquiry',
            'plugin_version' => '1.0.0',
            'target' => $inquiry,
            'context' => [
                'message' => 'お問い合わせが送信されました',
                'form_id' => $request->form_id,
            ],
        ]);
        
        return redirect()->back();
    }
}
```

### プラグインコンテキストの設定

ServiceProviderでプラグインコンテキストを設定すると、以降のログに自動的にプラグイン情報が付与されます。

```php
use App\Facades\Audit;

class MyPluginServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // プラグインコンテキストを設定
        Audit::setPluginContext('my-plugin', '1.0.0');
    }
}
```

## AuditableTrait の使用

モデルの作成・更新・削除を自動的に監査ログに記録できます。

### 基本的な使い方

```php
use App\Traits\AuditableTrait;

class Post extends Model
{
    use AuditableTrait;
}
```

### カスタマイズ

```php
class Post extends Model
{
    use AuditableTrait;
    
    // 監査対象外のカラム
    protected array $auditExclude = ['updated_at', 'view_count'];
    
    // 監査対象のカラム（指定した場合、これらのみ記録）
    protected array $auditInclude = ['title', 'status', 'content'];
    
    // カテゴリを指定
    protected string $auditCategory = 'content';
    
    // プラグイン名を指定
    protected ?string $auditPluginName = 'dixlase-blog';
    
    // カスタムアクション名
    protected array $auditActions = [
        'created' => 'post_created',
        'updated' => 'post_updated',
        'deleted' => 'post_deleted',
    ];
    
    // カスタムメッセージ
    protected array $auditMessages = [
        'created' => '記事が作成されました',
        'updated' => '記事が更新されました',
        'deleted' => '記事が削除されました',
    ];
    
    // ラベルに使用する属性
    protected string $auditLabelAttribute = 'title';
    
    // イベントごとの重要度
    protected array $auditSeverities = [
        'created' => 'info',
        'updated' => 'info',
        'deleted' => 'warning',
    ];
}
```

### 一時的に監査を無効化

```php
$post->withoutAudit(function ($post) {
    $post->view_count++;
    $post->save();
});
```

## 設定変更のログ

```php
use App\Facades\Audit;

// 設定変更をログ
Audit::logSettingsChange(
    'site_name',           // 設定キー
    'Old Site Name',       // 変更前の値
    'New Site Name',       // 変更後の値
    auth()->user(),        // 行為者
    'my-plugin'            // プラグイン名（オプション）
);
```

## モデル変更のログ

```php
use App\Facades\Audit;
use App\Models\AuditLog;

$user->email = 'new@example.com';
$user->save();

// モデルの変更をログ
Audit::logModelChange(
    $user,                              // 対象モデル
    AuditLog::ACTION_EMAIL_CHANGED,     // アクション
    auth()->user(),                     // 行為者
    ['reason' => 'ユーザーからの依頼']   // 追加コンテキスト
);
```

## ログの検索・取得

```php
use App\Facades\Audit;
use App\Models\AuditLog;

// 行為者のログを取得
$logs = Audit::getLogsForActor($user, 50);

// 対象のログを取得
$logs = Audit::getLogsForTarget($post, 50);

// リクエストIDで関連ログを取得
$logs = Audit::getLogsForRequest($requestId);

// 最近の警告以上のログ
$warnings = Audit::getRecentWarnings(24, 100);

// 最近の失敗ログ
$failures = Audit::getRecentFailures(24, 100);

// Eloquentクエリ
$logs = AuditLog::inCategory(AuditLog::CATEGORY_AUTH)
    ->withAction(AuditLog::ACTION_LOGIN)
    ->recent(24)
    ->orderByDesc('occurred_at')
    ->get();
```

## 定数一覧

### Severity（重要度）

| 定数 | 値 | 説明 |
|------|-----|------|
| `SEVERITY_DEBUG` | debug | デバッグ |
| `SEVERITY_INFO` | info | 情報 |
| `SEVERITY_NOTICE` | notice | 通知 |
| `SEVERITY_WARNING` | warning | 警告 |
| `SEVERITY_ERROR` | error | エラー |
| `SEVERITY_CRITICAL` | critical | 重大 |
| `SEVERITY_ALERT` | alert | アラート |
| `SEVERITY_EMERGENCY` | emergency | 緊急 |

### Outcome（結果）

| 定数 | 値 | 説明 |
|------|-----|------|
| `OUTCOME_SUCCESS` | success | 成功 |
| `OUTCOME_FAILURE` | failure | 失敗 |
| `OUTCOME_DENIED` | denied | 拒否 |
| `OUTCOME_PENDING` | pending | 保留 |
| `OUTCOME_UNKNOWN` | unknown | 不明 |

### Category（カテゴリ）

| 定数 | 値 | 説明 |
|------|-----|------|
| `CATEGORY_AUTH` | auth | 認証 |
| `CATEGORY_ACCOUNT` | account | アカウント |
| `CATEGORY_DEVICE` | device | デバイス |
| `CATEGORY_SECURITY` | security | セキュリティ |
| `CATEGORY_SESSION` | session | セッション |
| `CATEGORY_EXTENSION` | extension | 拡張機能 |
| `CATEGORY_CONTENT` | content | コンテンツ |
| `CATEGORY_SYSTEM` | system | システム |
| `CATEGORY_PLUGIN` | plugin | プラグイン |

## コアで自動記録されるイベント

以下のイベントはコアで自動的に監査ログに記録されます：

- **認証関連**
  - ログイン成功/失敗
  - ログアウト
  - ロックアウト
  - パスワードリセット
  - 他デバイスからのログアウト

- **アカウント関連**
  - ユーザー登録
  - メール認証完了

## context（JSON）の構造

```json
{
  "message": "説明テキスト",
  "before": {
    "email": "old@example.com"
  },
  "after": {
    "email": "new@example.com"
  },
  "diff": {
    "email": {
      "from": "old@example.com",
      "to": "new@example.com"
    }
  },
  "meta": {
    "http_method": "POST",
    "url": "/admin/members/123",
    "reason": "ユーザーからの依頼",
    "extra": {}
  }
}
```

## ベストプラクティス

1. **適切なカテゴリとアクションを使用**: 定義済みの定数を使用し、一貫性を保つ
2. **機密情報を記録しない**: パスワード、トークン等は記録しない
3. **contextを活用**: 変更前後の値、差分、追加情報はcontextに格納
4. **プラグイン名を明示**: プラグインからのログは必ずplugin_nameを設定
5. **適切な重要度を設定**: 削除や重要な変更はwarning以上に設定
