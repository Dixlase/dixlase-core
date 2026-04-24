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
use Plugins\DixlaseDevKit\App\Signing\Contracts\VerifierInterface;
use Plugins\DixlaseDevKit\App\Signing\CoreSignatureVerifierAdapter;
use Plugins\DixlaseDevKit\App\Signing\SignatureResult;
use Tests\TestCase;

class CoreSignatureVerifierAdapterTest extends TestCase
{
    /**
     * isAvailable() が true を返すテスト
     */
    public function test_is_available_returns_true(): void
    {
        $mockVerifier = $this->createMock(VerifierInterface::class);
        $adapter = new CoreSignatureVerifierAdapter($mockVerifier);

        $this->assertTrue($adapter->isAvailable());
    }

    /**
     * valid な SignatureResult を正しく変換するテスト
     */
    public function test_converts_valid_result(): void
    {
        $sigResult = SignatureResult::valid('dixlase-official-001', 'Dixlase Official Key');

        $mockVerifier = $this->createMock(VerifierInterface::class);
        $mockVerifier->method('verify')->willReturn($sigResult);

        $adapter = new CoreSignatureVerifierAdapter($mockVerifier);
        $result = $adapter->verify('test-plugin');

        $this->assertTrue($result->isValid());
        $this->assertEquals('dixlase-official-001', $result->keyId);
        $this->assertEquals('Dixlase Official Key', $result->keyLabel);
        $this->assertEquals('official', $result->type);
    }

    /**
     * unsigned な SignatureResult を正しく変換するテスト
     */
    public function test_converts_unsigned_result(): void
    {
        $sigResult = SignatureResult::unsigned();

        $mockVerifier = $this->createMock(VerifierInterface::class);
        $mockVerifier->method('verify')->willReturn($sigResult);

        $adapter = new CoreSignatureVerifierAdapter($mockVerifier);
        $result = $adapter->verify('test-plugin');

        $this->assertTrue($result->isUnsigned());
    }

    /**
     * invalid な SignatureResult を正しく変換するテスト
     */
    public function test_converts_invalid_result(): void
    {
        $sigResult = SignatureResult::invalid('Tampered data', ['error1']);

        $mockVerifier = $this->createMock(VerifierInterface::class);
        $mockVerifier->method('verify')->willReturn($sigResult);

        $adapter = new CoreSignatureVerifierAdapter($mockVerifier);
        $result = $adapter->verify('test-plugin');

        $this->assertTrue($result->isInvalid());
        $this->assertEquals(['error1'], $result->errors);
    }

    /**
     * expired な SignatureResult を正しく変換するテスト
     */
    public function test_converts_expired_result(): void
    {
        $sigResult = SignatureResult::expired('key-001', 'Expired Key');

        $mockVerifier = $this->createMock(VerifierInterface::class);
        $mockVerifier->method('verify')->willReturn($sigResult);

        $adapter = new CoreSignatureVerifierAdapter($mockVerifier);
        $result = $adapter->verify('test-plugin');

        $this->assertEquals(SignatureVerificationResult::STATUS_EXPIRED, $result->status);
        $this->assertEquals('key-001', $result->keyId);
    }

    /**
     * unknown_key な SignatureResult を正しく変換するテスト
     */
    public function test_converts_unknown_key_result(): void
    {
        $sigResult = SignatureResult::unknownKey('unknown-key-id');

        $mockVerifier = $this->createMock(VerifierInterface::class);
        $mockVerifier->method('verify')->willReturn($sigResult);

        $adapter = new CoreSignatureVerifierAdapter($mockVerifier);
        $result = $adapter->verify('test-plugin');

        $this->assertEquals(SignatureVerificationResult::STATUS_UNKNOWN_KEY, $result->status);
        $this->assertEquals('unknown-key-id', $result->keyId);
    }

    /**
     * error な SignatureResult を正しく変換するテスト
     */
    public function test_converts_error_result(): void
    {
        $sigResult = SignatureResult::error('Something went wrong', ['err']);

        $mockVerifier = $this->createMock(VerifierInterface::class);
        $mockVerifier->method('verify')->willReturn($sigResult);

        $adapter = new CoreSignatureVerifierAdapter($mockVerifier);
        $result = $adapter->verify('test-plugin');

        $this->assertEquals(SignatureVerificationResult::STATUS_ERROR, $result->status);
        $this->assertEquals(['err'], $result->errors);
    }

    /**
     * 署名タイプの判定: verified (marketplace)
     */
    public function test_determines_marketplace_as_verified(): void
    {
        $sigResult = SignatureResult::valid('marketplace-plugin-001', 'Marketplace Key');

        $mockVerifier = $this->createMock(VerifierInterface::class);
        $mockVerifier->method('verify')->willReturn($sigResult);

        $adapter = new CoreSignatureVerifierAdapter($mockVerifier);
        $result = $adapter->verify('test-plugin');

        $this->assertEquals('verified', $result->type);
    }

    /**
     * 署名タイプの判定: partner
     */
    public function test_determines_partner_type(): void
    {
        $sigResult = SignatureResult::valid('partner-acme-001', 'Acme Partner Key');

        $mockVerifier = $this->createMock(VerifierInterface::class);
        $mockVerifier->method('verify')->willReturn($sigResult);

        $adapter = new CoreSignatureVerifierAdapter($mockVerifier);
        $result = $adapter->verify('test-plugin');

        $this->assertEquals('partner', $result->type);
    }

    /**
     * 検証中に例外が発生した場合はエラーを返すテスト
     */
    public function test_returns_error_on_exception(): void
    {
        $mockVerifier = $this->createMock(VerifierInterface::class);
        $mockVerifier->method('verify')->willThrowException(
            new \RuntimeException('Plugin directory not found')
        );

        $adapter = new CoreSignatureVerifierAdapter($mockVerifier);
        $result = $adapter->verify('test-plugin');

        $this->assertEquals(SignatureVerificationResult::STATUS_ERROR, $result->status);
        $this->assertStringContainsString('Plugin directory not found', $result->message);
    }

    /**
     * null キーIDの場合は type が null になるテスト
     */
    public function test_null_key_id_returns_null_type(): void
    {
        $sigResult = SignatureResult::unsigned();

        $mockVerifier = $this->createMock(VerifierInterface::class);
        $mockVerifier->method('verify')->willReturn($sigResult);

        $adapter = new CoreSignatureVerifierAdapter($mockVerifier);
        $result = $adapter->verify('test-plugin');

        $this->assertNull($result->type);
    }
}
