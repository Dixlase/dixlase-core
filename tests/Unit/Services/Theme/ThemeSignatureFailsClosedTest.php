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

namespace Tests\Unit\Services\Theme;

use App\Services\Theme\ThemeHealthScorer;
use App\Services\Theme\ThemePermissionService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Themes have no signing pipeline and no signature verifier. The permission
 * service used to report "pending_verification" as soon as a theme shipped a
 * signature.sig, and derived an "official" type from a key_id the theme wrote
 * into its own theme.json. The health scorer only penalised "unsigned" and
 * "invalid", so such a theme passed the Strict preset's signature requirement
 * and showed an official badge without any signature being checked.
 */
class ThemeSignatureFailsClosedTest extends TestCase
{
    private string $originalBasePath;

    private string $sandbox;

    protected function setUp(): void
    {
        parent::setUp();

        // Point base_path() at a throwaway tree so the fixture never lands in
        // the repository's real themes/ directory.
        $this->originalBasePath = $this->app->basePath();
        $this->sandbox = storage_path('framework/testing/theme-signature-'.uniqid());
        File::ensureDirectoryExists($this->sandbox.'/themes/ForgedTheme');
        $this->app->setBasePath($this->sandbox);

        File::put($this->sandbox.'/themes/ForgedTheme/theme.json', json_encode([
            'name' => 'Forged Theme',
            'slug' => 'forged-theme',
            'signing' => ['key_id' => 'dixlase-authority-2026'],
        ]));
        File::put($this->sandbox.'/themes/ForgedTheme/signature.sig', json_encode([
            'key_id' => 'dixlase-authority-2026',
            'signed_by' => 'Dixlase',
            'signature' => 'not-a-real-signature',
        ]));
    }

    protected function tearDown(): void
    {
        $this->app->setBasePath($this->originalBasePath);
        File::deleteDirectory($this->sandbox);

        parent::tearDown();
    }

    public function test_a_theme_shipping_its_own_signature_file_is_reported_unsigned(): void
    {
        $info = app(ThemePermissionService::class)->getSignatureInfo('forged-theme');

        $this->assertSame('unsigned', $info['status']);
        $this->assertNull($info['type'], 'no "official" type may be derived from an unverified key_id');
        $this->assertTrue($info['unverified_signature_present'] ?? false);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unverifiedStatuses(): array
    {
        return [
            'unsigned' => ['unsigned'],
            'pending verification' => ['pending_verification'],
            'unknown status' => ['something-new'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('unverifiedStatuses')]
    public function test_anything_but_a_valid_signature_counts_as_unsigned(string $status): void
    {
        $permissions = $this->createStub(ThemePermissionService::class);
        $permissions->method('getSignatureInfo')->willReturn(['status' => $status]);

        $scorer = new class($permissions) extends ThemeHealthScorer
        {
            public function signatureIssues(): array
            {
                return $this->evaluateSignature('any', []);
            }
        };

        $types = array_map(fn ($issue) => $issue->type, $scorer->signatureIssues());

        $this->assertSame(['signature_unsigned'], $types);
    }
}
