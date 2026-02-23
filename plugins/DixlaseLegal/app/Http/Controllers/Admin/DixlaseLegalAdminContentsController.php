<?php

/**
 * This file is part of Dixlase Legal.
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

namespace Plugins\DixlaseLegal\App\Http\Controllers\Admin;

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Services\LegalPageService;
use App\Traits\AdminInterfaceTrait;
use App\Traits\AdminLoggedInTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Plugins\DixlaseLegal\App\Http\Requests\Admin\DixlaseLegalUpdateContentRequest;
use Plugins\DixlaseLegal\App\Models\DixlaseLegalPage;

/**
 * 法務ページコンテンツ管理コントローラー
 */
class DixlaseLegalAdminContentsController extends Controller
{
    use AdminInterfaceTrait;
    use AdminLoggedInTrait;
    use AuthorizesRequests;

    public function __construct(
        private LegalPageService $legalPageService,
    ) {
        $this->initialize();
        $this->initializeAfterLogin();
    }

    /**
     * コンテンツ一覧（ページ種別 x 言語マトリックス）
     */
    public function index(): View
    {
        $pageTypes = $this->legalPageService->getPageTypes();
        $languages = config('language.languages', []);

        // 全コンテンツを一括取得
        $contents = DixlaseLegalPage::query()
            ->select(['slug', 'lang', 'title', 'status'])
            ->get()
            ->keyBy(fn (DixlaseLegalPage $page) => "{$page->slug}:{$page->lang}");

        // マトリックスデータを構築
        $matrix = [];
        foreach ($pageTypes as $slug => $type) {
            $row = [
                'name' => __($type['name']),
                'icon' => $type['icon'],
                'languages' => [],
            ];

            foreach ($languages as $langCode => $langName) {
                $key = "{$slug}:{$langCode}";
                $content = $contents->get($key);

                $row['languages'][$langCode] = [
                    'lang_name' => $langName,
                    'exists' => $content !== null,
                    'title' => $content?->title,
                    'status' => $content?->status,
                    'status_label' => $content?->status?->label(),
                    'status_css' => $content?->status?->cssClass(),
                ];
            }

            $matrix[$slug] = $row;
        }

        return view('dixlase-legal::admin.legal-pages.contents.index', array_merge($this->viewParams, [
            'matrix' => $matrix,
            'languages' => $languages,
        ]));
    }

    /**
     * コンテンツ編集フォーム
     */
    public function edit(string $slug, string $lang): View
    {
        $pageTypes = $this->legalPageService->getPageTypes();

        // 無効なスラッグ/言語のチェック
        if (! isset($pageTypes[$slug])) {
            abort(404);
        }

        $languages = config('language.languages', []);
        if (! isset($languages[$lang])) {
            abort(404);
        }

        $content = DixlaseLegalPage::query()
            ->forSlug($slug)
            ->forLang($lang)
            ->first();

        $pageType = $pageTypes[$slug];
        $formData = $this->prepareFormData();

        return view('dixlase-legal::admin.legal-pages.contents.edit', array_merge($this->viewParams, [
            'content' => $content,
            'slug' => $slug,
            'lang' => $lang,
            'langName' => $languages[$lang],
            'pageTypeName' => __($pageType['name']),
            'pageTypeIcon' => $pageType['icon'],
            'statusOptions' => $formData['statusOptions'],
            'editorOptions' => $formData['editorOptions'],
        ]));
    }

    /**
     * コンテンツ保存
     */
    public function update(DixlaseLegalUpdateContentRequest $request, string $slug, string $lang): RedirectResponse
    {
        $pageTypes = $this->legalPageService->getPageTypes();
        if (! isset($pageTypes[$slug])) {
            abort(404);
        }

        $languages = config('language.languages', []);
        if (! isset($languages[$lang])) {
            abort(404);
        }

        $validated = $request->validated();

        $editorType = $validated['editor_type'] ?? 'html';
        $contentData = [
            'title' => $validated['title'],
            'editor_type' => $editorType,
            'status' => $validated['status'],
            'published_at' => $validated['status'] === ContentStatus::SCHEDULED->value
                ? $validated['published_at']
                : ($validated['status'] === ContentStatus::PUBLISHED->value ? now() : null),
            'content_html' => $editorType === 'html' ? $validated['content'] : null,
            'content_markdown' => $editorType === 'markdown' ? $validated['content'] : null,
        ];

        DixlaseLegalPage::query()->updateOrCreate(
            ['slug' => $slug, 'lang' => $lang],
            $contentData,
        );

        return redirect()
            ->route('dixlase-legal::admin.legal-pages.contents.edit', ['slug' => $slug, 'lang' => $lang])
            ->with('success', __('dixlase-legal::admin/legal-pages/contents.save_success'));
    }

    /**
     * フォームデータを準備
     *
     * @return array{statusOptions: array<string, array{label: string, description: string}>, editorOptions: array<string, string>}
     */
    private function prepareFormData(): array
    {
        $statusOptions = ContentStatus::optionsWithDescription();

        $editorOptions = [
            ContentEditorType::HTML->value => __('common.content_editor.html'),
            ContentEditorType::MARKDOWN->value => __('common.content_editor.markdown'),
        ];

        return [
            'statusOptions' => $statusOptions,
            'editorOptions' => $editorOptions,
        ];
    }
}
