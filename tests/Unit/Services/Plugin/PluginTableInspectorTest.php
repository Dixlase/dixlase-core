<?php

namespace Tests\Unit\Services\Plugin;

use App\Services\Plugin\PluginTableInspector;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PluginTableInspectorTest extends TestCase
{
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/dixlase-table-inspector-test-'.uniqid();
        File::makeDirectory($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }
        parent::tearDown();
    }

    #[Test]
    public function inspect_returns_empty_when_migrations_directory_is_missing(): void
    {
        $result = (new PluginTableInspector())->inspect($this->tempDir);

        $this->assertSame([], $result['tables']);
        $this->assertSame(0, $result['dynamic_count']);
        $this->assertFalse($result['has_migrations']);
    }

    #[Test]
    public function inspect_extracts_table_names_from_schema_create(): void
    {
        $this->makeMigration('001_create_pages.php', <<<'PHP'
<?php
return new class extends \Illuminate\Database\Migrations\Migration {
    public function up(): void
    {
        \Illuminate\Support\Facades\Schema::create('plg_test_pages', function ($table) {
            $table->id();
        });
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('plg_test_pages');
    }
};
PHP);

        $this->makeMigration('002_create_settings.php', <<<'PHP'
<?php
return new class extends \Illuminate\Database\Migrations\Migration {
    public function up(): void
    {
        Schema::create("plg_test_settings", function ($table) {
            $table->id();
        });
    }
};
PHP);

        $result = (new PluginTableInspector())->inspect($this->tempDir);

        $this->assertEqualsCanonicalizing(['plg_test_pages', 'plg_test_settings'], $result['tables']);
        $this->assertSame(0, $result['dynamic_count']);
        $this->assertTrue($result['has_migrations']);
    }

    #[Test]
    public function inspect_counts_dynamic_table_names_separately(): void
    {
        $this->makeMigration('001_dynamic.php', <<<'PHP'
<?php
$tableName = 'plg_test_dyn';
Schema::create($tableName, function ($table) {});
PHP);

        $result = (new PluginTableInspector())->inspect($this->tempDir);

        $this->assertSame([], $result['tables']);
        $this->assertSame(1, $result['dynamic_count']);
        $this->assertTrue($result['has_migrations']);
    }

    #[Test]
    public function inspect_ignores_commented_out_schema_create(): void
    {
        $this->makeMigration('001_create_only_in_comment.php', <<<'PHP'
<?php
// Schema::create('plg_test_should_be_ignored', function ($table) {});
/*
 * Schema::create('plg_test_block_ignored', function ($table) {});
 */
Schema::create('plg_test_real', function ($table) {});
PHP);

        $result = (new PluginTableInspector())->inspect($this->tempDir);

        $this->assertSame(['plg_test_real'], $result['tables']);
        $this->assertSame(0, $result['dynamic_count']);
    }

    #[Test]
    public function inspect_deduplicates_table_names_seen_multiple_times(): void
    {
        $this->makeMigration('001_create_a.php', <<<'PHP'
<?php
Schema::create('plg_dup', function ($table) {});
Schema::dropIfExists('plg_dup');
Schema::create('plg_dup', function ($table) {});
PHP);

        $result = (new PluginTableInspector())->inspect($this->tempDir);

        $this->assertSame(['plg_dup'], $result['tables']);
    }

    #[Test]
    public function inspect_skips_non_php_files(): void
    {
        File::makeDirectory("{$this->tempDir}/database/migrations", 0755, true);
        File::put("{$this->tempDir}/database/migrations/notes.txt", "Schema::create('plg_should_not_match', function () {});");

        $result = (new PluginTableInspector())->inspect($this->tempDir);

        $this->assertSame([], $result['tables']);
        $this->assertTrue($result['has_migrations']);
    }

    private function makeMigration(string $filename, string $contents): void
    {
        $migrationsDir = "{$this->tempDir}/database/migrations";
        if (! is_dir($migrationsDir)) {
            File::makeDirectory($migrationsDir, 0755, true);
        }
        File::put("{$migrationsDir}/{$filename}", $contents);
    }
}
