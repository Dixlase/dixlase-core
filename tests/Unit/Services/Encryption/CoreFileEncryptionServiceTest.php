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

namespace Tests\Unit\Services\Encryption;

use App\Contracts\Encryption\FileEncryptionServiceInterface;
use App\DTO\Encryption\EncryptionResultDTO;
use App\Exceptions\DecryptionException;
use App\Services\Encryption\CoreFileEncryptionService;
use Tests\TestCase;

class CoreFileEncryptionServiceTest extends TestCase
{
    private CoreFileEncryptionService $service;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CoreFileEncryptionService();
        $this->tempDir = sys_get_temp_dir().'/dixlase_encryption_test_'.uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // テスト用ディレクトリを削除
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
     * FileEncryptionServiceInterface を実装していることを確認
     */
    public function test_implements_interface(): void
    {
        $this->assertInstanceOf(FileEncryptionServiceInterface::class, $this->service);
    }

    /**
     * ファイルの暗号化→復号でオリジナルと一致
     */
    public function test_encrypt_and_decrypt_restores_original(): void
    {
        $originalContent = 'This is a test file for encryption. 日本語テスト。';
        $sourcePath = $this->tempDir.'/original.txt';
        $encryptedPath = $this->tempDir.'/encrypted.bin';
        $decryptedPath = $this->tempDir.'/decrypted.txt';

        file_put_contents($sourcePath, $originalContent);

        $key = $this->service->generateKey();

        $result = $this->service->encryptFile($sourcePath, $encryptedPath, $key);
        $this->assertTrue($result->success);
        $this->assertFileExists($encryptedPath);
        $this->assertNotEquals($originalContent, file_get_contents($encryptedPath));

        $this->service->decryptFile($encryptedPath, $decryptedPath, $key);
        $this->assertFileExists($decryptedPath);
        $this->assertEquals($originalContent, file_get_contents($decryptedPath));
    }

    /**
     * APP_KEY をデフォルトキーとして暗号化・復号できる
     */
    public function test_encrypt_and_decrypt_with_app_key(): void
    {
        $originalContent = 'Content encrypted with APP_KEY';
        $sourcePath = $this->tempDir.'/original.txt';
        $encryptedPath = $this->tempDir.'/encrypted.bin';
        $decryptedPath = $this->tempDir.'/decrypted.txt';

        file_put_contents($sourcePath, $originalContent);

        $result = $this->service->encryptFile($sourcePath, $encryptedPath);
        $this->assertTrue($result->success);

        $this->service->decryptFile($encryptedPath, $decryptedPath);
        $this->assertEquals($originalContent, file_get_contents($decryptedPath));
    }

    /**
     * 不正なキーでの復号が失敗する
     */
    public function test_decrypt_with_wrong_key_fails(): void
    {
        $sourcePath = $this->tempDir.'/original.txt';
        $encryptedPath = $this->tempDir.'/encrypted.bin';
        $decryptedPath = $this->tempDir.'/decrypted.txt';

        file_put_contents($sourcePath, 'Secret content');

        $key1 = $this->service->generateKey();
        $key2 = $this->service->generateKey();

        $this->service->encryptFile($sourcePath, $encryptedPath, $key1);

        $this->expectException(DecryptionException::class);
        $this->service->decryptFile($encryptedPath, $decryptedPath, $key2);
    }

    /**
     * isEncrypted がマジックバイトで正しく判定
     */
    public function test_is_encrypted_detects_encrypted_files(): void
    {
        $sourcePath = $this->tempDir.'/original.txt';
        $encryptedPath = $this->tempDir.'/encrypted.bin';

        file_put_contents($sourcePath, 'Test content');
        $this->service->encryptFile($sourcePath, $encryptedPath);

        $this->assertTrue($this->service->isEncrypted($encryptedPath));
        $this->assertFalse($this->service->isEncrypted($sourcePath));
    }

    /**
     * isEncrypted が存在しないファイルで false を返す
     */
    public function test_is_encrypted_returns_false_for_nonexistent_file(): void
    {
        $this->assertFalse($this->service->isEncrypted($this->tempDir.'/nonexistent.bin'));
    }

    /**
     * generateKey が毎回異なるキーを生成
     */
    public function test_generate_key_produces_unique_keys(): void
    {
        $key1 = $this->service->generateKey();
        $key2 = $this->service->generateKey();

        $this->assertNotEquals($key1, $key2);
        $this->assertNotEmpty($key1);
        $this->assertNotEmpty($key2);
    }

    /**
     * getAlgorithm が aes-256-gcm を返す
     */
    public function test_get_algorithm_returns_aes_256_gcm(): void
    {
        $this->assertEquals('aes-256-gcm', $this->service->getAlgorithm());
    }

    /**
     * 存在しないファイルの暗号化が失敗結果を返す
     */
    public function test_encrypt_nonexistent_file_returns_failure(): void
    {
        $result = $this->service->encryptFile(
            $this->tempDir.'/nonexistent.txt',
            $this->tempDir.'/encrypted.bin',
        );

        $this->assertFalse($result->success);
        $this->assertNotEmpty($result->error);
    }

    /**
     * 存在しないファイルの復号が例外をスロー
     */
    public function test_decrypt_nonexistent_file_throws_exception(): void
    {
        $this->expectException(DecryptionException::class);
        $this->service->decryptFile(
            $this->tempDir.'/nonexistent.bin',
            $this->tempDir.'/decrypted.txt',
        );
    }

    /**
     * EncryptionResultDTO の success ファクトリメソッド
     */
    public function test_encryption_result_dto_success(): void
    {
        $result = EncryptionResultDTO::success('/path/to/file', 'aes-256-gcm', 1000, 1034);

        $this->assertTrue($result->success);
        $this->assertEquals('/path/to/file', $result->outputPath);
        $this->assertEquals('aes-256-gcm', $result->algorithm);
        $this->assertEquals(1000, $result->originalSize);
        $this->assertEquals(1034, $result->encryptedSize);
        $this->assertNull($result->error);
    }

    /**
     * EncryptionResultDTO の failure ファクトリメソッド
     */
    public function test_encryption_result_dto_failure(): void
    {
        $result = EncryptionResultDTO::failure('Something went wrong');

        $this->assertFalse($result->success);
        $this->assertEquals('Something went wrong', $result->error);
        $this->assertEquals(0, $result->originalSize);
        $this->assertEquals(0, $result->encryptedSize);
    }

    /**
     * 暗号化されたファイルサイズがオリジナルより大きい（ヘッダー分）
     */
    public function test_encrypted_file_is_larger_than_original(): void
    {
        $sourcePath = $this->tempDir.'/original.txt';
        $encryptedPath = $this->tempDir.'/encrypted.bin';

        file_put_contents($sourcePath, str_repeat('A', 1000));
        $result = $this->service->encryptFile($sourcePath, $encryptedPath);

        $this->assertTrue($result->success);
        $this->assertGreaterThan($result->originalSize, $result->encryptedSize);
    }
}
