<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

namespace Tests\Feature\Security;

use App\Models\ExtensionSource;
use App\Services\Extension\SourceVerifier;
use ReflectionMethod;
use Tests\TestCase;

/**
 * SourceVerifier resolved its Ed25519 public key with
 * env('EXTENSION_SOURCE_PUBLIC_KEY').
 *
 * Once `php artisan config:cache` has run -- which the deploy documentation
 * instructs operators to do -- .env is no longer loaded and env() returns
 * null. Measured in this environment, with the key present in the
 * environment at cache time and absent at read time:
 *
 *     config() : 'dGVzdGtleQ=='
 *     env()    : NULL
 *
 * So signature verification lost its key on every deployment that followed
 * the documented caching step, and verify() answered 'no_key' for every
 * source. The failure is safe -- nothing is treated as verified -- but silent:
 * the check appeared configured and was not running.
 *
 * Core's own PHPStan setup already has a rule for this shape
 * (larastan.noEnvCallsOutsideOfConfig). It sits in the ignore list of the
 * blocking gate, which is how a call with security consequences stayed among
 * ~56 harmless ones.
 */
class SourceVerifierKeyResolutionTest extends TestCase
{
    private function resolveKey(): ?string
    {
        $method = new ReflectionMethod(SourceVerifier::class, 'resolvePublicKey');
        $method->setAccessible(true);

        return $method->invoke(app(SourceVerifier::class));
    }

    /**
     * The property that matters: the key comes from config, which survives
     * config:cache, rather than from env(), which does not.
     */
    public function test_key_is_read_from_config(): void
    {
        // A valid 32-byte Ed25519 public key, base64-encoded.
        $key = base64_encode(str_repeat("\x01", 32));
        config(['extension-sources.public_key' => $key]);

        $this->assertSame(
            str_repeat("\x01", 32),
            $this->resolveKey(),
            'The verifier must resolve its key through config so it survives config:cache.'
        );
    }

    public function test_source_does_not_call_env_for_the_key(): void
    {
        $source = file_get_contents(base_path('app/Services/Extension/SourceVerifier.php'));

        $this->assertStringNotContainsString(
            "env('EXTENSION_SOURCE_PUBLIC_KEY')",
            $source,
            'env() returns null under config:cache, so reading the key that way disables verification in production.'
        );

        $this->assertStringContainsString(
            "config('extension-sources.public_key')",
            $source,
            'The key must be resolved through the config repository.'
        );
    }

    /**
     * Absence has to keep failing closed. "No key" must never be mistaken for
     * "verified".
     */
    public function test_missing_key_leaves_verification_unverified(): void
    {
        config(['extension-sources.public_key' => null]);

        $this->assertNull(
            $this->resolveKey(),
            'A missing key must resolve to null rather than to some default.'
        );

        // A source that claims to carry a signature, so verify() gets past the
        // "unsigned" branch and reaches the key lookup.
        $source = new ExtensionSource([
            'name' => 'example',
            'official_signature' => base64_encode(str_repeat("\x02", 64)),
        ]);

        $result = app(SourceVerifier::class)->verify($source);

        $this->assertFalse($result['verified'], 'Without a key nothing may be reported as verified.');
        $this->assertSame('no_key', $result['status']);
    }

    /**
     * An empty string is what an operator gets from a blank .env line. It has
     * to be treated as "not configured", not handed to sodium as a key.
     */
    public function test_empty_key_is_treated_as_missing(): void
    {
        config(['extension-sources.public_key' => '']);

        $this->assertNull($this->resolveKey());
    }

    /**
     * The config entry has to exist, otherwise the call site resolves to null
     * no matter what the operator puts in .env.
     */
    public function test_config_entry_is_declared(): void
    {
        $config = require base_path('config/extension-sources.php');

        $this->assertArrayHasKey(
            'public_key',
            $config,
            'config/extension-sources.php must declare public_key for the env value to reach the verifier.'
        );
    }
}
