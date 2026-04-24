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
