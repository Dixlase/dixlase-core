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

namespace Tests\Unit\Services\Plugin;

use App\DTO\Plugin\SignatureVerificationResult;
use App\Services\Plugin\AuthorityPublicKeyResolver;
use App\Services\Plugin\CoreSignatureVerifier;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CoreSignatureVerifierTest extends TestCase
{
    protected CoreSignatureVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        // Pin the app locale to ja so the Japanese substrings these tests
        // assert on (署名ファイル, 解析) match regardless of the CI env's
        // APP_LOCALE — CI runs with APP_LOCALE=en which would otherwise emit
        // the English translations of these messages and the substring asserts
        // would miss.
        app()->setLocale('ja');

        // Resolver をモックし、常に null を返すことで「公開鍵が取得できない」状況を
        // シミュレートする。これにより検証は PENDING で終わり、ネットワーク・DB に
        // 触れずに type/key_id 等の前段ロジックを純粋にテストできる。
        $resolver = $this->createMock(AuthorityPublicKeyResolver::class);
        $resolver->method('resolve')->willReturn(null);

        $this->verifier = new CoreSignatureVerifier($resolver);
    }

    /**
     * isAvailable() が true を返すテスト（実装版になったため）
     */
    public function test_is_available_returns_true(): void
    {
        $this->assertTrue($this->verifier->isAvailable());
    }

    /**
     * plugin.json が存在しない場合は unsigned を返すテスト
     */
    public function test_verify_returns_unsigned_when_plugin_json_missing(): void
    {
        $result = $this->verifier->verify('non-existent-plugin');

        $this->assertTrue($result->isUnsigned());
        $this->assertStringContains('plugin.json', $result->message);
    }

    /**
     * signing セクションがない場合は unsigned を返すテスト
     */
    public function test_verify_returns_unsigned_when_no_signing_section(): void
    {
        $pluginDir = $this->createTempPlugin([
            'name' => 'TestPlugin',
            'version' => '1.0.0',
        ]);

        $slug = 'test-plugin';
        $result = $this->verifyWithPluginDir($pluginDir, $slug);

        $this->assertTrue($result->isUnsigned());
    }

    /**
     * signature.sig がない場合は unsigned を返すテスト
     */
    public function test_verify_returns_unsigned_when_no_signature_file(): void
    {
        $pluginDir = $this->createTempPlugin([
            'name' => 'TestPlugin',
            'version' => '1.0.0',
            'signing' => ['key_id' => 'dixlase-official-001'],
        ]);

        $slug = 'test-plugin';
        $result = $this->verifyWithPluginDir($pluginDir, $slug);

        $this->assertTrue($result->isUnsigned());
        $this->assertStringContains('署名ファイル', $result->message);
    }

    /**
     * 署名ファイルが存在する場合は pending を返すテスト
     */
    public function test_verify_returns_pending_with_signature_file(): void
    {
        $pluginDir = $this->createTempPlugin(
            [
                'name' => 'TestPlugin',
                'version' => '1.0.0',
                'signing' => ['key_id' => 'dixlase-official-001'],
            ],
            [
                'key_id' => 'dixlase-official-001',
                'signed_by' => 'exc-D inc.',
                'signed_at' => '2025-06-01T00:00:00Z',
                'signature' => 'base64signature==',
            ]
        );

        $slug = 'test-plugin';
        $result = $this->verifyWithPluginDir($pluginDir, $slug);

        $this->assertEquals(SignatureVerificationResult::STATUS_PENDING, $result->status);
        $this->assertEquals('official', $result->type);
        $this->assertEquals('exc-D inc.', $result->signedBy);
        $this->assertEquals('2025-06-01T00:00:00Z', $result->signedAt);
        $this->assertEquals('dixlase-official-001', $result->keyId);
    }

    /**
     * 署名タイプ判定: official
     */
    public function test_determines_official_type(): void
    {
        $pluginDir = $this->createTempPlugin(
            ['name' => 'TestPlugin', 'signing' => ['key_id' => 'dixlase-official-001']],
            ['key_id' => 'dixlase-official-001', 'signature' => 'sig']
        );

        $result = $this->verifyWithPluginDir($pluginDir, 'test-plugin');
        $this->assertEquals('official', $result->type);
    }

    /**
     * 署名タイプ判定: verified
     */
    public function test_determines_verified_type(): void
    {
        $pluginDir = $this->createTempPlugin(
            ['name' => 'TestPlugin', 'signing' => ['key_id' => 'dixlase-verified-001']],
            ['key_id' => 'dixlase-verified-001', 'signature' => 'sig']
        );

        $result = $this->verifyWithPluginDir($pluginDir, 'test-plugin');
        $this->assertEquals('verified', $result->type);
    }

    /**
     * 署名タイプ判定: partner
     */
    public function test_determines_partner_type(): void
    {
        $pluginDir = $this->createTempPlugin(
            ['name' => 'TestPlugin', 'signing' => ['key_id' => 'partner-acme']],
            ['key_id' => 'partner-acme', 'signature' => 'sig']
        );

        $result = $this->verifyWithPluginDir($pluginDir, 'test-plugin');
        $this->assertEquals('partner', $result->type);
    }

    /**
     * 署名タイプ判定: 不明な鍵ID
     */
    public function test_returns_null_type_for_unknown_key(): void
    {
        $pluginDir = $this->createTempPlugin(
            ['name' => 'TestPlugin', 'signing' => ['key_id' => 'unknown-key']],
            ['key_id' => 'unknown-key', 'signature' => 'sig']
        );

        $result = $this->verifyWithPluginDir($pluginDir, 'test-plugin');
        $this->assertNull($result->type);
    }

    /**
     * plugin.json が不正なJSONの場合はエラーを返すテスト
     */
    public function test_verify_returns_error_for_invalid_json(): void
    {
        $pluginName = 'TestPlugin';
        $pluginDir = base_path("plugins/{$pluginName}");

        // 一時的にディレクトリを作成
        if (! File::isDirectory($pluginDir)) {
            File::makeDirectory($pluginDir, 0755, true);
        }
        File::put("{$pluginDir}/plugin.json", '{invalid json');

        try {
            $result = $this->verifier->verify('test-plugin');
            $this->assertEquals(SignatureVerificationResult::STATUS_ERROR, $result->status);
            $this->assertStringContains('解析', $result->message);
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    /**
     * 署名ファイルが不正なJSONの場合はエラーを返すテスト
     */
    public function test_verify_returns_error_for_invalid_signature_json(): void
    {
        $pluginName = 'TestPlugin';
        $pluginDir = base_path("plugins/{$pluginName}");

        if (! File::isDirectory($pluginDir)) {
            File::makeDirectory($pluginDir, 0755, true);
        }
        File::put("{$pluginDir}/plugin.json", json_encode([
            'name' => 'TestPlugin',
            'signing' => ['key_id' => 'test'],
        ]));
        File::put("{$pluginDir}/signature.sig", '{invalid sig json');

        try {
            $result = $this->verifier->verify('test-plugin');
            $this->assertEquals(SignatureVerificationResult::STATUS_ERROR, $result->status);
            $this->assertStringContains('署名ファイル', $result->message);
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    /**
     * テスト用プラグインディレクトリを作成
     *
     * @param  array  $pluginJson  plugin.json の内容
     * @param  array|null  $signatureData  signature.sig の内容（null の場合作成しない）
     * @return string プラグインディレクトリパス
     */
    protected function createTempPlugin(array $pluginJson, ?array $signatureData = null): string
    {
        $pluginName = 'TestPlugin';
        $pluginDir = base_path("plugins/{$pluginName}");

        if (! File::isDirectory($pluginDir)) {
            File::makeDirectory($pluginDir, 0755, true);
        }

        File::put(
            "{$pluginDir}/plugin.json",
            json_encode($pluginJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        if ($signatureData !== null) {
            File::put(
                "{$pluginDir}/signature.sig",
                json_encode($signatureData, JSON_PRETTY_PRINT)
            );
        }

        return $pluginDir;
    }

    /**
     * テスト用プラグインディレクトリで検証を実行
     */
    protected function verifyWithPluginDir(string $pluginDir, string $slug): SignatureVerificationResult
    {
        try {
            return $this->verifier->verify($slug);
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    /**
     * 文字列に特定のサブストリングが含まれるかアサート
     */
    protected function assertStringContains(string $needle, ?string $haystack): void
    {
        $this->assertNotNull($haystack, "Expected string containing '{$needle}', got null");
        $this->assertStringContainsString($needle, $haystack);
    }
}
