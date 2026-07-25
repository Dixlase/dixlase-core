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

namespace App\Http\Controllers\Admin\Front;

use App\Actors\MemberActor;
use App\Helpers\AdminHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\FrontPage;
use App\Models\FrontPageRevision;
use App\Presenters\Admin\RevisionDiffPresenter;
use App\Services\FrontPageRevisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Front page revision list, diff display, and restore controller
 */
class AdminFrontRevisionController extends AdminLoggedInController
{
    public function __construct(
        protected FrontPageRevisionService $revisionService,
        protected RevisionDiffPresenter $diffPresenter,
    ) {
        parent::__construct();
    }

    /**
     * Revision list
     */
    public function index(): View|RedirectResponse
    {
        $this->setDescription(__('admin/front/revisions.index.description'));

        $frontPage = FrontPage::findByType('main_content');
        if (! $frontPage) {
            return redirect()->route('admin.front.index');
        }

        $revisions = $frontPage->revisions()->with('creator')->paginate(20);

        $this->viewParams['frontPage'] = $frontPage;
        $this->viewParams['revisions'] = $revisions;
        $this->viewParams['typeLabels'] = $this->typeLabels();
        $this->viewParams['retention'] = $this->revisionService->getRetentionCount();
        $this->viewParams['protectedCount'] = $this->revisionService->countProtected($frontPage);

        return view('admin.front.revisions.index', $this->viewParams);
    }

    /**
     * Revision details (diff display with current version)
     */
    public function show(int $id): View|RedirectResponse
    {
        $frontPage = FrontPage::findByType('main_content');
        if (! $frontPage) {
            return redirect()->route('admin.front.index');
        }

        $revision = FrontPageRevision::query()
            ->where('front_page_id', $frontPage->id)
            ->with('creator')
            ->findOrFail($id);

        $currentSnapshot = $this->revisionService->buildSnapshot($frontPage);
        $revisionSnapshot = $revision->snapshot;

        $fields = ['title', 'content', 'custom_js', 'custom_css'];
        $diffs = [];
        foreach ($fields as $field) {
            $left = (string) ($revisionSnapshot[$field] ?? '');
            $right = (string) ($currentSnapshot[$field] ?? '');
            if (! $this->diffPresenter->hasChanges($left, $right)) {
                continue;
            }
            $diffs[$field] = $this->diffPresenter->buildSideBySide($left, $right);
        }

        $metaDiffs = [];
        foreach (['storage_type', 'editor_type', 'status'] as $field) {
            $left = $revisionSnapshot[$field] ?? null;
            $right = $currentSnapshot[$field] ?? null;
            if ($left !== $right) {
                $metaDiffs[$field] = ['old' => $left, 'new' => $right];
            }
        }

        $this->viewParams['frontPage'] = $frontPage;
        $this->viewParams['revision'] = $revision;
        $this->viewParams['diffs'] = $diffs;
        $this->viewParams['metaDiffs'] = $metaDiffs;
        $this->viewParams['hasChanges'] = ! empty($diffs) || ! empty($metaDiffs);
        $this->viewParams['typeLabels'] = $this->typeLabels();

        return view('admin.front.revisions.show', $this->viewParams);
    }

    /**
     * Restore revision
     */
    public function restore(int $id): RedirectResponse
    {
        $frontPage = FrontPage::findByType('main_content');
        if (! $frontPage) {
            return redirect()->route('admin.front.index');
        }

        $revision = FrontPageRevision::query()
            ->where('front_page_id', $frontPage->id)
            ->findOrFail($id);

        $actor = new MemberActor(AdminHelper::getMember());
        (new \App\Actions\FrontPage\RestoreFrontPageRevisionAction($revision, app(\App\Services\RevisionService::class)))
            ->execute($actor, []);

        return redirect()
            ->route('admin.front.revisions.index')
            ->with('success', __('admin/front/revisions.restore_success'));
    }

    /**
     * Toggle revision protection flag
     */
    public function toggleProtection(int $id): RedirectResponse
    {
        $frontPage = FrontPage::findByType('main_content');
        if (! $frontPage) {
            return redirect()->route('admin.front.index');
        }

        $revision = FrontPageRevision::query()
            ->where('front_page_id', $frontPage->id)
            ->findOrFail($id);

        $actor = new MemberActor(AdminHelper::getMember());
        (new \App\Actions\FrontPage\ToggleFrontPageRevisionProtectionAction($revision))
            ->execute($actor, []);

        return back()->with(
            'success',
            $revision->fresh()->is_protected
                ? __('admin/front/revisions.protect_enabled')
                : __('admin/front/revisions.protect_disabled')
        );
    }

    /**
     * Update revision memo
     */
    public function updateNote(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $frontPage = FrontPage::findByType('main_content');
        if (! $frontPage) {
            return redirect()->route('admin.front.index');
        }

        $revision = FrontPageRevision::query()
            ->where('front_page_id', $frontPage->id)
            ->findOrFail($id);

        $actor = new MemberActor(AdminHelper::getMember());
        (new \App\Actions\FrontPage\UpdateFrontPageRevisionNoteAction($revision))
            ->execute($actor, $data);

        return redirect()
            ->route('admin.front.revisions.show', $revision->id)
            ->with('success', __('admin/front/revisions.note_updated'));
    }

    /**
     * @return array<string, string>
     */
    private function typeLabels(): array
    {
        return [
            FrontPageRevision::TYPE_AUTO => __('admin/front/revisions.type_auto'),
            FrontPageRevision::TYPE_MANUAL => __('admin/front/revisions.type_manual'),
            FrontPageRevision::TYPE_RESTORE_BACKUP => __('admin/front/revisions.type_restore_backup'),
        ];
    }
}
