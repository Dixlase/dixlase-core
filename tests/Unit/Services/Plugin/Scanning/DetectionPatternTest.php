<?php

namespace Tests\Unit\Services\Plugin\Scanning;

use App\Services\Plugin\Scanning\DangerousApiPattern;
use App\Services\Plugin\Scanning\DatabaseDetectionPattern;
use App\Services\Plugin\Scanning\MailDetectionPattern;
use App\Services\Plugin\Scanning\MemberDetectionPattern;
use App\Services\Plugin\Scanning\MiddlewareDetectionPattern;
use App\Services\Plugin\Scanning\SettingsDetectionPattern;
use App\Services\Plugin\Scanning\StorageDetectionPattern;
use App\Services\Plugin\Scanning\SystemDetectionPattern;
use App\Services\Plugin\Scanning\ThemeAssetDetectionPattern;
use Tests\TestCase;

class DetectionPatternTest extends TestCase
{
    /**
     * DatabaseDetectionPattern: コアテーブルのuse文のみは除外
     */
    public function test_database_core_tables_excludes_use_import(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables');
        $content = "<?php\nuse App\\Models\\Member;\n\nclass Test {}\n";

        $results = $pattern->scan($content, 'app/Test.php');
        $this->assertEmpty($results);
    }

    /**
     * DatabaseDetectionPattern: コアテーブルの実使用を検出
     */
    public function test_database_core_tables_detects_actual_usage(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables');
        $content = "<?php\n\$users = DB::table('members')->get();\n";

        $results = $pattern->scan($content, 'app/Service.php');
        $this->assertNotEmpty($results);
    }

    /**
     * MailDetectionPattern: use文のインポートのみは除外
     */
    public function test_mail_excludes_use_import(): void
    {
        $pattern = new MailDetectionPattern('send');
        $content = "<?php\nuse Illuminate\\Support\\Facades\\Mail;\n\nclass Test {}\n";

        $results = $pattern->scan($content, 'app/Test.php');
        $this->assertEmpty($results);
    }

    /**
     * MailDetectionPattern: Mailableの実装を検出
     */
    public function test_mail_detects_mailable_class(): void
    {
        $pattern = new MailDetectionPattern('send');
        $content = "<?php\nclass TestMail extends Mailable\n{\n}\n";

        $results = $pattern->scan($content, 'app/Mail/TestMail.php');
        $this->assertNotEmpty($results);
    }

    /**
     * MailDetectionPattern: bulk_sendの偽陽性を除外
     */
    public function test_mail_bulk_send_no_false_positive_for_distant_foreach(): void
    {
        $pattern = new MailDetectionPattern('bulk_send');
        // foreachとMail::toが200文字以上離れている
        $content = "<?php\nforeach (\$items as \$item) {\n    // process item\n}\n";
        $content .= str_repeat("// padding line\n", 30);
        $content .= "Mail::to(\$email)->send(new TestMail());\n";

        $results = $pattern->scan($content, 'app/Service.php');
        $this->assertEmpty($results);
    }

    /**
     * MiddlewareDetectionPattern: Route::middlewareは除外
     */
    public function test_middleware_excludes_route_middleware_usage(): void
    {
        $pattern = new MiddlewareDetectionPattern();
        $content = "<?php\nRoute::middleware('auth')->group(function () {\n});\n";

        $results = $pattern->scan($content, 'routes/web.php');
        $this->assertEmpty($results);
    }

    /**
     * MiddlewareDetectionPattern: pushMiddlewareは検出
     */
    public function test_middleware_detects_push_middleware(): void
    {
        $pattern = new MiddlewareDetectionPattern();
        $content = "<?php\n\$this->app['router']->pushMiddlewareToGroup('web', MyMiddleware::class);\n";

        $results = $pattern->scan($content, 'app/Providers/ServiceProvider.php');
        $this->assertNotEmpty($results);
    }

    /**
     * DangerousApiPattern: コメント内は除外
     */
    public function test_dangerous_api_excludes_comments(): void
    {
        $pattern = new DangerousApiPattern('exec');
        $content = "<?php\n// exec() is dangerous\n/* shell_exec should not be used */\n";

        $results = $pattern->scan($content, 'app/Test.php');
        $this->assertEmpty($results);
    }

    /**
     * DangerousApiPattern: 実際のexec呼び出しを検出
     */
    public function test_dangerous_api_detects_actual_exec(): void
    {
        $pattern = new DangerousApiPattern('exec');
        $content = "<?php\n\$output = exec('ls -la');\n";

        $results = $pattern->scan($content, 'app/Service.php');
        $this->assertNotEmpty($results);
    }

    /**
     * DangerousApiPattern: configファイル内のenv()は除外
     */
    public function test_dangerous_api_excludes_env_in_config(): void
    {
        $pattern = new DangerousApiPattern('env_access');
        $content = "<?php\nreturn [\n    'key' => env('APP_KEY'),\n];\n";

        $results = $pattern->scan($content, 'config/app.php');
        $this->assertEmpty($results);
    }

    /**
     * DangerousApiPattern: config外のenv()は検出
     */
    public function test_dangerous_api_detects_env_outside_config(): void
    {
        $pattern = new DangerousApiPattern('env_access');
        $content = "<?php\n\$key = env('APP_KEY');\n";

        $results = $pattern->scan($content, 'app/Service.php');
        $this->assertNotEmpty($results);
    }

    /**
     * MemberDetectionPattern: use文のみは除外
     */
    public function test_member_excludes_use_import(): void
    {
        $pattern = new MemberDetectionPattern('read');
        $content = "<?php\nuse App\\Models\\Member;\n\nclass Test {}\n";

        $results = $pattern->scan($content, 'app/Test.php');
        $this->assertEmpty($results);
    }

    /**
     * StorageDetectionPattern: use文のみは除外
     */
    public function test_storage_excludes_use_import(): void
    {
        $pattern = new StorageDetectionPattern('own_directory');
        $content = "<?php\nuse Illuminate\\Support\\Facades\\Storage;\n\nclass Test {}\n";

        $results = $pattern->scan($content, 'app/Test.php');
        $this->assertEmpty($results);
    }

    /**
     * ThemeAssetDetectionPattern: テーマ専用
     */
    public function test_theme_asset_is_theme_only(): void
    {
        $pattern = new ThemeAssetDetectionPattern('custom_css');
        $this->assertEquals('theme', $pattern->applicableTo());
    }

    /**
     * SystemDetectionPattern: 全パーミッションキーが正しい
     */
    public function test_system_permission_keys(): void
    {
        $shortcodes = new SystemDetectionPattern('register_shortcodes');
        $commands = new SystemDetectionPattern('register_commands');
        $blade = new SystemDetectionPattern('register_blade_directives');
        $routes = new SystemDetectionPattern('modify_routes');

        $this->assertEquals('system.register_shortcodes', $shortcodes->permissionKey());
        $this->assertEquals('system.register_commands', $commands->permissionKey());
        $this->assertEquals('system.register_blade_directives', $blade->permissionKey());
        $this->assertEquals('system.modify_routes', $routes->permissionKey());
    }

    /**
     * SettingsDetectionPattern: use文のみは除外
     */
    public function test_settings_excludes_use_import(): void
    {
        $pattern = new SettingsDetectionPattern('read_core');
        $content = "<?php\nuse App\\Models\\BaseSetting;\n\nclass Test {}\n";

        $results = $pattern->scan($content, 'app/Test.php');
        $this->assertEmpty($results);
    }

    /**
     * 全パターンのapplicableTo()が有効な値を返す
     */
    public function test_all_patterns_have_valid_applicable_to(): void
    {
        $patterns = [
            new DatabaseDetectionPattern('own_tables'),
            new StorageDetectionPattern('own_directory'),
            new MemberDetectionPattern('read'),
            new MailDetectionPattern('send'),
            new MiddlewareDetectionPattern(),
            new SystemDetectionPattern('register_shortcodes'),
            new DangerousApiPattern('exec'),
            new SettingsDetectionPattern('read_core'),
            new ThemeAssetDetectionPattern('custom_css'),
        ];

        foreach ($patterns as $pattern) {
            $this->assertContains($pattern->applicableTo(), ['both', 'plugin', 'theme']);
        }
    }
}
