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
     * デフォルトでは CoreSignatureVerifier にバインドされるテスト
     */
    public function test_default_binding_is_core_signature_verifier(): void
    {
        $verifier = $this->app->make(SignatureVerifierInterface::class);

        $this->assertInstanceOf(CoreSignatureVerifier::class, $verifier);
    }

    /**
     * CoreSignatureVerifier の isAvailable() が true を返すテスト
     *
     * 旧スタブ実装では false を返していたが、コアに Ed25519 検証を
     * 実装したため常に利用可能となった。
     */
    public function test_core_signature_verifier_is_available(): void
    {
        $verifier = $this->app->make(SignatureVerifierInterface::class);

        $this->assertTrue($verifier->isAvailable());
    }
}
