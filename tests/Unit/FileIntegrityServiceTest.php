<?php

namespace Tests\Unit;

use App\Models\FileIntegrityAudit;
use App\Services\FileIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FileIntegrityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FileIntegrityService $service;

    protected string $testBaselinePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FileIntegrityService();
        $this->testBaselinePath = storage_path('app/dixlase/security');

        // テスト用ディレクトリをクリーンアップ
        if (File::isDirectory($this->testBaselinePath)) {
            File::deleteDirectory($this->testBaselinePath);
        }
    }

    protected function tearDown(): void
    {
        // テスト後のクリーンアップ
        if (File::isDirectory($this->testBaselinePath)) {
            File::deleteDirectory($this->testBaselinePath);
        }

        parent::tearDown();
    }

    /**
     * ベースライン生成のテスト
     */
    public function test_generate_core_baseline_returns_valid_structure(): void
    {
        $baseline = $this->service->generateCoreBaseline();

        $this->assertIsArray($baseline);
        $this->assertArrayHasKey('meta', $baseline);
        $this->assertArrayHasKey('files', $baseline);

        // メタ情報の検証
        $this->assertArrayHasKey('generated_at', $baseline['meta']);
        $this->assertArrayHasKey('hash_algo', $baseline['meta']);
        $this->assertArrayHasKey('paths', $baseline['meta']);
        $this->assertArrayHasKey('ignore_patterns', $baseline['meta']);

        // ファイルハッシュの検証
        $this->assertIsArray($baseline['files']);
        $this->assertNotEmpty($baseline['files']);

        // 必須ファイルが含まれているか
        $this->assertArrayHasKey('artisan', $baseline['files']);
        $this->assertArrayHasKey('composer.json', $baseline['files']);
    }

    /**
     * ベースライン保存のテスト
     */
    public function test_save_baseline_creates_file(): void
    {
        $baseline = $this->service->generateCoreBaseline();
        $result = $this->service->saveBaselineArray($baseline);

        $this->assertTrue($result);
        $this->assertTrue(File::exists($this->testBaselinePath.'/core_hashes.json'));
    }

    /**
     * ベースライン読み込みのテスト
     */
    public function test_load_baseline_returns_saved_data(): void
    {
        $baseline = $this->service->generateCoreBaseline();
        $this->service->saveBaselineArray($baseline);

        // loadBaselineArray() で配列形式で読み込み検証
        $loaded = $this->service->loadBaselineArray();

        $this->assertIsArray($loaded);
        $this->assertEquals($baseline['meta']['hash_algo'], $loaded['meta']['hash_algo']);
        $this->assertEquals(count($baseline['files']), count($loaded['files']));
    }

    /**
     * 存在しないベースラインの読み込み
     */
    public function test_load_baseline_returns_null_when_not_exists(): void
    {
        $loaded = $this->service->loadBaseline('nonexistent.json');

        $this->assertNull($loaded);
    }

    /**
     * ベースライン存在確認のテスト
     */
    public function test_has_baseline_returns_correct_status(): void
    {
        $this->assertFalse($this->service->hasBaseline());

        $baseline = $this->service->generateCoreBaseline();
        $this->service->saveBaselineArray($baseline);

        $this->assertTrue($this->service->hasBaseline());
    }

    /**
     * ベースラインメタ情報取得のテスト
     */
    public function test_get_baseline_meta_returns_meta_with_file_count(): void
    {
        $baseline = $this->service->generateCoreBaseline();
        $this->service->saveBaselineArray($baseline);

        $meta = $this->service->getBaselineMeta();

        $this->assertIsArray($meta);
        $this->assertArrayHasKey('generated_at', $meta);
        $this->assertArrayHasKey('hash_algo', $meta);
        $this->assertArrayHasKey('files_count', $meta);
        $this->assertEquals(count($baseline['files']), $meta['files_count']);
    }

    /**
     * スキャン実行のテスト（ベースラインなし）
     */
    public function test_scan_core_creates_baseline_when_not_exists(): void
    {
        $audit = $this->service->scanCore();

        $this->assertInstanceOf(FileIntegrityAudit::class, $audit);
        $this->assertEquals(FileIntegrityAudit::STATUS_OK, $audit->status);
        $this->assertTrue($this->service->hasBaseline());
    }

    /**
     * スキャン実行のテスト（変更なし）
     */
    public function test_scan_core_returns_ok_when_no_changes(): void
    {
        // ベースラインを作成
        $baseline = $this->service->generateCoreBaseline();
        $this->service->saveBaselineArray($baseline);

        // スキャン実行
        $audit = $this->service->scanCore();

        $this->assertInstanceOf(FileIntegrityAudit::class, $audit);
        $this->assertEquals(FileIntegrityAudit::STATUS_OK, $audit->status);
        $this->assertEquals(0, $audit->changed_files_count);
        $this->assertEquals(0, $audit->added_files_count);
        $this->assertEquals(0, $audit->removed_files_count);
    }

    /**
     * 監査レコードがDBに保存されることのテスト
     */
    public function test_scan_core_saves_audit_record(): void
    {
        $audit = $this->service->scanCore(
            FileIntegrityAudit::TRIGGER_MANUAL,
            FileIntegrityAudit::INITIATED_BY_SYSTEM,
        );

        $this->assertDatabaseHas('file_integrity_audits', [
            'id' => $audit->id,
            'scope' => FileIntegrityAudit::SCOPE_CORE,
            'trigger' => FileIntegrityAudit::TRIGGER_MANUAL,
            'initiated_by_type' => FileIntegrityAudit::INITIATED_BY_SYSTEM,
        ]);
    }

    /**
     * ベースライン再生成のテスト
     */
    public function test_regenerate_baseline_updates_baseline(): void
    {
        // 初回ベースライン作成
        $baseline1 = $this->service->generateCoreBaseline();
        $this->service->saveBaselineArray($baseline1);

        $meta1 = $this->service->getBaselineMeta();

        // 少し待ってから再生成
        sleep(1);
        $result = $this->service->regenerateBaselineCore();

        $this->assertTrue($result);

        $meta2 = $this->service->getBaselineMeta();
        $this->assertNotEquals($meta1['generated_at'], $meta2['generated_at']);
    }

    /**
     * SHA256ハッシュが使用されていることのテスト
     */
    public function test_baseline_uses_sha256_hash(): void
    {
        $baseline = $this->service->generateCoreBaseline();

        $this->assertEquals('sha256', $baseline['meta']['hash_algo']);

        // ハッシュ値の長さを確認（SHA256は64文字）
        foreach ($baseline['files'] as $hash) {
            $this->assertEquals(64, strlen($hash));
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
        }
    }

    /**
     * 除外パターンが正しく機能することのテスト
     */
    public function test_ignore_patterns_exclude_correct_paths(): void
    {
        $baseline = $this->service->generateCoreBaseline();

        // 除外されるべきパスが含まれていないことを確認
        // resources/views/vendor/ 等はコアパスに含まれるため、先頭一致で判定
        foreach ($baseline['files'] as $path => $hash) {
            $this->assertDoesNotMatchRegularExpression('/^vendor\//', $path);
            $this->assertDoesNotMatchRegularExpression('/^node_modules\//', $path);
            $this->assertDoesNotMatchRegularExpression('/^storage\//', $path);
            $this->assertDoesNotMatchRegularExpression('/^\.git\//', $path);
            $this->assertDoesNotMatchRegularExpression('/^bootstrap\/cache\//', $path);
        }
    }

    /**
     * コアパスが正しくスキャンされることのテスト
     */
    public function test_core_paths_are_scanned(): void
    {
        $baseline = $this->service->generateCoreBaseline();

        // 必須のコアファイルが含まれていることを確認
        $requiredFiles = [
            'artisan',
            'composer.json',
            'composer.lock',
            'public/index.php',
        ];

        foreach ($requiredFiles as $file) {
            $this->assertArrayHasKey($file, $baseline['files'], "Required file {$file} should be in baseline");
        }

        // appディレクトリのファイルが含まれていることを確認
        $hasAppFiles = false;
        foreach ($baseline['files'] as $path => $hash) {
            if (str_starts_with($path, 'app/')) {
                $hasAppFiles = true;
                break;
            }
        }
        $this->assertTrue($hasAppFiles, 'App directory files should be in baseline');
    }

    /**
     * 監査レコードのヘルパーメソッドのテスト
     */
    public function test_audit_helper_methods(): void
    {
        $audit = $this->service->scanCore();

        // STATUS_OKの場合
        $this->assertFalse($audit->hasIssues());
        $this->assertFalse($audit->isCritical());
        $this->assertIsArray($audit->getChangedFiles());
        $this->assertIsArray($audit->getAddedFiles());
        $this->assertIsArray($audit->getRemovedFiles());
        $this->assertIsArray($audit->getSuspiciousFiles());
    }

    /**
     * スキャン時間が記録されることのテスト
     */
    public function test_scan_records_duration(): void
    {
        $audit = $this->service->scanCore();

        $this->assertNotNull($audit->started_at);
        $this->assertNotNull($audit->finished_at);
        $this->assertNotNull($audit->duration_ms);
        $this->assertGreaterThanOrEqual(0, $audit->duration_ms);
    }
}
