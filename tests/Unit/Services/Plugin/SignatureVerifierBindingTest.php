<?php

namespace Tests\Unit\Services\Plugin;

use App\Contracts\Plugin\SignatureVerifierInterface;
use App\Services\Plugin\CoreSignatureVerifier;
use Tests\TestCase;

class SignatureVerifierBindingTest extends TestCase
{
    /**
     * SignatureVerifierInterface がコンテナにバインドされているテスト
     */
    public function test_interface_is_bound_in_container(): void
    {
        $verifier = $this->app->make(SignatureVerifierInterface::class);

        $this->assertInstanceOf(SignatureVerifierInterface::class, $verifier);
    }

    /**
     * デフォルトではCoreSignatureVerifierにバインドされるテスト
     */
    public function test_default_binding_is_core_stub(): void
    {
        $verifier = $this->app->make(SignatureVerifierInterface::class);

        $this->assertInstanceOf(CoreSignatureVerifier::class, $verifier);
    }

    /**
     * CoreSignatureVerifier の isAvailable() が false を返すテスト
     */
    public function test_core_stub_is_not_available(): void
    {
        $verifier = $this->app->make(SignatureVerifierInterface::class);

        $this->assertFalse($verifier->isAvailable());
    }
}
