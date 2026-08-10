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

namespace Tests\Unit\Services;

use App\Helpers\EmailVerificationHelper;
use App\Services\SystemNotificationService;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pins down symbols that Core calls but had drifted away from.
 *
 * Every case here corresponds to a reference that had gone stale — a rename
 * whose callers were not updated, or a missing import that silently resolved
 * into the current namespace. None of them were caught by the suite, because
 * each sat on a path that either only runs while something else is already
 * failing, or is gated by class_exists() and therefore fails by doing nothing.
 *
 * PHPStan reported all of them for months. Its CI job is continue-on-error,
 * so nobody saw it. These assertions do fail the build.
 */
class StaleSymbolReferenceTest extends TestCase
{
    /**
     * @return array<string, array{class-string, string}>
     */
    public static function methodsCalledFromErrorPaths(): array
    {
        return [
            // CaptchaFailoverService and WebhookDeadLetterService both called a
            // static ::send() that never existed. Both sit inside error
            // handling, so the fatal only appeared once something else had
            // already gone wrong — the worst place to hide one.
            'admin notification' => [SystemNotificationService::class, 'sendAdminNotification'],
            'error notification' => [SystemNotificationService::class, 'sendErrorNotification'],
        ];
    }

    #[DataProvider('methodsCalledFromErrorPaths')]
    public function test_notification_method_exists(string $class, string $method): void
    {
        $this->assertTrue(
            method_exists($class, $method),
            "{$class}::{$method}() is called from an error path. If it is renamed, the caller "
            .'must move with it: a failure there replaces an already-degraded state with a fatal.'
        );
    }

    public function test_notification_methods_are_instance_methods(): void
    {
        // The original bug was not only the name. The callers used static
        // dispatch against instance methods, so even a correctly named static
        // call would have failed.
        foreach (['sendAdminNotification', 'sendErrorNotification'] as $method) {
            $this->assertFalse(
                (new ReflectionMethod(SystemNotificationService::class, $method))->isStatic(),
                "SystemNotificationService::{$method}() is an instance method; resolve the "
                .'service from the container rather than calling it statically.'
            );
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function classesResolvedByName(): array
    {
        return [
            // Resolved through app() in FrontWelcomeController for GUI content
            // on the site's front page. Without the import it resolved to
            // App\Http\Controllers\Front\EditorManager, which does not exist.
            'editor manager' => [\App\Services\Editor\EditorManager::class],
            // Auth was unimported in ExtensionOperationService, resolving to
            // App\Services\Auth. The surrounding catch was \Exception, so the
            // resulting \Error escaped it.
            'auth facade' => [\Illuminate\Support\Facades\Auth::class],
            // The verification-completed notification was renamed; the callers
            // kept pointing at MemberVerificationCompletedNotification, which
            // has never existed as a file.
            'member verified notification' => [\App\Notifications\MemberVerifiedNotification::class],
            'admin member verified notification' => [\App\Notifications\AdminMemberVerifiedNotification::class],
        ];
    }

    #[DataProvider('classesResolvedByName')]
    public function test_class_referenced_by_core_exists(string $class): void
    {
        $this->assertTrue(
            class_exists($class),
            "{$class} is referenced by Core. A missing class here does not always crash — where "
            .'the call site gates on class_exists() the feature just goes quiet, which is harder '
            .'to notice than a failure.'
        );
    }

    public function test_verification_notification_mapping_resolves_for_core_contexts(): void
    {
        // The admin and default branches are Core's own; they must resolve.
        // The 'user' branch belongs to the DixlaseUsers plugin and is
        // deliberately absent here, so it is not asserted.
        $helper = new EmailVerificationHelper();

        foreach (['admin', 'anything-else'] as $context) {
            foreach (['getVerificationCompletedNotificationClass', 'getAdminVerifiedNotificationClass'] as $resolver) {
                $method = new ReflectionMethod($helper, $resolver);
                $method->setAccessible(true);
                $class = $method->invoke($helper, $context);

                $this->assertTrue(
                    class_exists($class),
                    "{$resolver}('{$context}') resolved to {$class}, which does not exist. The "
                    .'call sites gate on class_exists(), so this sends no mail rather than raising.'
                );
            }
        }
    }
}
