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

namespace Tests\Unit\Services\Csp;

use App\Services\Csp\CspComplianceScanner;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * CspComplianceScanner のユニットテスト
 *
 * プラグインディレクトリ内の Blade ファイルをスキャンして
 * CSP 違反を検出する機能を検証する
 */
class CspComplianceScannerTest extends TestCase
{
    protected CspComplianceScanner $scanner;

    /** @var string テスト用一時ディレクトリ */
    protected string $tmpDir;

    /** @var string テスト用プラグインスラッグ */
    protected string $testSlug = 'test-csp-plugin';

    protected function setUp(): void
    {
        parent::setUp();

        $this->scanner = new CspComplianceScanner();

        // テスト用一時ディレクトリを plugins/ 配下に作成
        $this->tmpDir = base_path("plugins/{$this->testSlug}");
        File::makeDirectory("{$this->tmpDir}/resources/views", 0755, true, true);
    }

    protected function tearDown(): void
    {
        // テスト用一時ディレクトリを削除
        if (File::isDirectory($this->tmpDir)) {
            File::deleteDirectory($this->tmpDir);
        }

        parent::tearDown();
    }

    /**
     * ヘルパー: Blade ファイルをテスト用ディレクトリに作成する
     */
    private function createBladeFile(string $filename, string $content): string
    {
        $path = "{$this->tmpDir}/resources/views/{$filename}";
        File::put($path, $content);

        return $path;
    }

    // =====================================================
    // 存在しないディレクトリ
    // =====================================================

    /**
     * 存在しないプラグインをスキャンすると status が 'unknown' になる
     */
    public function test_returns_unknown_status_when_directory_does_not_exist(): void
    {
        $result = $this->scanner->scanPlugin('nonexistent-plugin-xyz');

        $this->assertSame('unknown', $result['status']);
        $this->assertEmpty($result['violations']);
    }

    // =====================================================
    // クリーンなファイル（違反なし）
    // =====================================================

    /**
     * 違反がない Blade ファイルをスキャンすると compatible ステータスを返す
     */
    public function test_returns_compatible_status_for_clean_blade_file(): void
    {
        $this->createBladeFile('clean.blade.php', <<<'BLADE'
            <div class="container">
                <p>Hello World</p>
                <script src="/js/app.js"></script>
            </div>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame('compatible', $result['status']);
        $this->assertFalse($result['requires_inline_js']);
        $this->assertFalse($result['requires_inline_css']);
        $this->assertEmpty($result['violations']);
        $this->assertSame(0, $result['summary']['inline_scripts']);
        $this->assertSame(0, $result['summary']['inline_styles']);
        $this->assertSame(0, $result['summary']['event_handlers']);
        $this->assertSame(0, $result['summary']['javascript_urls']);
    }

    // =====================================================
    // インラインスクリプト検出
    // =====================================================

    /**
     * src 属性のない <script> タグをインラインスクリプトとして検出する
     */
    public function test_detects_inline_script_tag_without_src(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            <div>
                <script>
                    console.log('hello');
                </script>
            </div>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame('inline_required', $result['status']);
        $this->assertTrue($result['requires_inline_js']);
        $this->assertSame(1, $result['summary']['inline_scripts']);
        $this->assertCount(1, $result['violations']);
        $this->assertSame('inline_script', $result['violations'][0]['type']);
        $this->assertSame('warning', $result['violations'][0]['severity']);
    }

    /**
     * src 属性を持つ <script> タグはインラインスクリプトとして検出しない
     */
    public function test_does_not_detect_script_tag_with_src(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            <script src="/js/app.js"></script>
            <script src="https://cdn.example.com/lib.js"></script>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame(0, $result['summary']['inline_scripts']);
        $this->assertEmpty($result['violations']);
    }

    /**
     * type="application/json" の <script> タグはインラインスクリプトとして検出しない
     */
    public function test_does_not_detect_script_tag_with_json_type(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            <script type="application/json">{"key": "value"}</script>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame(0, $result['summary']['inline_scripts']);
        $this->assertEmpty($result['violations']);
    }

    /**
     * type="application/ld+json" の <script> タグはインラインスクリプトとして検出しない
     */
    public function test_does_not_detect_script_tag_with_ld_json_type(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            <script type="application/ld+json">{"@context": "https://schema.org"}</script>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame(0, $result['summary']['inline_scripts']);
        $this->assertEmpty($result['violations']);
    }

    // =====================================================
    // インラインスタイル検出
    // =====================================================

    /**
     * <style> タグをインラインスタイルとして検出する
     */
    public function test_detects_inline_style_tag(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            <style>
                body { background: red; }
            </style>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame('inline_css_only', $result['status']);
        $this->assertFalse($result['requires_inline_js']);
        $this->assertTrue($result['requires_inline_css']);
        $this->assertSame(1, $result['summary']['inline_styles']);
        $this->assertCount(1, $result['violations']);
        $this->assertSame('inline_style', $result['violations'][0]['type']);
        $this->assertSame('info', $result['violations'][0]['severity']);
    }

    /**
     * インラインスクリプトとインラインスタイルが両方ある場合は inline_required ステータスを返す
     */
    public function test_inline_required_takes_precedence_over_inline_css_only(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            <style>body { color: red; }</style>
            <script>console.log('test');</script>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame('inline_required', $result['status']);
        $this->assertTrue($result['requires_inline_js']);
        $this->assertTrue($result['requires_inline_css']);
    }

    // =====================================================
    // インラインイベントハンドラ検出
    // =====================================================

    /**
     * onclick 属性をイベントハンドラとして検出する
     */
    public function test_detects_onclick_event_handler(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            <button onclick="doSomething()">Click</button>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame('inline_required', $result['status']);
        $this->assertSame(1, $result['summary']['event_handlers']);
        $this->assertSame('event_handler', $result['violations'][0]['type']);
        $this->assertSame('warning', $result['violations'][0]['severity']);
    }

    /**
     * onchange 属性をイベントハンドラとして検出する
     */
    public function test_detects_onchange_event_handler(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            <select onchange="handleChange(this.value)">
                <option value="1">Option 1</option>
            </select>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame(1, $result['summary']['event_handlers']);
    }

    /**
     * onsubmit 属性をイベントハンドラとして検出する
     */
    public function test_detects_onsubmit_event_handler(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            <form onsubmit="return validate()">
                <input type="submit">
            </form>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame(1, $result['summary']['event_handlers']);
    }

    // =====================================================
    // javascript: URL 検出
    // =====================================================

    /**
     * href="javascript:..." の URL を検出する
     */
    public function test_detects_javascript_url_in_href(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            <a href="javascript:void(0)">Click</a>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame('inline_required', $result['status']);
        $this->assertSame(1, $result['summary']['javascript_urls']);
        $this->assertSame('javascript_url', $result['violations'][0]['type']);
        $this->assertSame('critical', $result['violations'][0]['severity']);
    }

    // =====================================================
    // Blade コメント内の除外
    // =====================================================

    /**
     * Blade コメント内のインラインスクリプトは検出しない
     */
    public function test_excludes_inline_script_inside_blade_comment(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            {{-- <script>console.log('ignored');</script> --}}
            <p>Normal content</p>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame(0, $result['summary']['inline_scripts']);
        $this->assertEmpty($result['violations']);
    }

    /**
     * Blade コメント内のイベントハンドラは検出しない
     */
    public function test_excludes_event_handler_inside_blade_comment(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            {{-- <button onclick="doSomething()">Old Button</button> --}}
            <button>New Button</button>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame(0, $result['summary']['event_handlers']);
        $this->assertEmpty($result['violations']);
    }

    // =====================================================
    // HTML コメント内の除外
    // =====================================================

    /**
     * HTML コメント内のインラインイベントハンドラは検出しない
     */
    public function test_excludes_event_handler_inside_html_comment(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            <!-- <button onclick="doSomething()">Old</button> -->
            <p>Normal</p>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame(0, $result['summary']['event_handlers']);
        $this->assertEmpty($result['violations']);
    }

    /**
     * HTML コメント内の javascript: URL は検出しない
     */
    public function test_excludes_javascript_url_inside_html_comment(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            <!-- <a href="javascript:void(0)">Old</a> -->
            <a href="#">New</a>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame(0, $result['summary']['javascript_urls']);
        $this->assertEmpty($result['violations']);
    }

    // =====================================================
    // PHP コメント内の除外
    // =====================================================

    /**
     * PHP 行コメント内のイベントハンドラは検出しない
     */
    public function test_excludes_event_handler_inside_php_line_comment(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            @php
            // <button onclick="doSomething()">Old</button>
            $var = 'test';
            @endphp
            <p>{{ $var }}</p>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame(0, $result['summary']['event_handlers']);
    }

    /**
     * PHP ブロックコメント内のイベントハンドラは検出しない
     */
    public function test_excludes_event_handler_inside_php_block_comment(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            @php
            /*
             * onclick="oldHandler()"
             */
            $var = 'test';
            @endphp
            <p>{{ $var }}</p>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame(0, $result['summary']['event_handlers']);
    }

    // =====================================================
    // violations 配列の構造検証
    // =====================================================

    /**
     * violations 配列の各エントリが必須フィールドを持つ
     */
    public function test_violation_has_required_fields(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            <script>alert('test');</script>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertNotEmpty($result['violations']);
        $violation = $result['violations'][0];

        $this->assertArrayHasKey('type', $violation);
        $this->assertArrayHasKey('file', $violation);
        $this->assertArrayHasKey('line', $violation);
        $this->assertArrayHasKey('match', $violation);
        $this->assertArrayHasKey('severity', $violation);
        $this->assertIsInt($violation['line']);
        $this->assertGreaterThan(0, $violation['line']);
    }

    /**
     * violation の line は正確な行番号を返す
     */
    public function test_violation_reports_correct_line_number(): void
    {
        $this->createBladeFile('index.blade.php', <<<'BLADE'
            <div>
                <p>First line content</p>
                <script>alert('line 3');</script>
            </div>
            BLADE);

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertNotEmpty($result['violations']);
        $this->assertSame(3, $result['violations'][0]['line']);
    }

    /**
     * 結果配列が必須キーをすべて持つ
     */
    public function test_result_has_all_required_keys(): void
    {
        $this->createBladeFile('index.blade.php', '<p>clean</p>');

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('requires_inline_js', $result);
        $this->assertArrayHasKey('requires_inline_css', $result);
        $this->assertArrayHasKey('has_csp_config', $result);
        $this->assertArrayHasKey('violations', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('inline_scripts', $result['summary']);
        $this->assertArrayHasKey('inline_styles', $result['summary']);
        $this->assertArrayHasKey('event_handlers', $result['summary']);
        $this->assertArrayHasKey('javascript_urls', $result['summary']);
    }

    // =====================================================
    // ステータス判定（determineStatus）
    // =====================================================

    /**
     * CSP 設定ファイルが存在する場合は csp_ready ステータスを返す
     */
    public function test_returns_csp_ready_status_when_has_csp_config(): void
    {
        $this->createBladeFile('index.blade.php', '<p>clean</p>');

        // plugin.json に csp セクションを追加
        File::put("{$this->tmpDir}/plugin.json", json_encode([
            'name' => 'Test Plugin',
            'slug' => $this->testSlug,
            'csp' => ['policy' => "script-src 'self'"],
        ]));

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame('csp_ready', $result['status']);
        $this->assertTrue($result['has_csp_config']);
    }

    /**
     * CSP 設定なし・違反なしの場合は compatible ステータスを返す
     */
    public function test_returns_compatible_status_when_no_violations_and_no_csp_config(): void
    {
        $this->createBladeFile('index.blade.php', '<p>clean</p>');

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame('compatible', $result['status']);
        $this->assertFalse($result['has_csp_config']);
    }

    /**
     * インラインスクリプトありの場合は inline_required ステータスを返す（CSP 設定より優先）
     */
    public function test_inline_required_overrides_csp_ready(): void
    {
        $this->createBladeFile('index.blade.php', '<script>alert(1);</script>');

        File::put("{$this->tmpDir}/plugin.json", json_encode([
            'slug' => $this->testSlug,
            'csp' => ['policy' => "script-src 'self' 'unsafe-inline'"],
        ]));

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame('inline_required', $result['status']);
    }

    // =====================================================
    // 複数ファイル・複数違反
    // =====================================================

    /**
     * 複数の Blade ファイルをまたいだ違反を集計する
     */
    public function test_scans_multiple_blade_files(): void
    {
        $this->createBladeFile('page1.blade.php', '<script>alert(1);</script>');
        $this->createBladeFile('page2.blade.php', '<style>body{}</style>');
        $this->createBladeFile('page3.blade.php', '<button onclick="click()">Btn</button>');

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame(1, $result['summary']['inline_scripts']);
        $this->assertSame(1, $result['summary']['inline_styles']);
        $this->assertSame(1, $result['summary']['event_handlers']);
        $this->assertCount(3, $result['violations']);
    }

    /**
     * tests/ ディレクトリ内のファイルはスキャン対象外
     */
    public function test_excludes_files_in_tests_directory(): void
    {
        File::makeDirectory("{$this->tmpDir}/tests", 0755, true, true);
        File::put("{$this->tmpDir}/tests/TestView.blade.php", '<script>alert(1);</script>');
        $this->createBladeFile('clean.blade.php', '<p>clean</p>');

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertSame(0, $result['summary']['inline_scripts']);
        $this->assertEmpty($result['violations']);
    }

    /**
     * .blade.php 以外のファイルはスキャン対象外
     */
    public function test_only_scans_blade_php_files(): void
    {
        File::put("{$this->tmpDir}/resources/views/script.js", 'onclick="test()"');
        File::put("{$this->tmpDir}/resources/views/style.css", 'body{}');
        $this->createBladeFile('clean.blade.php', '<p>clean</p>');

        $result = $this->scanner->scanPlugin($this->testSlug);

        $this->assertEmpty($result['violations']);
    }
}
