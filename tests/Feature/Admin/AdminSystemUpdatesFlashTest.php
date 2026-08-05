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
}
