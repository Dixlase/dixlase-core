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

namespace Tests\Unit\Services\Plugin;

use App\Services\Plugin\AuthorityPublicKeyResolver;
use App\Services\Plugin\CoreSignatureVerifier;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * A signed plugin's .gitignore is not itself signed, yet it decided which files
 * the verifier hashed. Appending `config/zz.php` to it and adding that file kept
 * every signed hash valid and hid the new file from the "extra" check, while
 * PluginServiceProvider requires every config/*.php of an enabled plugin --
 * arbitrary PHP behind a green "official" badge.
 *
 * The fixture lives under storage/framework/testing, never in plugins/.
 */
class SignatureGitignoreBypassTest extends TestCase
{
    private string $plugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugin = storage_path('framework/testing/signed-plugin-'.uniqid());
        File::ensureDirectoryExists($this->plugin.'/app');
        File::ensureDirectoryExists($this->plugin.'/config');
        File::put($this->plugin.'/app/Provider.php', "<?php\n");
        File::put($this->plugin.'/config/plugin.php', "<?php return [];\n");
        File::put($this->plugin.'/.gitignore', "/build\n/node_modules\n");
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->plugin);

        parent::tearDown();
    }

    private function verifier(): CoreSignatureVerifier
    {
        return new class(app(AuthorityPublicKeyResolver::class)) extends CoreSignatureVerifier
        {
            /** @return array<string, string> */
            public function signedFiles(string $path): array
            {
                return $this->collectFileHashes($path);
            }

            /** @return array{valid: bool, mismatched: array<string>, missing: array<string>, extra: array<string>} */
            public function check(string $path, array $expected): array
            {
                return $this->verifyFileHashes($path, $expected);
            }
        };
    }

    public function test_the_untouched_plugin_verifies(): void
    {
        $verifier = $this->verifier();
        $signed = $verifier->signedFiles($this->plugin);

        $this->assertTrue($verifier->check($this->plugin, $signed)['valid']);
    }

    public function test_php_hidden_through_the_plugins_own_gitignore_is_reported_as_extra(): void
    {
        $verifier = $this->verifier();
        $signed = $verifier->signedFiles($this->plugin);

        File::append($this->plugin.'/.gitignore', "config/zz.php\n");
        File::put($this->plugin.'/config/zz.php', "<?php system(\$_GET['c'] ?? 'id');\n");

        $result = $verifier->check($this->plugin, $signed);

        $this->assertFalse($result['valid']);
        $this->assertContains('config/zz.php', $result['extra']);
    }

    public function test_php_matching_a_default_exclude_pattern_is_still_reported(): void
    {
        $verifier = $this->verifier();
        $signed = $verifier->signedFiles($this->plugin);

        // ".git" matches as a substring, so this path used to be skipped.
        File::ensureDirectoryExists($this->plugin.'/app/.github');
        File::put($this->plugin.'/app/.github/loader.php', "<?php\n");

        $this->assertContains('app/.github/loader.php', $verifier->check($this->plugin, $signed)['extra']);
    }

    public function test_ignored_non_php_output_and_node_modules_stay_out_of_the_comparison(): void
    {
        $verifier = $this->verifier();
        $signed = $verifier->signedFiles($this->plugin);

        File::ensureDirectoryExists($this->plugin.'/build');
        File::put($this->plugin.'/build/app.js', 'console.log(1);');
        File::ensureDirectoryExists($this->plugin.'/node_modules/pkg');
        File::put($this->plugin.'/node_modules/pkg/index.php', "<?php\n");

        $this->assertTrue($verifier->check($this->plugin, $signed)['valid']);
    }
}
