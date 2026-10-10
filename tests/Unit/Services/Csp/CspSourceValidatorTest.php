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

namespace Tests\Unit\Services\Csp;

use App\Services\Csp\CspSourceValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Shape checks for CSP sources contributed by extensions (#494)
 */
class CspSourceValidatorTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function acceptedProvider(): array
    {
        return [
            'https host' => ['script-src', 'https://cdn.example.com'],
            'bare host' => ['connect-src', 'api.example.com'],
            'wildcard subdomain' => ['connect-src', 'https://*.example.com'],
            'host with port' => ['connect-src', 'wss://ws.example.com:8443'],
            'host with any port' => ['img-src', 'https://img.example.com:*'],
            'host with path' => ['script-src', 'https://cdn.example.com/lib/x.js'],
            'self' => ['frame-src', "'self'"],
            'nonce placeholder' => ['script-src', "'nonce'"],
            'sha256 hash' => ['style-src', "'sha256-47DEQpj8HBSa+/TImW+5JCeuQeRkm5NMpJWZG3hSuFU='"],
            'data in img-src' => ['img-src', 'data:'],
            'data in font-src' => ['font-src', 'data:'],
            'blob in worker-src' => ['worker-src', 'blob:'],
            'uppercase directive' => ['SCRIPT-SRC', 'https://cdn.example.com'],
        ];
    }

    #[DataProvider('acceptedProvider')]
    public function test_accepts_concrete_sources(string $directive, string $value): void
    {
        $this->assertNull((new CspSourceValidator())->rejection($directive, $value));
    }

    /**
     * @return array<string, array{string, mixed, string}>
     */
    public static function rejectedProvider(): array
    {
        return [
            'unsafe-inline' => ['script-src', "'unsafe-inline'", 'unsafe_keyword'],
            'unsafe-inline in style-src' => ['style-src', "'unsafe-inline'", 'unsafe_keyword'],
            'unsafe-eval' => ['script-src', "'unsafe-eval'", 'unsafe_keyword'],
            'unsafe-hashes' => ['script-src', "'unsafe-hashes'", 'unsafe_keyword'],
            'wasm-unsafe-eval' => ['script-src', "'wasm-unsafe-eval'", 'unsafe_keyword'],
            'strict-dynamic' => ['script-src', "'strict-dynamic'", 'keyword_not_allowed'],
            'none' => ['img-src', "'none'", 'keyword_not_allowed'],
            'static nonce' => ['script-src', "'nonce-abc123'", 'static_nonce'],
            'hash outside script/style' => ['img-src', "'sha256-abc='", 'keyword_not_allowed'],
            'bare star' => ['img-src', '*', 'wildcard_any'],
            'scheme star' => ['script-src', 'https://*', 'wildcard_any'],
            'tld wildcard' => ['script-src', '*.com', 'wildcard_any'],
            'data in script-src' => ['script-src', 'data:', 'scheme_source'],
            'blob in script-src' => ['script-src', 'blob:', 'scheme_source'],
            'https scheme only' => ['connect-src', 'https:', 'scheme_source'],
            'data in frame-src' => ['frame-src', 'data:', 'scheme_source'],
            'directive smuggling' => ['img-src', 'https://a.example.com; script-src *', 'malformed'],
            'space separated' => ['img-src', 'https://a.example.com https://b.example.com', 'malformed'],
            'comma' => ['img-src', 'https://a.example.com,https://b.example.com', 'malformed'],
            'empty' => ['img-src', '', 'malformed'],
            'not a string' => ['img-src', ['nested'], 'not_a_string'],
            'frame-ancestors' => ['frame-ancestors', 'https://evil.example.com', 'directive_not_allowed'],
            'base-uri' => ['base-uri', 'https://evil.example.com', 'directive_not_allowed'],
            'object-src' => ['object-src', 'https://evil.example.com', 'directive_not_allowed'],
            'script-src-attr' => ['script-src-attr', "'self'", 'directive_not_allowed'],
            'report-uri' => ['report-uri', 'https://evil.example.com/r', 'directive_not_allowed'],
        ];
    }

    #[DataProvider('rejectedProvider')]
    public function test_rejects_policy_weakening_sources(string $directive, mixed $value, string $reason): void
    {
        $this->assertSame($reason, (new CspSourceValidator())->rejection($directive, $value));
    }

    public function test_filter_splits_accepted_and_rejected(): void
    {
        $result = (new CspSourceValidator())->filter([
            'script-src' => ['https://cdn.example.com', "'unsafe-inline'"],
            'frame-ancestors' => ['https://evil.example.com'],
        ]);

        $this->assertSame(['script-src' => ['https://cdn.example.com']], $result['accepted']);
        $this->assertSame([
            ['directive' => 'script-src', 'value' => "'unsafe-inline'", 'reason' => 'unsafe_keyword'],
            ['directive' => 'frame-ancestors', 'value' => 'https://evil.example.com', 'reason' => 'directive_not_allowed'],
        ], $result['rejected']);
    }
}
