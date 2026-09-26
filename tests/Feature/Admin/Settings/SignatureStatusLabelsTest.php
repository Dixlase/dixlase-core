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

namespace Tests\Feature\Admin\Settings;

use App\DTO\Plugin\SignatureVerificationResult;
use Illuminate\View\Factory as ViewFactory;
use ReflectionClass;
use Tests\TestCase;

/**
 * The scan-result modal looks a signature status up in the label map the
 * audit-script partial hands to JavaScript (`labels[status] || status`), so a
 * status without an entry is printed raw — "valid" instead of "Signed". Every
 * SignatureVerificationResult::STATUS_* must be translated on both surfaces.
 */
class SignatureStatusLabelsTest extends TestCase
{
    /**
     * @return array<int, string>
     */
    private function statuses(): array
    {
        $constants = (new ReflectionClass(SignatureVerificationResult::class))->getConstants();

        $statuses = [];
        foreach ($constants as $name => $value) {
            if (str_starts_with($name, 'STATUS_') && is_string($value)) {
                $statuses[] = $value;
            }
        }

        $this->assertNotEmpty($statuses, 'No STATUS_* constants were found.');

        return $statuses;
    }

    /**
     * @return array<string, mixed>
     */
    private function auditConfig(string $view, string $scriptId): array
    {
        // The config block is inside @push('scripts'), so it never appears in
        // the view's own output — it has to be taken off the stack. Rendering a
        // view on its own flushes that stack as it finishes, so hold the render
        // open while the stack is read.
        $factory = app(ViewFactory::class);
        $factory->incrementRender();
        $factory->make($view)->render();
        $html = $factory->yieldPushContent('scripts');
        $factory->decrementRender();
        $factory->flushState();

        $pattern = '/<script id="'.preg_quote($scriptId, '/').'" type="application\/json">(.*?)<\/script>/s';
        $this->assertMatchesRegularExpression($pattern, $html, "The {$scriptId} block was not rendered.");

        preg_match($pattern, $html, $matches);
        $config = json_decode(trim($matches[1]), true);

        $this->assertIsArray($config, "The {$scriptId} block is not valid JSON.");

        return $config;
    }

    public function test_theme_scan_translates_every_signature_status(): void
    {
        $labels = $this->auditConfig('admin.settings.themes.partials.audit-script', 'theme-audit-config')['signatureLabels'] ?? [];

        foreach ($this->statuses() as $status) {
            $this->assertArrayHasKey(
                $status,
                $labels,
                "The theme scan modal has no label for the '{$status}' signature status and would print it raw."
            );
            $this->assertNotSame($status, $labels[$status], "The '{$status}' label is untranslated.");
        }
    }

    public function test_plugin_scan_translates_every_signature_status(): void
    {
        $labels = $this->auditConfig('admin.settings.plugins.partials.audit-script', 'plugin-audit-config')['signatureLabels'] ?? [];

        foreach ($this->statuses() as $status) {
            $this->assertArrayHasKey(
                $status,
                $labels,
                "The plugin scan modal has no label for the '{$status}' signature status and would print it raw."
            );
            $this->assertNotSame($status, $labels[$status], "The '{$status}' label is untranslated.");
        }
    }

    public function test_both_locales_define_every_signature_status(): void
    {
        foreach (['en', 'ja'] as $locale) {
            foreach (['themes', 'plugins'] as $area) {
                foreach ($this->statuses() as $status) {
                    $key = "admin/settings/{$area}/index.permissions.signature_{$status}";
                    $this->assertTrue(
                        \Lang::has($key, $locale),
                        "Missing translation {$key} for locale {$locale}."
                    );
                }
            }
        }
    }
}
