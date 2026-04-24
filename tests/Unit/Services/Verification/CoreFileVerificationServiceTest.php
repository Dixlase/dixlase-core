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

namespace Tests\Unit\Services\Verification;

use App\Contracts\Verification\FileVerificationServiceInterface;
use App\Services\Verification\CoreFileVerificationService;
use Tests\TestCase;

class CoreFileVerificationServiceTest extends TestCase
{
    private CoreFileVerificationService $service;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CoreFileVerificationService;
        $this->tempDir = sys_get_temp_dir().'/dixlase_verification_test_'.uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir.'/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    /**
     * FileVerificationServiceInterface を実装していることを確認
     */
    public function test_implements_interface(): void
    {
        $this->assertInstanceOf(FileVerificationServiceInterface::class, $this->service);
    }

    /**
     * hashFile が正しい SHA-256 ハッシュを返す
     */
    public function test_hash_file_returns_correct_sha256(): void
    {
        $filePath = $this->tempDir.'/test.txt';
        $content = 'Hello, World!';
        file_put_contents($filePath, $content);

        $hash = $this->service->hashFile($filePath);
        $expectedHash = hash('sha256', $content);

        $this->assertEquals($expectedHash, $hash);
    }

    /**
     * verifyHash が正しいハッシュで true を返す
     */
    public function test_verify_hash_returns_true_for_matching_hash(): void
    {
        $filePath = $this->tempDir.'/test.txt';
        $content = 'Test content for verification';
        file_put_contents($filePath, $content);

        $hash = $this->service->hashFile($filePath);

        $this->assertTrue($this->service->verifyHash($filePath, $hash));
    }

    /**
     * verifyHash が改ざんされたファイルで false を返す
     */
    public function test_verify_hash_returns_false_for_tampered_file(): void
    {
        $filePath = $this->tempDir.'/test.txt';
        file_put_contents($filePath, 'Original content');
        $hash = $this->service->hashFile($filePath);

        // ファイルを改ざん
        file_put_contents($filePath, 'Tampered content');

        $this->assertFalse($this->service->verifyHash($filePath, $hash));
    }

    /**
     * verifyHash が不正なハッシュで false を返す
     */
    public function test_verify_hash_returns_false_for_wrong_hash(): void
    {
        $filePath = $this->tempDir.'/test.txt';
        file_put_contents($filePath, 'Test content');

        $this->assertFalse($this->service->verifyHash($filePath, 'invalid_hash_value'));
    }

    /**
     * SHA-384 アルゴリズムが動作する
     */
    public function test_hash_file_with_sha384(): void
    {
        $filePath = $this->tempDir.'/test.txt';
        $content = 'SHA-384 test';
        file_put_contents($filePath, $content);

        $hash = $this->service->hashFile($filePath, 'sha384');
        $expectedHash = hash('sha384', $content);

        $this->assertEquals($expectedHash, $hash);
    }

    /**
     * SHA-512 アルゴリズムが動作する
     */
    public function test_hash_file_with_sha512(): void
    {
        $filePath = $this->tempDir.'/test.txt';
        $content = 'SHA-512 test';
        file_put_contents($filePath, $content);

        $hash = $this->service->hashFile($filePath, 'sha512');
        $expectedHash = hash('sha512', $content);

        $this->assertEquals($expectedHash, $hash);
    }

    /**
     * サポートされていないアルゴリズムで例外をスロー
     */
    public function test_hash_file_throws_for_unsupported_algorithm(): void
    {
        $filePath = $this->tempDir.'/test.txt';
        file_put_contents($filePath, 'test');

        $this->expectException(\InvalidArgumentException::class);
        $this->service->hashFile($filePath, 'md5');
    }

    /**
     * 存在しないファイルで例外をスロー
     */
    public function test_hash_file_throws_for_nonexistent_file(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->hashFile($this->tempDir.'/nonexistent.txt');
    }

    /**
     * 存在しないファイルの verifyHash が false を返す
     */
    public function test_verify_hash_returns_false_for_nonexistent_file(): void
    {
        $this->assertFalse(
            $this->service->verifyHash($this->tempDir.'/nonexistent.txt', 'somehash')
        );
    }

    /**
     * getSupportedAlgorithms が期待する配列を返す
     */
    public function test_get_supported_algorithms(): void
    {
        $algorithms = $this->service->getSupportedAlgorithms();

        $this->assertContains('sha256', $algorithms);
        $this->assertContains('sha384', $algorithms);
        $this->assertContains('sha512', $algorithms);
        $this->assertCount(3, $algorithms);
    }

    /**
     * 異なるアルゴリズム間の verifyHash が false を返す
     */
    public function test_verify_hash_fails_across_algorithms(): void
    {
        $filePath = $this->tempDir.'/test.txt';
        file_put_contents($filePath, 'Cross-algorithm test');

        $sha256Hash = $this->service->hashFile($filePath, 'sha256');

        $this->assertFalse($this->service->verifyHash($filePath, $sha256Hash, 'sha512'));
    }
}
