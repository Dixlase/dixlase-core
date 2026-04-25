<?php

namespace Tests\Unit\Services\Plugin;

use App\Services\Plugin\PluginManifestSyncService;
use App\Services\Plugin\Scanning\PatternRegistry;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PluginManifestSyncServiceTest extends TestCase
{
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/dixlase-sync-test-'.uniqid();
        File::makeDirectory($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }
        parent::tearDown();
    }

    /** @test */
    public function diff_returns_no_changes_when_manifest_matches_implementation(): void
    {
        $this->writeManifest([
            'slug' => 'test-plugin',
            'declares' => [
                'configs' => ['roles' => false, 'database_cleanup' => false, 'navigation' => false],
                'contracts' => [],
                'migrations' => false,
                'commands' => false,
                'middleware' => false,
            ],
            'permissions' => $this->emptyPermissionShape(),
        ]);

        $service = $this->makeService();
        $result = $service->diff($this->tempDir, 'plugin');

        $this->assertFalse($result['changed']);
    }

    /** @test */
    public function diff_detects_added_config_file(): void
    {
        $this->writeManifest([
            'slug' => 'test-plugin',
            'declares' => [
                'configs' => ['roles' => false, 'database_cleanup' => false, 'navigation' => false],
            ],
            'permissions' => $this->emptyPermissionShape(),
        ]);
        File::makeDirectory("{$this->tempDir}/config/admin", 0755, true);
        File::put("{$this->tempDir}/config/admin/navigation.php", '<?php return [];');

        $service = $this->makeService();
        $result = $service->diff($this->tempDir, 'plugin');

        $this->assertTrue($result['changed']);
        $changedPaths = array_column($result['changes'], 'path');
        $this->assertContains('declares.configs.navigation', $changedPaths);
    }

    /** @test */
    public function diff_detects_added_command_file(): void
    {
        $this->writeManifest([
            'slug' => 'test-plugin',
            'declares' => ['commands' => false],
            'permissions' => $this->emptyPermissionShape(),
        ]);
        File::makeDirectory("{$this->tempDir}/app/Console/Commands", 0755, true);
        File::put("{$this->tempDir}/app/Console/Commands/MyCommand.php", '<?php');

        $service = $this->makeService();
        $result = $service->diff($this->tempDir, 'plugin');

        $this->assertTrue($result['changed']);
        $paths = array_column($result['changes'], 'path');
        $this->assertContains('declares.commands', $paths);
    }

    /** @test */
    public function sync_writes_to_manifest_when_changes_detected(): void
    {
        $this->writeManifest([
            'slug' => 'test-plugin',
            'declares' => ['migrations' => false],
            'permissions' => $this->emptyPermissionShape(),
        ]);
        File::makeDirectory("{$this->tempDir}/database/migrations", 0755, true);
        File::put("{$this->tempDir}/database/migrations/2024_01_01_create.php", '<?php');

        $service = $this->makeService();
        $service->sync($this->tempDir, 'plugin');

        $written = json_decode(File::get("{$this->tempDir}/plugin.json"), true);
        $this->assertTrue($written['declares']['migrations']);
    }

    /** @test */
    public function sync_preserves_manual_optional_and_notes_fields(): void
    {
        $this->writeManifest([
            'slug' => 'test-plugin',
            'declares' => ['migrations' => false],
            'permissions' => array_merge($this->emptyPermissionShape(), [
                '_optional' => ['mail.send'],
                '_notes' => ['ja' => '手動メモ', 'en' => 'manual note'],
            ]),
        ]);
        File::makeDirectory("{$this->tempDir}/database/migrations", 0755, true);
        File::put("{$this->tempDir}/database/migrations/2024_01_01_create.php", '<?php');

        $service = $this->makeService();
        $service->sync($this->tempDir, 'plugin');

        $written = json_decode(File::get("{$this->tempDir}/plugin.json"), true);
        $this->assertSame(['mail.send'], $written['permissions']['_optional']);
        $this->assertSame('手動メモ', $written['permissions']['_notes']['ja']);
    }

    /** @test */
    public function sync_preserves_content_other_plugin_lists(): void
    {
        $this->writeManifest([
            'slug' => 'test-plugin',
            'declares' => [],
            'permissions' => array_merge($this->emptyPermissionShape(), [
                'content' => [
                    'read_other_plugins' => ['dixlase-seo'],
                    'write_other_plugins' => [],
                ],
            ]),
        ]);

        $service = $this->makeService();
        $service->sync($this->tempDir, 'plugin');

        $written = json_decode(File::get("{$this->tempDir}/plugin.json"), true);
        $this->assertSame(['dixlase-seo'], $written['permissions']['content']['read_other_plugins']);
    }

    protected function makeService(): PluginManifestSyncService
    {
        return new PluginManifestSyncService(PatternRegistry::createDefault());
    }

    protected function writeManifest(array $data): void
    {
        File::put("{$this->tempDir}/plugin.json", json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyPermissionShape(): array
    {
        return [
            'database' => ['own_tables' => false, 'core_tables' => []],
            'storage' => ['own_directory' => false, 'public_uploads' => false, 'temp_files' => false],
            'settings' => ['read_core' => false, 'write_own' => false],
            'members' => ['read' => false, 'write' => false, 'create' => false, 'delete' => false],
            'mail' => ['send' => false, 'bulk_send' => false],
            'content' => ['read_other_plugins' => [], 'write_other_plugins' => []],
            'system' => ['register_shortcodes' => false, 'register_middleware' => false, 'register_commands' => false, 'register_blade_directives' => false, 'modify_routes' => false],
            '_optional' => [],
            '_notes' => ['ja' => '', 'en' => ''],
        ];
    }
}
