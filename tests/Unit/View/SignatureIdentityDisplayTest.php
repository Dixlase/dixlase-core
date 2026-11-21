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

namespace Tests\Unit\View;

use Tests\TestCase;

/**
 * No surface shows the signer name the signature sidecar claims (#1098).
 *
 * `signed_by` and `signed_at` sit in the detached sidecar, outside the signed
 * payload, so a signer can write anything there and the signature still
 * verifies. What is covered is the key: a signature only verifies against the
 * key its `key_id` names. So each surface shows `key_display` -- the name
 * core's pinned list gives that key, or the key_id itself.
 *
 * These are source assertions because the four surfaces are independent and
 * each one is one line long. A test that renders them would not notice a
 * fifth one being added the old way.
 */
class SignatureIdentityDisplayTest extends TestCase
{
    /**
     * The Blade partials that print the signature block.
     *
     * @return array<int, string>
     */
    private function partials(): array
    {
        return [
            'views/admin/settings/plugins/partials/scan-details.blade.php',
            'views/admin/settings/themes/partials/scan-details.blade.php',
            'views/admin/settings/themes/partials/permission-modal.blade.php',
        ];
    }

    public function test_no_partial_prints_the_claimed_signer(): void
    {
        foreach ($this->partials() as $partial) {
            $source = file_get_contents(resource_path($partial));

            $this->assertStringNotContainsString(
                "signature']['signed_by'",
                $source,
                $partial.' prints the signer name the sidecar claims. Show '
                .'key_display instead, which the signature covers (#1098).'
            );
            $this->assertStringContainsString(
                "signature']['key_display'",
                $source,
                $partial.' should show the key the signature verified against.'
            );
        }
    }

    public function test_the_scan_result_builder_shows_the_key_not_the_claim(): void
    {
        $js = file_get_contents(resource_path('src/admin/settings/plugins/js/scan-result-builder.js'));

        $this->assertStringNotContainsString(
            'audit.signature_signer',
            $js,
            'The scan-result builder reads the claimed signer name again (#1098). '
            .'The presenter hands it signature_key, which is the identity the '
            .'signature covers.'
        );
        $this->assertStringContainsString('audit.signature_key', $js);

        // This builder's output is assigned to innerHTML, and a key_id is read
        // from the extension's own manifest, so the value is escaped.
        $this->assertStringContainsString(
            'escapeHtml(signingKey)',
            $js,
            'The signing key goes into innerHTML, so it must be escaped.'
        );
        $this->assertStringContainsString('function escapeHtml(', $js);
    }

    public function test_the_presenter_hands_the_key_to_the_modal(): void
    {
        $presenter = file_get_contents(app_path('Presenters/Admin/ExtensionCardPresenter.php'));

        $this->assertStringNotContainsString(
            "'signature']['signed_by'",
            $presenter,
            'The presenter resolves the claimed signer name again (#1098).'
        );
        $this->assertStringContainsString("'signature']['key_display'", $presenter);
        $this->assertStringContainsString("'signature_key' => \$signatureKey", $presenter);
    }

    public function test_both_areas_translate_the_label_in_both_languages(): void
    {
        foreach (['plugins', 'themes'] as $area) {
            foreach (['en', 'ja'] as $locale) {
                $path = lang_path($locale.'/admin/settings/'.$area.'/index.php');
                $strings = require $path;

                $this->assertArrayHasKey(
                    'signing_key',
                    $strings['permissions'] ?? [],
                    $locale.'/'.$area.' should translate the signing-key label; the '
                    .'partials and the scan modal both ask for it.'
                );
                $this->assertNotSame(
                    '',
                    $strings['permissions']['signing_key'],
                    $locale.'/'.$area.' leaves the signing-key label empty.'
                );
            }
        }
    }
}
