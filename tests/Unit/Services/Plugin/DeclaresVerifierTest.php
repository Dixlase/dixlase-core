<?php

namespace Tests\Unit\Services\Plugin;

use App\Services\Plugin\DeclaresVerifier;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DeclaresVerifierTest extends TestCase
{
    protected DeclaresVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifier = new DeclaresVerifier();
    }

    /**
     * 存在しないプラグインは空の結果を返すテスト
     */
    public function test_verify_returns_empty_for_nonexistent_plugin(): void
    {
        $result = $this->verifier->verify('nonexistent-plugin');

        $this->assertTrue($result->isClean());
        $this->assertEquals(0, $result->declaredCount);
    }

    /**
     * declares と実際のファイルが一致する場合は問題なしのテスト
     */
    public function test_verify_clean_when_declares_match_files(): void
    {
        $pluginDir = $this->createTempPlugin(
            declares: [
                'configs' => ['roles' => false, 'database_cleanup' => false, 'navigation' => true],
                'contracts' => [],
                'migrations' => true,
                'commands' => false,
                'middleware' => false,
            ],
            files: ['config/admin/navigation.php', 'database/migrations/2025_01_01_test.php']
        );

        try {
            $result = $this->verifier->verify('test-plugin');

            $this->assertTrue($result->isClean());
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    /**
     * 宣言あり + ファイルなしを検出するテスト
     */
    public function test_verify_detects_declared_but_missing(): void
    {
        $pluginDir = $this->createTempPlugin(
            declares: [
                'configs' => ['roles' => true, 'database_cleanup' => false, 'navigation' => false],
                'contracts' => [],
                'migrations' => false,
                'commands' => false,
                'middleware' => false,
            ],
            files: []
        );

        try {
            $result = $this->verifier->verify('test-plugin');

            $this->assertFalse($result->isClean());
            $this->assertEquals(1, $result->issueCount());
            $this->assertEquals('declared_but_missing', $result->issues[0]['type']);
            $this->assertStringContainsString('configs.roles', $result->issues[0]['key']);
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    /**
     * ファイルあり + 宣言なしを検出するテスト
     */
    public function test_verify_detects_exists_but_undeclared(): void
    {
        $pluginDir = $this->createTempPlugin(
            declares: [
                'configs' => ['roles' => false, 'database_cleanup' => false, 'navigation' => false],
                'contracts' => [],
                'migrations' => false,
                'commands' => false,
                'middleware' => false,
            ],
            files: ['config/admin/navigation.php']
        );

        try {
            $result = $this->verifier->verify('test-plugin');

            $this->assertFalse($result->isClean());
            $this->assertEquals('exists_but_undeclared', $result->issues[0]['type']);
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    /**
     * migrations の検証テスト
     */
    public function test_verify_migrations_declared_but_missing(): void
    {
        $pluginDir = $this->createTempPlugin(
            declares: [
                'configs' => ['roles' => false, 'database_cleanup' => false, 'navigation' => false],
                'contracts' => [],
                'migrations' => true,
                'commands' => false,
                'middleware' => false,
            ],
            files: []
        );

        try {
            $result = $this->verifier->verify('test-plugin');

            $this->assertFalse($result->isClean());
            $migrationIssue = collect($result->issues)->firstWhere('key', 'migrations');
            $this->assertNotNull($migrationIssue);
            $this->assertEquals('declared_but_missing', $migrationIssue['type']);
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    /**
     * commands の検証テスト
     */
    public function test_verify_commands_exists_but_undeclared(): void
    {
        $pluginDir = $this->createTempPlugin(
            declares: [
                'configs' => ['roles' => false, 'database_cleanup' => false, 'navigation' => false],
                'contracts' => [],
                'migrations' => false,
                'commands' => false,
                'middleware' => false,
            ],
            files: ['app/Console/Commands/TestCommand.php']
        );

        try {
            $result = $this->verifier->verify('test-plugin');

            $commandIssue = collect($result->issues)->firstWhere('key', 'commands');
            $this->assertNotNull($commandIssue);
            $this->assertEquals('exists_but_undeclared', $commandIssue['type']);
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    /**
     * declaredCount と actualCount が正しいテスト
     */
    public function test_counts_are_correct(): void
    {
        $pluginDir = $this->createTempPlugin(
            declares: [
                'configs' => ['roles' => true, 'database_cleanup' => false, 'navigation' => true],
                'contracts' => [],
                'migrations' => true,
                'commands' => false,
                'middleware' => false,
            ],
            files: [
                'config/admin/roles.php',
                'config/admin/navigation.php',
                'database/migrations/2025_01_01_test.php',
            ]
        );

        try {
            $result = $this->verifier->verify('test-plugin');

            $this->assertEquals(3, $result->declaredCount);
            $this->assertEquals(3, $result->actualCount);
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    /**
     * テスト用プラグインディレクトリを作成
     *
     * @param  array  $declares  declares セクション
     * @param  array<string>  $files  作成するファイルの相対パス
     */
    protected function createTempPlugin(array $declares, array $files): string
    {
        $pluginDir = base_path('plugins/TestPlugin');

        if (File::isDirectory($pluginDir)) {
            File::deleteDirectory($pluginDir);
        }

        File::makeDirectory($pluginDir, 0755, true);

        // plugin.json を作成
        $pluginJson = [
            'name' => 'TestPlugin',
            'slug' => 'test-plugin',
            'version' => '1.0.0',
            'declares' => $declares,
        ];

        File::put(
            "{$pluginDir}/plugin.json",
            json_encode($pluginJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        // 指定されたファイルを作成
        foreach ($files as $file) {
            $filePath = "{$pluginDir}/{$file}";
            $dir = dirname($filePath);
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            File::put($filePath, "<?php\n// test file\n");
        }

        return $pluginDir;
    }
}
