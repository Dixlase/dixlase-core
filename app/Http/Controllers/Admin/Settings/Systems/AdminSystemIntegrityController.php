<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Http\Controllers\Admin\Settings\Systems;

use App\DTO\Core\CoreIntegrityResult;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\SignatureWaiver;
use App\Services\Core\CoreIntegrityVerifier;
use App\Services\Signature\SignatureWaiverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AdminSystemIntegrityController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Core integrity overview + danger zone.
     */
    public function index(CoreIntegrityVerifier $verifier, SignatureWaiverService $waivers)
    {
        $result = $verifier->cachedResult();

        $this->viewParams['result'] = $result->toArray();
        $this->viewParams['badge'] = $this->badge($result);
        $this->viewParams['changedFiles'] = $this->changedFiles($result);
        $this->viewParams['waiver'] = $this->activeWaiver($waivers);
        // Danger-zone actions (waive / remove) are gated to dev / customized installs.
        $this->viewParams['gateOpen'] = $waivers->coreMutationGateOpen();

        return view('admin::settings.systems.integrity', $this->viewParams);
    }

    /**
     * Drop the cached result so the next view recomputes.
     */
    public function recheck(): RedirectResponse
    {
        Cache::forget(CoreIntegrityVerifier::CACHE_KEY);

        return redirect()
            ->route('admin.settings.systems.integrity')
            ->with('success', __('admin/settings/systems/integrity.flash.rechecked'));
    }

    /**
     * Record an operator waiver for the core signature (gated).
     */
    public function waive(Request $request, SignatureWaiverService $waivers): RedirectResponse
    {
        if (! $waivers->coreMutationGateOpen()) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($waivers->isWaived(SignatureWaiver::SCOPE_CORE, 'core')) {
            return redirect()
                ->route('admin.settings.systems.integrity')
                ->with('warning', __('admin/settings/systems/integrity.flash.already_waived'));
        }

        $waivers->waive(
            SignatureWaiver::SCOPE_CORE,
            'core',
            $validated['reason'],
            actor: Auth::user(),
            source: 'admin',
        );

        Cache::forget(CoreIntegrityVerifier::CACHE_KEY);

        return redirect()
            ->route('admin.settings.systems.integrity')
            ->with('success', __('admin/settings/systems/integrity.flash.waived'));
    }

    /**
     * Revoke the active core waiver (not gated — restores the warning).
     */
    public function unwaive(SignatureWaiverService $waivers): RedirectResponse
    {
        $waivers->unwaive(SignatureWaiver::SCOPE_CORE, 'core', actor: Auth::user(), source: 'admin');

        Cache::forget(CoreIntegrityVerifier::CACHE_KEY);

        return redirect()
            ->route('admin.settings.systems.integrity')
            ->with('success', __('admin/settings/systems/integrity.flash.unwaived'));
    }

    /**
     * Remove the core signature (manifest + detached signature) — destructive,
     * gated. The install becomes "unsigned" until re-signed in a build env.
     */
    public function removeSignature(SignatureWaiverService $waivers): RedirectResponse
    {
        if (! $waivers->coreMutationGateOpen()) {
            abort(403);
        }

        $waivers->removeSignature(SignatureWaiver::SCOPE_CORE, 'core', actor: Auth::user(), source: 'admin');

        Cache::forget(CoreIntegrityVerifier::CACHE_KEY);

        return redirect()
            ->route('admin.settings.systems.integrity')
            ->with('success', __('admin/settings/systems/integrity.flash.signature_removed'));
    }

    /**
     * Badge presentation for the current status (color/icon + status key used
     * by the view to look up the localized label and description).
     *
     * @return array{icon: string, key: string, classes: string}
     */
    protected function badge(CoreIntegrityResult $result): array
    {
        [$color, $icon, $key] = $result->isWaived()
            ? ['yellow', 'fas fa-user-shield', 'waived']
            : match ($result->status) {
                CoreIntegrityResult::STATUS_GENUINE => ['green', 'fas fa-shield-alt', 'genuine'],
                CoreIntegrityResult::STATUS_MODIFIED => ['yellow', 'fas fa-exclamation-triangle', 'modified'],
                CoreIntegrityResult::STATUS_UNSIGNED => ['gray', 'fas fa-question-circle', 'unsigned'],
                CoreIntegrityResult::STATUS_PENDING => ['blue', 'fas fa-hourglass-half', 'pending'],
                CoreIntegrityResult::STATUS_INVALID => ['red', 'fas fa-times-circle', 'invalid'],
                default => ['red', 'fas fa-bug', 'error'],
            };

        return [
            'icon' => $icon,
            'key' => $key,
            'classes' => $this->colorClasses($color),
        ];
    }

    protected function colorClasses(string $color): string
    {
        return match ($color) {
            'green' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'red' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        };
    }

    /**
     * Flatten the changed-file lists into rows for the view.
     *
     * @return array<int, array{marker: string, label: string, path: string}>
     */
    protected function changedFiles(CoreIntegrityResult $result): array
    {
        $rows = [];
        foreach ($result->mismatched as $path) {
            $rows[] = ['marker' => 'M', 'label' => __('admin/settings/systems/integrity.diff.modified'), 'path' => $path];
        }
        foreach ($result->missing as $path) {
            $rows[] = ['marker' => '-', 'label' => __('admin/settings/systems/integrity.diff.missing'), 'path' => $path];
        }
        foreach ($result->extra as $path) {
            $rows[] = ['marker' => '+', 'label' => __('admin/settings/systems/integrity.diff.extra'), 'path' => $path];
        }

        return $rows;
    }

    /**
     * Active waiver metadata for display, or null.
     *
     * @return array{reason: ?string, by: ?string, at: ?string}|null
     */
    protected function activeWaiver(SignatureWaiverService $waivers): ?array
    {
        $waiver = $waivers->getActiveWaiver(SignatureWaiver::SCOPE_CORE, 'core');

        if ($waiver === null) {
            return null;
        }

        return [
            'reason' => $waiver->reason,
            'by' => $waiver->waived_by_label,
            'at' => optional($waiver->waived_at)->toDateTimeString(),
        ];
    }
}
