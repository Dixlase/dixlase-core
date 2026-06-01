<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

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
     * DatabaseDetectionPattern: `use App\Models\<core-class>;` IS treated as
     * evidence of core_tables_read. PHP requires explicit imports, so the
     * presence of a `use` line referencing a core model is a strong static
     * signal that the file consumes that core table — especially when the
     * plugin follows the recommended short-name idiom (`Member::...`).
     * Before this change the `use ` filter masked the only static signal we
     * had for plugins using `use ... + short-name`, producing false-positive
     * "unused declaration" warnings (see plan/handoff-plugin-audit-false-positives.md).
     */
    public function test_database_core_tables_read_counts_use_import_of_core_model(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables_read');
        $content = "<?php\nuse App\\Models\\Member;\n\nclass Test {}\n";

        $results = $pattern->scan($content, 'app/Test.php');
        $this->assertNotEmpty(
            $results,
            'use App\\Models\\<core-class>; must count as core_tables_read evidence so '.
            'the recommended short-name idiom is not penalised.',
        );
    }

    /**
     * DatabaseDetectionPattern: `use` of a non-core namespace must NOT
     * spuriously match. The regex is intentionally constrained to
     * App\Models\* and the enumerated App\Facades\* shortlist, so a use
     * statement for any other namespace (helpers, traits, third-party
     * vendor code, plugin internals) reads zero evidence.
     */
    public function test_database_core_tables_read_ignores_unrelated_use_import(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables_read');
        $content = "<?php\nuse App\\Helpers\\StringTool;\nuse Plugins\\DixlaseInquiry\\App\\Services\\Inner;\n\nclass Test {}\n";

        $results = $pattern->scan($content, 'app/Test.php');
        $this->assertEmpty(
            $results,
            'use statements of non-core namespaces must NOT count as core_tables_read evidence.',
        );
    }

    /**
     * DatabaseDetectionPattern: regression guard — direct FQN usage of a
     * core facade (e.g. `\App\Facades\SiteSettings::get(...)`) must still
     * match after we relaxed the leading-backslash requirement.
     */
    public function test_database_core_tables_read_still_detects_fqn_facade_call(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables_read');
        $content = "<?php\n\$mode = \\App\\Facades\\SiteSettings::get('admin_mode', 0);\n";

        $results = $pattern->scan($content, 'app/Controller.php');
        $this->assertNotEmpty(
            $results,
            'FQN form \\App\\Facades\\SiteSettings::get(...) must still be recognised after the regex relaxation.',
        );
    }

    /**
     * DatabaseDetectionPattern: the recommended idiom — `use App\Facades\SiteSettings;`
     * (no leading backslash) combined with a later short-name call site — IS
     * counted because the use line itself matches the new regex. This is the
     * exact pattern DixlaseInquiry follows and the original false-positive
     * case the handoff document targets.
     */
    public function test_database_core_tables_read_counts_use_import_of_core_facade(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables_read');
        $content = "<?php\nuse App\\Facades\\SiteSettings;\n\nclass C {\n    public function handle() {\n        return SiteSettings::get('admin_mode', 0);\n    }\n}\n";

        $results = $pattern->scan($content, 'app/Controller.php');
        $this->assertNotEmpty(
            $results,
            'use App\\Facades\\SiteSettings; must count as evidence so the recommended idiom '.
            '(use + short-name call) is not penalised as an unused declaration.',
        );
    }

    /**
     * DatabaseDetectionPattern: a `use` of a repository contract under
     * App\Contracts\Repositories\ must count as core_tables_read
     * evidence. CLAUDE.md "Cross-Plugin/Theme Data Access" requires
     * plugins to reach core tables via these contracts instead of
     * importing App\Models\* directly. Each repository proxies a
     * known core table (Media -> media, SiteSetting -> site_settings,
     * etc.), so the contract import is the strongest static signal
     * that the file reaches the underlying table. Without this match,
     * plugins that fully migrated to the contract idiom (DixlaseSEO
     * after the f3c5e8f refactor) get a false-positive
     * "unused_declaration" mismatch on their declared core_tables_read
     * entries even though they legitimately access the table through
     * the abstraction layer.
     */
    public function test_database_core_tables_read_counts_use_import_of_repository_contract(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables_read');
        $content = "<?php\nuse App\\Contracts\\Repositories\\MediaRepositoryInterface;\n\nclass C {\n    public function __construct(private readonly MediaRepositoryInterface \$repo) {}\n}\n";

        $results = $pattern->scan($content, 'app/Service.php');
        $this->assertNotEmpty(
            $results,
            'use App\\Contracts\\Repositories\\<X>RepositoryInterface; must count as core_tables_read '.
            'evidence so the recommended contract-based access pattern is not penalised.',
        );
    }

    /**
     * DatabaseDetectionPattern: regression guard — a plugin-nested
     * repository contract (e.g. Plugins\Foo\App\Contracts\Repositories\Bar)
     * must NOT be mis-detected as a core-table reference. Same
     * negative-lookbehind rationale as the App\Models\X exclusion.
     */
    public function test_database_core_tables_read_ignores_plugin_nested_repository_contract(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables_read');
        $content = "<?php\nuse Plugins\\DixlaseFoo\\App\\Contracts\\Repositories\\BarRepositoryInterface;\n\nclass C {}\n";

        $results = $pattern->scan($content, 'app/Service.php');
        $this->assertEmpty(
            $results,
            'plugin-nested App\\Contracts\\Repositories\\X must NOT count as core_tables_read evidence.',
        );
    }

    /**
     * DatabaseDetectionPattern: core_tables_write must still require an
     * actual write operation in the file — a use-only file imports the
     * model but does not write to it, and must therefore NOT count as
     * evidence of core_tables_write.
     */
    public function test_database_core_tables_write_use_import_without_write_is_excluded(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables_write');
        $content = "<?php\nuse App\\Models\\Member;\n\nclass Reader {\n    public function name(Member \$m) { return \$m->name; }\n}\n";

        $results = $pattern->scan($content, 'app/Service.php');
        $this->assertEmpty(
            $results,
            'use-only file (no ->save/->create/->update/->delete/->insert/->upsert) must NOT count as core_tables_write.',
        );
    }

    /**
     * DatabaseDetectionPattern: a `use` line of a core class must NOT
     * count as core_tables_write evidence on its own, even when the
     * file contains write calls elsewhere. The file-level
     * hasWriteOperations() check is too coarse to tell whether those
     * writes target the imported core class or the plugin's own models —
     * a use-statement is only a class-level dependency signal, not a
     * call-site signal. Restricting write evidence to inline FQN call
     * sites eliminates the false positive where a plugin imports
     * App\Facades\SiteSettings for READ access while writing to its own
     * models in the same file (the original DixlaseInquiry case in
     * plan/handoff-plugin-audit-false-positives.md).
     */
    public function test_database_core_tables_write_excludes_use_only_match(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables_write');
        // Use a core facade for READ, write to a non-core (plugin) model.
        // The file passes hasWriteOperations() because of $own->save(), but
        // that write targets the plugin's own model — not core_settings.
        $content = "<?php\nuse App\\Facades\\SiteSettings;\n\nclass C {\n    public function handle(\$own) {\n        \$mode = SiteSettings::get('admin_mode');\n        \$own->save();\n    }\n}\n";

        $results = $pattern->scan($content, 'plugins/PluginX/app/Controller.php');
        $this->assertEmpty(
            $results,
            'A use-statement for a core class must NOT count as core_tables_write evidence — '.
            'the write call in the same file might target plugin-owned models, not the imported core class.',
        );
    }

    /**
     * DatabaseDetectionPattern: inline FQN write calls on core models
     * (e.g. `\App\Models\Member::create(...)` or
     * `DB::table('members')->update(...)`) MUST still be detected.
     * The use-only exclusion only drops pure `use ...;` matches, not
     * call-site references.
     */
    public function test_database_core_tables_write_still_detects_inline_fqn_write(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables_write');
        $content = "<?php\n\\App\\Models\\Member::find(1)->save();\n";

        $results = $pattern->scan($content, 'app/Service.php');
        $this->assertNotEmpty(
            $results,
            'Inline FQN write call on a core model must still count as core_tables_write evidence.',
        );
    }

    /**
     * DatabaseDetectionPattern: deeply-namespaced plugin classes (e.g.
     * `\Plugins\DixlaseInquiry\App\Models\DixlaseInquiry`) must NOT be
     * mis-detected as core-table access. The substring `\App\Models\X`
     * appears inside the plugin FQN, but the leading namespace prefix
     * (`Plugins\DixlaseInquiry`) clearly marks it as a plugin-owned
     * class, not a core model. The negative lookbehind in the regex
     * guarantees this exclusion.
     *
     * Without this guard, every plugin that follows the standard
     * Plugins\<Plugin>\App\Models\* layout would be falsely flagged for
     * core_tables_read / core_tables_write, which is the inverse of the
     * intended behaviour and exactly the regression that surfaced when
     * the use-statement filter was removed (see
     * plan/handoff-plugin-audit-false-positives.md).
     */
    public function test_database_core_tables_ignores_plugin_nested_models(): void
    {
        $readPattern = new DatabaseDetectionPattern('core_tables_read');
        $writePattern = new DatabaseDetectionPattern('core_tables_write');

        $contentFqn = "<?php\n\$row = \\Plugins\\DixlaseInquiry\\App\\Models\\DixlaseInquiry::find(1);\n";
        $contentUse = "<?php\nuse Plugins\\DixlaseInquiry\\App\\Models\\DixlaseInquirySetting;\n\nclass C {}\n";
        // include a write call so the core_tables_write file-level guard
        // is satisfied — but the namespace prefix should still prevent
        // a plugin-owned model from contributing evidence.
        $contentWrite = "<?php\n\$row = new \\Plugins\\DixlaseInquiry\\App\\Models\\DixlaseInquiry();\n\$row->save();\n";

        $this->assertEmpty(
            $readPattern->scan($contentFqn, 'plugins/DixlaseInquiry/app/Service.php'),
            'FQN reference to a plugin-nested App\\Models\\X must NOT count as core_tables_read.',
        );
        $this->assertEmpty(
            $readPattern->scan($contentUse, 'plugins/DixlaseInquiry/app/Service.php'),
            'use statement of a plugin-nested App\\Models\\X must NOT count as core_tables_read.',
        );
        $this->assertEmpty(
            $writePattern->scan($contentWrite, 'plugins/DixlaseInquiry/app/Service.php'),
            'plugin-nested App\\Models\\X with ->save() must NOT count as core_tables_write.',
        );
    }

    /**
     * DatabaseDetectionPattern: コアテーブル読み取りの実使用を検出
     */
    public function test_database_core_tables_read_detects_actual_usage(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables_read');
        $content = "<?php\n\$users = DB::table('members')->get();\n";

        $results = $pattern->scan($content, 'app/Service.php');
        $this->assertNotEmpty($results);
    }

    /**
     * DatabaseDetectionPattern: コアテーブル書き込みの検出
     */
    public function test_database_core_tables_write_detects_write_operations(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables_write');
        $content = "<?php\nDB::table('members')->update(['name' => 'test']);\n";

        $results = $pattern->scan($content, 'app/Service.php');
        $this->assertNotEmpty($results);
    }

    /**
     * DatabaseDetectionPattern: コアテーブル書き込みは読み取りのみでは検出しない
     */
    public function test_database_core_tables_write_ignores_read_only(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables_write');
        $content = "<?php\n\$users = DB::table('members')->get();\n";

        $results = $pattern->scan($content, 'app/Service.php');
        $this->assertEmpty($results);
    }

    /**
     * DatabaseDetectionPattern: any App\Models\* class is detected,
     * not just the original hardcoded shortlist. Use case: plugins
     * accessing core models like CaptchaEnabledForm or
     * RolePermissionOverride that were missing from the old regex.
     */
    public function test_database_core_tables_read_detects_arbitrary_core_model(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables_read');
        $content = "<?php\n\$row = \\App\\Models\\CaptchaEnabledForm::isFormEnabled('inquiry');\n";

        $results = $pattern->scan($content, 'app/Service.php');
        $this->assertNotEmpty(
            $results,
            'core_tables_read must detect any App\\Models\\* reference, not only the original shortlist.',
        );
    }

    /**
     * DatabaseDetectionPattern: App\Facades\SiteSettings (the recommended
     * facade entry point for reading core base settings) is detected.
     * Plugins following the App\Facades\SiteSettings::get(...) idiom
     * recommended in handoff-plugin-core-access-cleanup.md should not
     * be penalised for "unused" core_tables_read declarations.
     */
    public function test_database_core_tables_read_detects_settings_facade(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables_read');
        $content = "<?php\n\$mode = \\App\\Facades\\SiteSettings::get('admin_mode', 0);\n";

        $results = $pattern->scan($content, 'app/Controller.php');
        $this->assertNotEmpty(
            $results,
            'core_tables_read must detect App\\Facades\\SiteSettings (and the other core facades) so the recommended idiom does not get penalised.',
        );
    }

    /**
     * DatabaseDetectionPattern: extended core table list. Tables like
     * members_role_permissions (seeded by plugin role-permission
     * seeders) are now in the shortlist and detected.
     */
    public function test_database_core_tables_write_detects_extended_table_list(): void
    {
        $pattern = new DatabaseDetectionPattern('core_tables_write');
        $content = "<?php\nDB::table('members_role_permissions')->insert(['menu_key' => 'settings.inquiries']);\n";

        $results = $pattern->scan($content, 'database/seeders/Seed.php');
        $this->assertNotEmpty(
            $results,
            'core_tables_write must recognise members_role_permissions and other ancillary core tables, not only the original shortlist.',
        );
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
     * MiddlewareDetectionPattern: prependMiddlewareToGroupは検出
     */
    public function test_middleware_detects_prepend_middleware_to_group(): void
    {
        $pattern = new MiddlewareDetectionPattern();
        $content = "<?php\n\$router->prependMiddlewareToGroup('web', HandleRedirects::class);\n";

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
        $content = "<?php\nuse App\\Models\\SiteSetting;\n\nclass Test {}\n";

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
