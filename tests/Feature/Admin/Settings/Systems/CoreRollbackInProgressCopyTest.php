<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Tests\Feature\Admin\Settings\Systems;

use App\Http\Controllers\Admin\Settings\Systems\AdminSystemUpdatesController;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pins the in-progress placeholder copy for the core update vs. core
 * rollback flows. Round 4 Finding E: the placeholder that shows while
 * a rollback is running rendered as "v— へアップグレード中です" —
 * both the version placeholder was blank AND the wording said
 * "アップグレード / upgrade" even though the running operation was
 * a rollback. The fix (PR-L) branches the lang keys on
 * `$info['operation']` and passes the rollback target version through
 * the flag payload so the placeholder can render it.
 *
 * We test the response method directly (via reflection) rather than
 * going through the HTTP endpoint because the placeholder is
 * intentionally not routed through the admin middleware stack — the
 * update process may be mid-swap of resources/views/ and a normal
 * request path could 500. Calling the response method with a fake
 * flag payload exercises the exact code path the running placeholder
 * uses, without the overhead of standing up middleware / auth.
 */
class CoreRollbackInProgressCopyTest extends TestCase
{
    public function test_rollback_placeholder_uses_rollback_copy_and_target_version(): void
    {
        $html = $this->renderPlaceholder([
            'started_at' => time() - 30,
            'operation' => 'rollback',
            'target_version' => '0.3.2-dryrun-7',
            'started_by_id' => null,
        ]);

        // Rollback-specific title.
        $this->assertStringContainsString(
            (string) __('admin/settings/systems/updates.core.rollback.in_progress_title'),
            $html,
            'Rollback in-progress placeholder must use the rollback-specific title, '
            .'not the update-side one (Round 4 Finding E).'
        );
        // Rollback-specific message with target version interpolated.
        $this->assertStringContainsString(
            'v0.3.2-dryrun-7',
            $html,
            'Rollback placeholder must interpolate the target version rather than '
            .'falling back to the "—" placeholder.'
        );
        // Should NOT show the update-side title.
        $this->assertStringNotContainsString(
            (string) __('admin/settings/systems/updates.core.in_progress_title'),
            $html
        );
    }

    public function test_update_placeholder_still_uses_update_copy(): void
    {
        $html = $this->renderPlaceholder([
            'started_at' => time() - 30,
            'target_version' => '0.3.3-dryrun-8',
            'started_by_id' => null,
        ]);

        $this->assertStringContainsString(
            (string) __('admin/settings/systems/updates.core.in_progress_title'),
            $html
        );
        $this->assertStringContainsString('v0.3.3-dryrun-8', $html);
        // Rollback title must not leak into the update flow.
        $this->assertStringNotContainsString(
            (string) __('admin/settings/systems/updates.core.rollback.in_progress_title'),
            $html
        );
    }

    public function test_rollback_placeholder_still_renders_when_target_version_is_missing(): void
    {
        // Belt-and-braces: even if a caller forgets to include
        // target_version in the flag payload the placeholder must not
        // 500 — it should degrade to the "—" placeholder rather than
        // an interpolation error.
        $html = $this->renderPlaceholder([
            'started_at' => time(),
            'operation' => 'rollback',
            'started_by_id' => null,
        ]);

        $this->assertStringContainsString('—', $html);
    }

    public function test_new_lang_keys_are_present_in_both_locales(): void
    {
        // Every render-time key path the response method reads must
        // resolve in every locale — otherwise Laravel returns the key
        // itself as a string, which would surface as raw
        // "admin/settings/systems/updates.core.rollback.in_progress_title"
        // to the operator.
        foreach (['en', 'ja'] as $locale) {
            app()->setLocale($locale);

            $title = (string) __('admin/settings/systems/updates.core.rollback.in_progress_title');
            $message = (string) __('admin/settings/systems/updates.core.rollback.in_progress_message', ['version' => '1.0.0']);

            $this->assertNotSame(
                'admin/settings/systems/updates.core.rollback.in_progress_title',
                $title,
                "Missing lang key for locale '{$locale}': core.rollback.in_progress_title"
            );
            $this->assertNotSame(
                'admin/settings/systems/updates.core.rollback.in_progress_message',
                $message,
                "Missing lang key for locale '{$locale}': core.rollback.in_progress_message"
            );
            $this->assertStringContainsString(
                'v1.0.0',
                $message,
                "Locale '{$locale}': rollback in_progress_message must interpolate :version"
            );
        }
    }

    private function renderPlaceholder(array $info): string
    {
        $controller = app(AdminSystemUpdatesController::class);
        $method = new ReflectionMethod(AdminSystemUpdatesController::class, 'coreUpdateInProgressResponse');

        return (string) $method->invoke($controller, $info)->getContent();
    }
}
