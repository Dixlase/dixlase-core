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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Http\Controllers\Admin\Privacy;

use App\Enums\MemberRole;
use App\Enums\PluginPrivacy\DeletionMode;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\Member;
use App\Services\Privacy\UserPrivacyEraser;
use App\Services\Privacy\UserPrivacyExporter;
use App\Services\Site\SiteContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Privacy data export / deletion stub for site operators.
 *
 * This controller is intentionally minimal: it lets a super-admin look
 * up a member by id or email and dispatch an export ZIP download or a
 * deletion run through the privacy aggregator services. A full
 * subject-access workflow (audit trail, notifications, dry-run preview)
 * is left for a later iteration.
 */
class UserDataController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Render the search + actions page.
     */
    public function index(Request $request): Response
    {
        $this->authorizeSuperAdmin($request);

        $search = trim((string) $request->query('search', ''));
        $member = $this->resolveMember($search);

        $this->viewParams['search'] = $search;
        $this->viewParams['member'] = $member;
        $this->viewParams['scopeOptions'] = [
            'site' => __('admin/privacy/users.scope.site'),
            'network' => __('admin/privacy/users.scope.network'),
        ];
        $this->viewParams['deletionModeOptions'] = [
            DeletionMode::Anonymize->value => __('admin/privacy/users.deletion_mode.anonymize'),
            DeletionMode::SoftDelete->value => __('admin/privacy/users.deletion_mode.soft_delete'),
            DeletionMode::HardDelete->value => __('admin/privacy/users.deletion_mode.hard_delete'),
        ];

        return response()->view('admin.privacy.users.index', $this->viewParams);
    }

    /**
     * Build a privacy export ZIP for the given member and stream it
     * back as an attachment download.
     */
    public function export(
        Request $request,
        int $id,
        UserPrivacyExporter $exporter,
        SiteContext $siteContext,
    ): BinaryFileResponse {
        $this->authorizeSuperAdmin($request);

        $member = Member::findOrFail($id);
        $siteId = $this->resolveSiteId($request, $siteContext);

        $zipPath = $exporter->exportToZip((int) $member->id, $siteId);

        $scope = $siteId === null ? 'network' : 'site'.$siteId;
        $downloadName = "privacy-export-member{$member->id}-{$scope}.zip";

        return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);
    }

    /**
     * Execute deletion for the given member and redirect back to the
     * index page with a status banner.
     */
    public function delete(
        Request $request,
        int $id,
        UserPrivacyEraser $eraser,
        SiteContext $siteContext,
    ): RedirectResponse {
        $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'mode' => ['required', 'string'],
            'scope' => ['required', 'string', 'in:site,network'],
            'confirm' => ['required', 'accepted'],
        ]);

        $mode = DeletionMode::tryFrom((string) $validated['mode']);
        if ($mode === null) {
            return redirect()
                ->route('admin.privacy.users.index', ['search' => (string) $id])
                ->with('error', __('admin/privacy/users.errors.invalid_mode'));
        }

        $member = Member::findOrFail($id);
        $siteId = $validated['scope'] === 'site' ? $siteContext->currentSiteId() : null;

        $results = $eraser->erase((int) $member->id, $mode, $siteId);

        $totalDeleted = 0;
        $totalAnonymized = 0;
        $errors = [];
        foreach ($results as $r) {
            $totalDeleted += $r->deletedRecords;
            $totalAnonymized += $r->anonymizedRecords;
            if ($r->hasErrors()) {
                foreach ($r->errors as $message) {
                    $errors[] = $r->providerKey.': '.$message;
                }
            }
        }

        return redirect()
            ->route('admin.privacy.users.index')
            ->with('status', __('admin/privacy/users.status.deletion_completed', [
                'deleted' => $totalDeleted,
                'anonymized' => $totalAnonymized,
                'errors' => count($errors),
            ]))
            ->with('errors_detail', $errors);
    }

    /**
     * Look up a member by numeric id or email substring.
     */
    private function resolveMember(string $search): ?Member
    {
        if ($search === '') {
            return null;
        }

        if (ctype_digit($search)) {
            return Member::query()->withTrashed()->find((int) $search);
        }

        return Member::query()
            ->withTrashed()
            ->where('email', $search)
            ->orWhere('account_name', $search)
            ->first();
    }

    /**
     * Resolve the requested scope into a concrete siteId or null.
     */
    private function resolveSiteId(Request $request, SiteContext $siteContext): ?int
    {
        $scope = $request->query('scope', 'site');

        return $scope === 'network' ? null : $siteContext->currentSiteId();
    }

    /**
     * Restrict every endpoint to super admins. This stub is intended
     * for site operators only; a finer-grained gate (per-action
     * permissions, audit trail) belongs in a later iteration.
     */
    private function authorizeSuperAdmin(Request $request): void
    {
        $user = $request->user('member');
        if ($user === null) {
            abort(403, 'Privacy operations require super-admin role.');
        }

        $role = $user->role instanceof MemberRole ? $user->role : MemberRole::tryFrom((int) $user->role);
        if ($role !== MemberRole::SUPER_ADMIN) {
            abort(403, 'Privacy operations require super-admin role.');
        }
    }
}
