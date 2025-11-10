# リポジトリパターン使用ガイド

このドキュメントでは、Dixlaseで実装されているリポジトリパターンの使用方法について説明します。

## 概要

リポジトリパターンは、データアクセスロジックをビジネスロジックから分離するためのデザインパターンです。このパターンを使用することで、以下のメリットが得られます：

- **テスタビリティの向上**: モックやスタブを使用したテストが容易になります
- **保守性の向上**: データアクセスロジックが一箇所に集約されます
- **柔軟性の向上**: データソースの変更が容易になります
- **依存性の逆転**: 上位レイヤーが下位レイヤーに依存しなくなります

## アーキテクチャ

### ベースインターフェース

すべての設定系リポジトリは `SettingRepositoryInterface` を継承しています。これにより、共通メソッドの重複を排除し、一貫性のあるAPIを提供します。

```php
SettingRepositoryInterface (ベース)
├── MemberSettingRepositoryInterface
├── BaseSettingRepositoryInterface
├── SecuritySettingRepositoryInterface
├── MediaSettingRepositoryInterface
└── FrontSettingRepositoryInterface
```

**共通メソッド:**
- `all()` - すべての設定を取得
- `get()` - 特定の設定を取得
- `getMultiple()` - 複数の設定を一括取得
- `set()` - 設定を保存
- `setMultiple()` - 複数の設定を一括保存
- `has()` - 設定の存在確認
- `delete()` - 設定を削除
- `clearCache()` - キャッシュをクリア
- `clearAllCache()` - すべてのキャッシュをクリア

## 実装済みリポジトリ

### MemberSettingRepository

メンバー設定のデータアクセスを管理するリポジトリです。

**インターフェース:** `App\Contracts\Repositories\MemberSettingRepositoryInterface`  
**実装クラス:** `App\Repositories\MemberSettingRepository`

### BaseSettingRepository

基本設定（サイト名、メール設定、OGP設定など）のデータアクセスを管理するリポジトリです。

**インターフェース:** `App\Contracts\Repositories\BaseSettingRepositoryInterface`  
**実装クラス:** `App\Repositories\BaseSettingRepository`

**特徴:**
- JSON値の自動エンコード/デコード
- OGP画像リレーションのサポート
- メール設定テスト状態の管理

### SecuritySettingRepository

セキュリティ設定（IP制限、CAPTCHA、通知設定など）のデータアクセスを管理するリポジトリです。

**インターフェース:** `App\Contracts\Repositories\SecuritySettingRepositoryInterface`  
**実装クラス:** `App\Repositories\SecuritySettingRepository`

**特徴:**
- Boolean値の自動変換（true→'1', false→'0'）
- CAPTCHA設定の管理
- IP制限設定の管理
- 通知設定の管理

### MediaSettingRepository

メディア設定（ファイルタイプ、サイズ制限など）のデータアクセスを管理するリポジトリです。

**インターフェース:** `App\Contracts\Repositories\MediaSettingRepositoryInterface`  
**実装クラス:** `App\Repositories\MediaSettingRepository`

**特徴:**
- JSON値の自動エンコード/デコード
- ファイルタイプ制限の管理
- ファイルサイズ制限の管理

### FrontSettingRepository

フロント設定（OGP画像、説明文など）のデータアクセスを管理するリポジトリです。

**インターフェース:** `App\Contracts\Repositories\FrontSettingRepositoryInterface`  
**実装クラス:** `App\Repositories\FrontSettingRepository`

**特徴:**
- フロントOGP画像リレーションのサポート
- フロントページ設定の管理
- キャッシュ管理

### MediaRepository

メディアマスター（画像、動画、ドキュメントなど）のデータアクセスを管理するリポジトリです。

**インターフェース:** `App\Contracts\Repositories\MediaRepositoryInterface`  
**実装クラス:** `App\Repositories\MediaRepository`

**特徴:**
- CRUD操作（作成、読取、更新、削除）
- ページネーション付き一覧取得
- 検索・フィルタリング（名前、タイプ、アップロード者、日付範囲）
- ソフトデリート対応
- メンバーリレーションのサポート

## 基本的な使用方法

### 1. コンストラクタインジェクション（推奨）

```php
use App\Contracts\Repositories\MemberSettingRepositoryInterface;

class YourController extends Controller
{
    protected MemberSettingRepositoryInterface $memberSettingRepository;

    public function __construct(MemberSettingRepositoryInterface $memberSettingRepository)
    {
        $this->memberSettingRepository = $memberSettingRepository;
    }

    public function index()
    {
        // 設定値を取得
        $value = $this->memberSettingRepository->get('password_min_length', 8);
        
        // 設定値を保存
        $this->memberSettingRepository->set('password_min_length', 12);
        
        // すべての設定を取得
        $allSettings = $this->memberSettingRepository->all();
    }
}
```

### 2. app()ヘルパー使用

```php
$repository = app(MemberSettingRepositoryInterface::class);
$value = $repository->get('some_key', 'default_value');
```

### 3. 後方互換性（非推奨）

既存コードとの互換性のため、Modelの静的メソッドは残されていますが、内部でRepositoryを使用しています。

```php
// 非推奨: 将来的に削除される可能性があります
$value = MemberSetting::getValue('some_key', 'default');

// 推奨: Repositoryを直接使用
$value = $this->memberSettingRepository->get('some_key', 'default');
```

## 利用可能なメソッド

### all()

すべての設定を配列で取得します。

```php
$settings = $this->memberSettingRepository->all();
// ['password_min_length' => '8', 'force_2fa' => '0', ...]
```

### get(string $key, mixed $default = null)

特定のキーの値を取得します。

```php
$minLength = $this->memberSettingRepository->get('password_min_length', 8);
```

### getMultiple(array $keys, mixed $default = null)

複数のキーの値を一括取得します。

```php
$settings = $this->memberSettingRepository->getMultiple([
    'password_min_length',
    'password_require_uppercase',
    'password_require_number'
], false);
```

### set(string $key, mixed $value)

設定値を保存します。

```php
$record = $this->memberSettingRepository->set('password_min_length', 12);
```

### setMultiple(array $settings)

複数の設定値を一括保存します。

```php
$success = $this->memberSettingRepository->setMultiple([
    'password_min_length' => 12,
    'password_require_uppercase' => true,
    'password_require_number' => true
]);
```

### has(string $key)

設定が存在するか確認します。

```php
if ($this->memberSettingRepository->has('custom_setting')) {
    // 設定が存在する
}
```

### delete(string $key)

設定を削除します。

```php
$this->memberSettingRepository->delete('old_setting');
```

### clearCache(?string $key = null)

キャッシュをクリアします。

```php
// 特定のキーのキャッシュをクリア
$this->memberSettingRepository->clearCache('password_min_length');

// すべてのキャッシュをクリア
$this->memberSettingRepository->clearAllCache();
```

## キャッシュ戦略

- すべての取得操作は自動的にキャッシュされます（デフォルト: 10分）
- 保存・削除操作時は自動的にキャッシュがクリアされます
- キャッシュキー: `member_setting:{key}` および `members_settings_all`

## テスト例

```php
use App\Contracts\Repositories\MemberSettingRepositoryInterface;
use Tests\TestCase;

class MemberSettingTest extends TestCase
{
    public function test_can_get_setting()
    {
        $repository = app(MemberSettingRepositoryInterface::class);
        
        $repository->set('test_key', 'test_value');
        $value = $repository->get('test_key');
        
        $this->assertEquals('test_value', $value);
    }
}
```

## 今後の拡張予定

以下のリポジトリを順次実装予定です：

- `MemberRepository` - メンバー管理（認証、権限、2FA）
- `ThemeRepository` - テーマ管理
- `PluginRepository` - プラグイン管理

## ベストプラクティス

1. **常にインターフェースに依存する**: 実装クラスではなく、インターフェースを使用してください
2. **コンストラクタインジェクションを使用**: DIコンテナの恩恵を最大限に活用できます
3. **静的メソッドは避ける**: `MemberSetting::getValue()`ではなく、Repositoryを使用してください
4. **一括操作を活用**: 複数の設定を扱う場合は`getMultiple()`や`setMultiple()`を使用してください

## 移行ガイド

既存コードをリポジトリパターンに移行する場合：

### Before（旧）
```php
// MemberSetting
$value = MemberSetting::getValue('password_min_length', 8);
MemberSetting::setValue('password_min_length', 12);

// BaseSetting
$siteName = BaseSetting::getValue('site_description', '');
BaseSetting::setValue('site_description', 'New description');
BaseSetting::setMany(['key1' => 'value1', 'key2' => 'value2']);

// SecuritySetting
$captchaEnabled = SecuritySetting::get('captcha_enabled', false);
SecuritySetting::set('captcha_enabled', true);

// MediaSetting
$allowedTypes = MediaSetting::where('name', 'allowed_file_types')->value('value');
$allowedTypes = json_decode($allowedTypes, true) ?? [];
MediaSetting::updateOrCreate(['name' => 'allowed_file_types'], ['value' => json_encode($types)]);

// FrontSetting
$ogpImageId = FrontSetting::getValue('front_ogp_image_id');
FrontSetting::setValue('front_description', 'New description');
```

### After（新）
```php
// コンストラクタで注入
public function __construct(
    MemberSettingRepositoryInterface $memberSettingRepository,
    BaseSettingRepositoryInterface $baseSettingRepository,
    SecuritySettingRepositoryInterface $securitySettingRepository,
    MediaSettingRepositoryInterface $mediaSettingRepository,
    FrontSettingRepositoryInterface $frontSettingRepository
) {
    $this->memberSettingRepository = $memberSettingRepository;
    $this->baseSettingRepository = $baseSettingRepository;
    $this->securitySettingRepository = $securitySettingRepository;
    $this->mediaSettingRepository = $mediaSettingRepository;
    $this->frontSettingRepository = $frontSettingRepository;
}

// メソッド内で使用
$value = $this->memberSettingRepository->get('password_min_length', 8);
$this->memberSettingRepository->set('password_min_length', 12);

$siteName = $this->baseSettingRepository->get('site_description', '');
$this->baseSettingRepository->set('site_description', 'New description');
$this->baseSettingRepository->setMultiple(['key1' => 'value1', 'key2' => 'value2']);

$captchaEnabled = $this->securitySettingRepository->get('captcha_enabled', false);
$this->securitySettingRepository->set('captcha_enabled', true);

// JSON自動処理
$allowedTypes = $this->mediaSettingRepository->get('allowed_file_types', []); // 自動デコード
$this->mediaSettingRepository->set('allowed_file_types', ['image', 'pdf']); // 自動エンコード

$ogpImageId = $this->frontSettingRepository->get('front_ogp_image_id');
$this->frontSettingRepository->set('front_description', 'New description');
```

## 参考

- メニュープラグインの実装: `plugins/DixlaseMenu/app/Repositories/`
- インターフェース定義: `app/Contracts/Repositories/`
- 実装クラス: `app/Repositories/`
- サービスプロバイダー: `app/Providers/RepositoryServiceProvider.php`
