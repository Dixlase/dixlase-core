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

declare(strict_types=1);

namespace Tests\Unit\Services\Plugin;

use App\Services\Plugin\AuthorityPublicKeyResolver;
use App\Services\Plugin\CoreSignatureVerifier;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Build output is gitignored by every official extension, so it sat outside
 * the comparison: the compiled JavaScript the application serves could be
 * replaced and the extension still verified. Only PHP was compared regardless
 * of the exclusion ({@see SignatureGitignoreBypassTest}).
 *
 * The signed files[] decides it now. When it names a path the walk would skip,
 * the directory holding that path joins the comparison. The tell is inside the
 * signed payload, so it cannot be removed without breaking the signature.
 *
 * Both sides of the migration are pinned here: a manifest that names no such
 * path behaves exactly as it did, which is what lets the extensions be
 * re-signed one at a time rather than all on one day.
 *
 * The fixture lives under storage/framework/testing, never in plugins/.
 */
class SignatureCoversSignedBuildOutputTest extends TestCase
{
    private string $plugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugin = storage_path('framework/testing/signed-plugin-'.uniqid());
        File::ensureDirectoryExists($this->plugin.'/app');
        File::ensureDirectoryExists($this->plugin.'/resources/assets/js');
        File::put($this->plugin.'/app/Provider.php', "<?php\n");
        File::put($this->plugin.'/resources/assets/js/app.js', "console.log('one');\n");
        File::put($this->plugin.'/resources/assets/manifest.json', '{"app.js":{"file":"js/app.js"}}');
        // What every official extension does, and the reason the output was
        // never compared.
        File::put($this->plugin.'/.gitignore', "/resources/assets/*\n");
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
            public function signedFiles(string $path, array $expected = []): array
            {
                return $this->collectFileHashes($path, 'plugin.json', $expected);
            }

            /** @return array{valid: bool, mismatched: array<string>, missing: array<string>, extra: array<string>} */
            public function check(string $path, array $expected): array
            {
                return $this->verifyFileHashes($path, $expected);
            }

            /** @return array<string, string> */
            public function signedIncludingBuildOutput(string $path): array
            {
                $files = $this->collectFileHashes($path);

                foreach (['resources/assets/js/app.js', 'resources/assets/manifest.json'] as $relPath) {
                    $files[$relPath] = 'sha256:'.hash_file('sha256', $path.'/'.$relPath);
                }

                ksort($files);

                return $files;
            }
        };
    }

    public function test_a_manifest_that_signs_no_build_output_behaves_as_before(): void
    {
        $verifier = $this->verifier();

        $signed = $verifier->signedFiles($this->plugin);

        $this->assertArrayNotHasKey('resources/assets/js/app.js', $signed);
        $this->assertTrue($verifier->check($this->plugin, $signed)['valid']);
    }

    public function test_an_old_manifest_still_verifies_after_the_build_output_changes(): void
    {
        $verifier = $this->verifier();
        $signed = $verifier->signedFiles($this->plugin);

        File::put($this->plugin.'/resources/assets/js/app.js', "console.log('two');\n");

        // Unchanged behaviour, deliberately: this is the state every extension
        // is in until it is re-signed, and core must not turn it invalid.
        $this->assertTrue($verifier->check($this->plugin, $signed)['valid']);
    }

    public function test_a_signed_manifest_notices_a_replaced_bundle(): void
    {
        $verifier = $this->verifier();
        $signed = $verifier->signedIncludingBuildOutput($this->plugin);

        $this->assertTrue($verifier->check($this->plugin, $signed)['valid']);

        File::put($this->plugin.'/resources/assets/js/app.js', "console.log('replaced');\n");

        $result = $verifier->check($this->plugin, $signed);

        $this->assertFalse($result['valid']);
        $this->assertContains('resources/assets/js/app.js', $result['mismatched']);
    }

    public function test_a_signed_manifest_notices_a_file_added_to_the_build_output(): void
    {
        $verifier = $this->verifier();
        $signed = $verifier->signedIncludingBuildOutput($this->plugin);

        File::put($this->plugin.'/resources/assets/js/extra.js', "console.log('added');\n");

        $result = $verifier->check($this->plugin, $signed);

        $this->assertFalse($result['valid']);
        $this->assertContains('resources/assets/js/extra.js', $result['extra']);
    }

    public function test_a_signed_build_output_file_that_is_deleted_is_reported_missing(): void
    {
        $verifier = $this->verifier();
        $signed = $verifier->signedIncludingBuildOutput($this->plugin);

        File::delete($this->plugin.'/resources/assets/manifest.json');

        $result = $verifier->check($this->plugin, $signed);

        $this->assertFalse($result['valid']);
        $this->assertContains('resources/assets/manifest.json', $result['missing']);
    }

    public function test_widening_the_gitignore_cannot_hide_signed_build_output(): void
    {
        $verifier = $this->verifier();
        $signed = $verifier->signedIncludingBuildOutput($this->plugin);

        // .gitignore is outside the signed payload, so an actor who can write
        // the directory can rewrite it. It must not buy them anything.
        File::put($this->plugin.'/.gitignore', "/resources\n");
        File::put($this->plugin.'/resources/assets/js/app.js', "console.log('replaced');\n");

        $result = $verifier->check($this->plugin, $signed);

        $this->assertFalse($result['valid']);
        $this->assertContains('resources/assets/js/app.js', $result['mismatched']);
    }

    public function test_the_whole_ignored_tree_is_covered_even_with_nothing_signed_at_its_root(): void
    {
        // Some extensions ship no file directly in the build output root --
        // their output starts a level down. The covered area must not depend
        // on that, or a directory added beside the listed one stays outside.
        File::delete($this->plugin.'/resources/assets/manifest.json');

        $verifier = $this->verifier();
        $signed = $verifier->signedFiles($this->plugin);
        $signed['resources/assets/js/app.js'] = 'sha256:'.hash_file('sha256', $this->plugin.'/resources/assets/js/app.js');
        ksort($signed);

        $this->assertTrue($verifier->check($this->plugin, $signed)['valid']);

        File::ensureDirectoryExists($this->plugin.'/resources/assets/css');
        File::put($this->plugin.'/resources/assets/css/added.css', "body{}\n");

        $result = $verifier->check($this->plugin, $signed);

        $this->assertFalse($result['valid']);
        $this->assertContains('resources/assets/css/added.css', $result['extra']);
    }

    public function test_a_listed_path_the_rules_keep_does_not_widen_the_comparison(): void
    {
        // app/ is not ignored, so listing a file there must contribute nothing:
        // walking up must stop where the rules stop.
        $verifier = $this->verifier();
        $signed = $verifier->signedFiles($this->plugin);

        $this->assertArrayHasKey('app/Provider.php', $signed);

        File::append($this->plugin.'/.gitignore', "/node_modules\n");
        File::ensureDirectoryExists($this->plugin.'/node_modules/pkg');
        File::put($this->plugin.'/node_modules/pkg/index.js', "module.exports={};\n");

        // node_modules stays out, as it always has.
        $this->assertTrue($verifier->check($this->plugin, $signed)['valid']);
    }
}
