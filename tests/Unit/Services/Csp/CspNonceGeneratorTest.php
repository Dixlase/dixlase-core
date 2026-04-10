<?php

namespace Tests\Unit\Services\Csp;

use App\Services\Csp\CspNonceGenerator;
use Tests\TestCase;

class CspNonceGeneratorTest extends TestCase
{
    private CspNonceGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new CspNonceGenerator();
    }

    public function test_get_nonce_returns_string(): void
    {
        $nonce = $this->generator->getNonce();

        $this->assertIsString($nonce);
        $this->assertNotEmpty($nonce);
    }

    public function test_nonce_is_consistent_within_request(): void
    {
        $first = $this->generator->getNonce();
        $second = $this->generator->getNonce();

        $this->assertEquals($first, $second);
    }

    public function test_nonce_changes_after_reset(): void
    {
        $first = $this->generator->getNonce();
        $this->generator->resetNonce();
        $second = $this->generator->getNonce();

        $this->assertNotEquals($first, $second);
    }

    public function test_get_nonce_directive_format(): void
    {
        $directive = $this->generator->getNonceDirective();

        $this->assertStringStartsWith("'nonce-", $directive);
        $this->assertStringEndsWith("'", $directive);
    }

    public function test_get_nonce_attribute_format(): void
    {
        $attribute = $this->generator->getNonceAttribute();

        $this->assertStringStartsWith('nonce="', $attribute);
        $this->assertStringEndsWith('"', $attribute);
    }

    public function test_nonce_has_sufficient_length(): void
    {
        $nonce = $this->generator->getNonce();

        // base64 エンコードされたランダムバイト — 最低16文字以上
        $this->assertGreaterThanOrEqual(16, strlen($nonce));
    }
}
