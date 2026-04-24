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

namespace Tests\Unit\DTO\Plugin;

use App\DTO\Plugin\SignatureVerificationResult;
use Tests\TestCase;

class SignatureVerificationResultTest extends TestCase
{
    /**
     * コンストラクタで全フィールドが設定されるテスト
     */
    public function test_constructor_sets_all_fields(): void
    {
        $result = new SignatureVerificationResult(
            status: 'valid',
            type: 'official',
            signedBy: 'exc-D',
            signedAt: '2025-01-01T00:00:00Z',
            keyId: 'dixlase-official-001',
            keyLabel: 'Dixlase Official Key',
            message: 'Valid signature',
            errors: ['test error'],
        );

        $this->assertEquals('valid', $result->status);
        $this->assertEquals('official', $result->type);
        $this->assertEquals('exc-D', $result->signedBy);
        $this->assertEquals('2025-01-01T00:00:00Z', $result->signedAt);
        $this->assertEquals('dixlase-official-001', $result->keyId);
        $this->assertEquals('Dixlase Official Key', $result->keyLabel);
        $this->assertEquals('Valid signature', $result->message);
        $this->assertEquals(['test error'], $result->errors);
    }

    /**
     * isValid() のテスト
     */
    public function test_is_valid(): void
    {
        $valid = new SignatureVerificationResult(status: SignatureVerificationResult::STATUS_VALID);
        $invalid = new SignatureVerificationResult(status: SignatureVerificationResult::STATUS_INVALID);

        $this->assertTrue($valid->isValid());
        $this->assertFalse($invalid->isValid());
    }

    /**
     * isUnsigned() のテスト
     */
    public function test_is_unsigned(): void
    {
        $unsigned = new SignatureVerificationResult(status: SignatureVerificationResult::STATUS_UNSIGNED);
        $valid = new SignatureVerificationResult(status: SignatureVerificationResult::STATUS_VALID);

        $this->assertTrue($unsigned->isUnsigned());
        $this->assertFalse($valid->isUnsigned());
    }

    /**
     * isInvalid() のテスト
     */
    public function test_is_invalid(): void
    {
        $invalid = new SignatureVerificationResult(status: SignatureVerificationResult::STATUS_INVALID);
        $valid = new SignatureVerificationResult(status: SignatureVerificationResult::STATUS_VALID);

        $this->assertTrue($invalid->isInvalid());
        $this->assertFalse($valid->isInvalid());
    }

    /**
     * unsigned() ファクトリメソッドのテスト
     */
    public function test_unsigned_factory(): void
    {
        $result = SignatureVerificationResult::unsigned();

        $this->assertEquals(SignatureVerificationResult::STATUS_UNSIGNED, $result->status);
        $this->assertNotNull($result->message);
        $this->assertNull($result->keyId);
    }

    /**
     * unsigned() にカスタムメッセージを渡すテスト
     */
    public function test_unsigned_factory_with_custom_message(): void
    {
        $result = SignatureVerificationResult::unsigned('カスタムメッセージ');

        $this->assertEquals('カスタムメッセージ', $result->message);
    }

    /**
     * pending() ファクトリメソッドのテスト
     */
    public function test_pending_factory(): void
    {
        $result = SignatureVerificationResult::pending();

        $this->assertEquals(SignatureVerificationResult::STATUS_PENDING, $result->status);
        $this->assertNotNull($result->message);
    }

    /**
     * valid() ファクトリメソッドのテスト
     */
    public function test_valid_factory(): void
    {
        $result = SignatureVerificationResult::valid(
            keyId: 'dixlase-official-001',
            keyLabel: 'Official Key',
            signedBy: 'exc-D',
            signedAt: '2025-01-01',
            type: 'official',
        );

        $this->assertTrue($result->isValid());
        $this->assertEquals('dixlase-official-001', $result->keyId);
        $this->assertEquals('Official Key', $result->keyLabel);
        $this->assertEquals('exc-D', $result->signedBy);
        $this->assertEquals('2025-01-01', $result->signedAt);
        $this->assertEquals('official', $result->type);
    }

    /**
     * invalid() ファクトリメソッドのテスト
     */
    public function test_invalid_factory(): void
    {
        $result = SignatureVerificationResult::invalid('改ざんされています', ['detail']);

        $this->assertTrue($result->isInvalid());
        $this->assertEquals('改ざんされています', $result->message);
        $this->assertEquals(['detail'], $result->errors);
    }

    /**
     * error() ファクトリメソッドのテスト
     */
    public function test_error_factory(): void
    {
        $result = SignatureVerificationResult::error('検証失敗', ['err1']);

        $this->assertEquals(SignatureVerificationResult::STATUS_ERROR, $result->status);
        $this->assertEquals('検証失敗', $result->message);
        $this->assertEquals(['err1'], $result->errors);
    }

    /**
     * toArray() のテスト
     */
    public function test_to_array(): void
    {
        $result = new SignatureVerificationResult(
            status: 'valid',
            type: 'official',
            signedBy: 'exc-D',
            signedAt: '2025-01-01',
            keyId: 'key-001',
            keyLabel: 'Key Label',
            message: 'OK',
            errors: [],
        );

        $array = $result->toArray();

        $this->assertEquals('valid', $array['status']);
        $this->assertEquals('official', $array['type']);
        $this->assertEquals('exc-D', $array['signed_by']);
        $this->assertEquals('2025-01-01', $array['signed_at']);
        $this->assertEquals('key-001', $array['key_id']);
        $this->assertEquals('Key Label', $array['key_label']);
        $this->assertEquals('OK', $array['message']);
        $this->assertEquals([], $array['errors']);
    }

    /**
     * jsonSerialize() が toArray() と同じ結果を返すテスト
     */
    public function test_json_serialize_matches_to_array(): void
    {
        $result = SignatureVerificationResult::valid('key-001');

        $this->assertEquals($result->toArray(), $result->jsonSerialize());
    }

    /**
     * json_encode で正しくシリアライズされるテスト
     */
    public function test_json_encode(): void
    {
        $result = SignatureVerificationResult::unsigned();
        $json = json_encode($result);

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals('unsigned', $decoded['status']);
    }

    /**
     * デフォルト値のテスト
     */
    public function test_default_values(): void
    {
        $result = new SignatureVerificationResult(status: 'unsigned');

        $this->assertNull($result->type);
        $this->assertNull($result->signedBy);
        $this->assertNull($result->signedAt);
        $this->assertNull($result->keyId);
        $this->assertNull($result->keyLabel);
        $this->assertNull($result->message);
        $this->assertEquals([], $result->errors);
    }
}
