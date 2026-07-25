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

namespace App\Http\Controllers\Admin\Settings\Security;

use App\DTO\FileIntegrity\ScanTargetDTO;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\FileIntegrityAudit;
use App\Services\FileIntegrityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminSecurityIntegrityController extends AdminLoggedInController
{
    protected FileIntegrityService $fileIntegrityService;

    public function __construct(FileIntegrityService $fileIntegrityService)
    {
        parent::__construct();
        $this->fileIntegrityService = $fileIntegrityService;
    }

    /**
     * File integrity settings page
     */
    public function index()
    {
        $latestAudit = FileIntegrityAudit::getLatestCore();
        $hasBaseline = $this->fileIntegrityService->hasBaseline();
        $baselineMeta = $hasBaseline ? $this->fileIntegrityService->getBaselineMeta() : null;

        // Get recent scan history (with pagination)
        $recentAudits = FileIntegrityAudit::where('scope', FileIntegrityAudit::SCOPE_CORE)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Date format according to locale
        $dateFormat = app()->getLocale() === 'ja' ? __('http/controllers/admin/settings/security/admin_security_integrity_controller.date_format_year_month_day_time') : 'Y-m-d H:i';

        // Format baseline date
        if (isset($baselineMeta['generated_at'])) {
            $baselineMeta['formatted_generated_at'] = \Carbon\Carbon::parse($baselineMeta['generated_at'])->format($dateFormat);
        }

        $this->viewParams['dateFormat'] = $dateFormat;
        $this->viewParams['latestAudit'] = $latestAudit;
        $this->viewParams['hasBaseline'] = $hasBaseline;
        $this->viewParams['baselineMeta'] = $baselineMeta;
        $this->viewParams['recentAudits'] = $recentAudits;
        $this->addIntegrityConstants();
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.security.integrity');

        return view('admin.settings.security.integrity', $this->viewParams);
    }

    /**
     * Execute file integrity scan
     */
    public function scan(Request $request)
    {
        $audit = $this->fileIntegrityService->scanCore(
            FileIntegrityAudit::TRIGGER_MANUAL,
            FileIntegrityAudit::INITIATED_BY_USER,
            Auth::id()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $audit->status,
                'summary' => $audit->summary,
                'has_issues' => $audit->hasIssues(),
                'audit_id' => $audit->id,
            ]);
        }

        $message = $audit->hasIssues()
            ? __('admin/settings/security/integrity.scan_completed_with_issues', ['count' => $audit->total_files_scanned])
            : __('admin/settings/security/integrity.scan_completed_ok', ['count' => $audit->total_files_scanned]);

        return redirect()->route('admin.settings.security.integrity')
            ->with($audit->hasIssues() ? 'warning' : 'success', $message);
    }

    /**
     * Regenerate baseline
     */
    public function regenerateBaseline(Request $request)
    {
        $result = $this->fileIntegrityService->regenerateBaseline(
            ScanTargetDTO::core(),
            FileIntegrityAudit::TRIGGER_MANUAL,
            FileIntegrityAudit::INITIATED_BY_USER,
            Auth::id()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $result,
                'message' => $result
                    ? __('admin/settings/security/integrity.baseline_regenerated')
                    : __('admin/settings/security/integrity.baseline_regeneration_failed'),
            ]);
        }

        return redirect()->route('admin.settings.security.integrity')
            ->with($result ? 'success' : 'error', $result
                ? __('admin/settings/security/integrity.baseline_regenerated')
                : __('admin/settings/security/integrity.baseline_regeneration_failed'));
    }

    /**
     * Display scan details
     */
    public function show(FileIntegrityAudit $audit)
    {
        $this->viewParams['audit'] = $audit;
        $this->viewParams['resultPayload'] = $audit->result_payload ?? [];
        $this->addIntegrityConstants();

        return view('admin.settings.security.integrity-show', $this->viewParams);
    }

    /**
     * Delete scan history
     */
    public function destroy(FileIntegrityAudit $audit)
    {
        $audit->delete();

        return redirect()->route('admin.settings.security.integrity')
            ->with('success', __('admin/settings/security/integrity.audit_deleted'));
    }

    /**
     * Bulk delete old scan history
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1',
        ]);

        $cutoffDate = now()->subDays($request->days);

        $count = FileIntegrityAudit::where('scope', FileIntegrityAudit::SCOPE_CORE)
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        return redirect()->route('admin.settings.security.integrity')
            ->with('success', __('admin/settings/security/integrity.audits_deleted', ['count' => $count]));
    }

    private function addIntegrityConstants(): void
    {
        $this->viewParams['integrityStatusOk'] = FileIntegrityAudit::STATUS_OK;
        $this->viewParams['integrityStatusWarning'] = FileIntegrityAudit::STATUS_WARNING;
        $this->viewParams['triggerManual'] = FileIntegrityAudit::TRIGGER_MANUAL;
        $this->viewParams['triggerSchedule'] = FileIntegrityAudit::TRIGGER_SCHEDULE;
        $this->viewParams['triggerInstall'] = FileIntegrityAudit::TRIGGER_INSTALL;
        $this->viewParams['triggerUpdate'] = FileIntegrityAudit::TRIGGER_UPDATE;
    }
}
