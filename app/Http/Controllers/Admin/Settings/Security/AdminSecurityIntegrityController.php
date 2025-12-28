<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\Http\Controllers\Admin\Settings\Security;

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
     * ファイル整合性設定ページ
     */
    public function index()
    {
        $this->addBreadcrumb(null, __('admin/nav.settings.text'));
        $this->addBreadcrumb('admin.settings.security.index', __('admin/nav.settings.security.text'));
        $this->addBreadcrumb(null, __('admin/nav.settings.security.integrity'));
        $this->setBreadcrumbs();
        
        $latestAudit = FileIntegrityAudit::getLatestCore();
        $hasBaseline = $this->fileIntegrityService->hasBaseline();
        $baselineMeta = $hasBaseline ? $this->fileIntegrityService->getBaselineMeta() : null;
        
        // 直近のスキャン履歴を取得
        $recentAudits = FileIntegrityAudit::where('scope', FileIntegrityAudit::SCOPE_CORE)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $this->viewParams['latestAudit'] = $latestAudit;
        $this->viewParams['hasBaseline'] = $hasBaseline;
        $this->viewParams['baselineMeta'] = $baselineMeta;
        $this->viewParams['recentAudits'] = $recentAudits;

        return view('admin.settings.security.integrity', $this->viewParams);
    }

    /**
     * ファイル整合性スキャンを実行
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
     * ベースラインを再生成
     */
    public function regenerateBaseline(Request $request)
    {
        $result = $this->fileIntegrityService->regenerateBaseline(
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
     * スキャン詳細を表示
     */
    public function show(FileIntegrityAudit $audit)
    {
        $this->viewParams['audit'] = $audit;

        return view('admin.settings.security.integrity-show', $this->viewParams);
    }
}
