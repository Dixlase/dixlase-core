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

namespace App\Http\Controllers\Admin\Front;

use App\Actors\MemberActor;
use App\Helpers\AdminHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\FrontPage;
use App\Models\FrontPageRevision;
use App\Presenters\Admin\RevisionDiffPresenter;
use App\Services\FrontPageRevisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * フロントページ リビジョン一覧・差分表示・復元コントローラー
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
     * リビジョン一覧
     */
    public function index(): View|RedirectResponse
    {
        $this->setDescription(__('admin/front/revisions.index.description'));

        $frontPage = FrontPage::findByType('main_content');
        if (! $frontPage) {
            return redirect()->route('admin.front.create');
        }

        $revisions = $frontPage->revisions()->with('creator')->paginate(20);

        $this->viewParams['frontPage'] = $frontPage;
        $this->viewParams['revisions'] = $revisions;
        $this->viewParams['typeLabels'] = $this->typeLabels();

        return view('admin.front.revisions.index', $this->viewParams);
    }

    /**
     * リビジョン詳細（現行との差分表示）
     */
    public function show(int $id): View|RedirectResponse
    {
        $frontPage = FrontPage::findByType('main_content');
        if (! $frontPage) {
            return redirect()->route('admin.front.create');
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
     * リビジョン復元
     */
    public function restore(int $id): RedirectResponse
    {
        $frontPage = FrontPage::findByType('main_content');
        if (! $frontPage) {
            return redirect()->route('admin.front.create');
        }

        $revision = FrontPageRevision::query()
            ->where('front_page_id', $frontPage->id)
            ->findOrFail($id);

        $actor = new MemberActor(AdminHelper::getMember());
        $this->revisionService->restore($revision, userId: $actor->getActorId());

        return redirect()
            ->route('admin.front.revisions.index')
            ->with('success', __('admin/front/revisions.restore_success'));
    }

    /**
     * リビジョンのメモを更新する
     */
    public function updateNote(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $frontPage = FrontPage::findByType('main_content');
        if (! $frontPage) {
            return redirect()->route('admin.front.create');
        }

        $revision = FrontPageRevision::query()
            ->where('front_page_id', $frontPage->id)
            ->findOrFail($id);

        $revision->update(['note' => $data['note'] ?? null]);

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
