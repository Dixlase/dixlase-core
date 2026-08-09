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

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\Settings\Systems\AdminSystemUpdatesController;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pins the System Updates completion flash so a ROLLBACK announces itself as a
 * rollback and not as an update. The web UI runs core update/rollback detached
 * and cannot flash directly; the detached command writes a one-shot
 * SystemUpdateFlash record and index() turns it into a session flash via
 * buildUpdateCompleteFlash(). Before this was fixed, CoreRollback wrote no
 * record at all (so a completed rollback showed no banner), and the controller
 * had only the update branch.
 *
 * buildUpdateCompleteFlash() is pure (translation only, no instance state), so
 * the controller is built without its constructor to avoid routing/middleware
 * setup that is irrelevant here.
 */
class AdminSystemUpdatesFlashTest extends TestCase
{
    private function buildFlash(array $result): string
    {
        $controller = (new ReflectionClass(AdminSystemUpdatesController::class))
            ->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($controller, 'buildUpdateCompleteFlash');
        $method->setAccessible(true);

        return (string) $method->invoke($controller, $result);
    }

    public function test_core_rollback_result_uses_the_rollback_message(): void
    {
        $this->app->setLocale('en');

        $message = $this->buildFlash([
            'status' => 'success',
            'kind' => 'core',
            'operation' => 'rollback',
            'from' => '0.3.11-dryrun-16',
            'to' => '0.3.10-dryrun-15',
        ]);

        $this->assertSame(
            __('admin/settings/systems/updates.messages.core_rollback_complete', [
                'from' => '0.3.11-dryrun-16',
                'to' => '0.3.10-dryrun-15',
            ]),
            $message,
        );
        $this->assertStringContainsStringIgnoringCase('rollback', $message);
        $this->assertStringContainsString('0.3.11-dryrun-16', $message);
        $this->assertStringContainsString('0.3.10-dryrun-15', $message);
        // Must not fall back to the update wording.
        $this->assertStringNotContainsString(
            __('admin/settings/systems/updates.messages.core_update_complete', [
                'from' => '0.3.11-dryrun-16',
                'to' => '0.3.10-dryrun-15',
            ]),
            $message,
        );
    }

    public function test_core_update_result_still_uses_the_update_message(): void
    {
        $this->app->setLocale('en');

        $message = $this->buildFlash([
            'status' => 'success',
            'kind' => 'core',
            'from' => '0.3.10-dryrun-15',
            'to' => '0.3.11-dryrun-16',
        ]);

        $this->assertSame(
            __('admin/settings/systems/updates.messages.core_update_complete', [
                'from' => '0.3.10-dryrun-15',
                'to' => '0.3.11-dryrun-16',
            ]),
            $message,
        );
    }

    public function test_rollback_message_key_exists_in_both_locales(): void
    {
        foreach (['en', 'ja'] as $locale) {
            $this->app->setLocale($locale);
            $key = 'admin/settings/systems/updates.messages.core_rollback_complete';
            $this->assertNotSame($key, __($key), "Missing core_rollback_complete for locale {$locale}");
        }
    }

    public function test_single_plugin_update_links_the_rollback_hint_to_the_plugin_detail(): void
    {
        $this->app->setLocale('en');

        $message = $this->buildFlash([
            'status' => 'success',
            'kind' => 'extension',
            'updated_plugins' => ['Cookie'],
            'updated_plugin_slugs' => ['dixlase-cookie'],
            'updated_themes' => [],
            'updated_theme_slugs' => [],
        ]);

        // Names the plugin, carries the rollback hint, and links straight to
        // that one plugin's detail page (where the rollback button lives).
        $this->assertStringContainsString('Cookie', $message);
        $this->assertStringContainsStringIgnoringCase('roll back', $message);
        $this->assertStringContainsString(
            route('admin.settings.plugins.show', ['slug' => 'dixlase-cookie']),
            $message,
        );
        // Uses the "detail" label, not the "master" label. (The detail URL
        // contains the master URL as a path prefix, so assert on the label.)
        $this->assertStringContainsString(__('admin/settings/systems/updates.messages.rollback_hint_plugin_detail'), $message);
        $this->assertStringNotContainsString(__('admin/settings/systems/updates.messages.rollback_hint_plugin_master'), $message);
    }

    public function test_multiple_plugins_update_links_the_rollback_hint_to_the_plugin_master(): void
    {
        $this->app->setLocale('en');

        $message = $this->buildFlash([
            'status' => 'success',
            'kind' => 'extension',
            'updated_plugins' => ['Cookie', 'SEO'],
            'updated_plugin_slugs' => ['dixlase-cookie', 'dixlase-seo'],
            'updated_themes' => [],
            'updated_theme_slugs' => [],
        ]);

        // Two plugins → a single detail link cannot serve both, so the hint
        // points at the plugin master list.
        $this->assertStringContainsString(route('admin.settings.plugins.index'), $message);
        $this->assertStringNotContainsString(
            route('admin.settings.plugins.show', ['slug' => 'dixlase-cookie']),
            $message,
        );
    }

    public function test_extension_display_names_are_html_escaped_in_the_flash(): void
    {
        $this->app->setLocale('en');

        $message = $this->buildFlash([
            'status' => 'success',
            'kind' => 'extension',
            'updated_plugins' => ['<script>x</script>'],
            'updated_plugin_slugs' => ['evil-plugin'],
            'updated_themes' => [],
            'updated_theme_slugs' => [],
        ]);

        // The flash is rendered unescaped, so a malicious display name must be
        // neutralised at build time.
        $this->assertStringNotContainsString('<script>', $message);
        $this->assertStringContainsString('&lt;script&gt;', $message);
    }

    public function test_rollback_hint_keys_exist_in_both_locales(): void
    {
        foreach (['en', 'ja'] as $locale) {
            $this->app->setLocale($locale);
            foreach ([
                'update_complete_rollback_hint',
                'rollback_hint_plugin_detail',
                'rollback_hint_plugin_master',
                'rollback_hint_theme_detail',
                'rollback_hint_theme_master',
            ] as $suffix) {
                $key = "admin/settings/systems/updates.messages.{$suffix}";
                $this->assertNotSame($key, __($key), "Missing {$suffix} for locale {$locale}");
            }
        }
    }
}
