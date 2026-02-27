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

namespace App\Http\Controllers\Admin\Front;

use App\Contracts\Repositories\FrontSettingRepositoryInterface;
use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Front\AdminFrontCreateRequest;
use App\Http\Requests\Admin\Front\AdminFrontEditUpdateRequest;
use App\Http\Requests\Admin\Front\AdminFrontSettingsUpdateRequest;
use App\Models\FrontPage;
use App\Services\FrontPageContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminFrontController extends AdminLoggedInController
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected FrontSettingRepositoryInterface $frontSettingRepository,
        protected FrontPageContentService $contentService,
    ) {
        parent::__construct();
    }

    /**
     * フロントページマスター（一覧）
     */
    public function index(): View
    {
        $frontPage = FrontPage::findByType('main_content');
        $languages = config('language.languages', []);

        // エディタータイプのラベル
        $editorTypeLabel = null;
        $storageTypeLabel = null;
        $langName = null;

        if ($frontPage) {
            $editorTypeLabel = __($frontPage->editor_type->translationKey());
            $storageTypeLabel = __($frontPage->storage_type->translationKey());
            $langName = $languages[$frontPage->lang] ?? $frontPage->lang;
        }

        $this->viewParams['frontPage'] = $frontPage;
        $this->viewParams['editorTypeLabel'] = $editorTypeLabel;
        $this->viewParams['storageTypeLabel'] = $storageTypeLabel;
        $this->viewParams['langName'] = $langName;

        return view('admin::front/index', $this->viewParams);
    }

    /**
     * フロントページ作成フォーム
     */
    public function create(): View|RedirectResponse
    {
        // コンテンツ存在時は edit にリダイレクト
        $existing = FrontPage::findByType('main_content');
        if ($existing) {
            return redirect()->route('admin.front.edit');
        }

        $languages = config('language.languages', []);
        $formData = $this->prepareFormData();
        $templates = $this->buildTemplateData();

        // ユーザーのプロフィール言語をデフォルト値として使用
        $userLocale = auth()->user()?->locale?->value ?? array_key_first($languages);

        $this->viewParams['languages'] = $languages;
        $this->viewParams['editorOptions'] = $formData['editorOptions'];
        $this->viewParams['storageOptions'] = ContentStorageType::optionsWithDescription();
        $this->viewParams['templates'] = $templates;
        $this->viewParams['defaultLang'] = $userLocale;
        $this->viewParams['defaultStorageType'] = ContentStorageType::DATABASE->value;
        $this->viewParams['fileStorageBasePath'] = $this->contentService->getBasePath();

        return view('admin::front/create', $this->viewParams);
    }

    /**
     * フロントページ作成保存
     */
    public function store(AdminFrontCreateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $storageType = $validated['storage_type'];
        $content = $validated['content'] ?? '';

        // ファイル保存の場合はファイルにも保存
        if ($storageType === ContentStorageType::FILE->value) {
            $this->contentService->saveToFile(
                'main_content',
                $validated['lang'],
                $validated['editor_type'],
                $content
            );
        }

        // コンテンツ作成（常にDBにもコンテンツを保存 = バックアップ）
        FrontPage::create([
            'page_type' => 'main_content',
            'lang' => $validated['lang'],
            'content' => $content,
            'editor_type' => $validated['editor_type'],
            'storage_type' => $storageType,
            'status' => 'published',
        ]);

        return redirect()
            ->route('admin.front.edit')
            ->with('success', __('admin/front.create.create_success'));
    }

    /**
     * フロントページ編集フォーム
     */
    public function edit(): View|RedirectResponse
    {
        $frontPage = FrontPage::findByType('main_content');

        // コンテンツ未存在時は create にリダイレクト
        if (! $frontPage) {
            return redirect()->route('admin.front.create');
        }

        $languages = config('language.languages', []);
        $formData = $this->prepareFormData();

        // エディタータイプのラベル
        $editorTypeLabel = $frontPage->editor_type === ContentEditorType::HTML
            ? ($formData['editorOptions']['html'] ?? 'HTML')
            : ($formData['editorOptions']['markdown'] ?? 'Markdown');

        // ファイル保存の場合、ファイルからコンテンツを読み込む
        $body = $frontPage->content;
        if ($frontPage->storage_type === ContentStorageType::FILE) {
            $fileContents = $this->contentService->loadFromFile(
                'main_content',
                $frontPage->lang,
                $frontPage->editor_type->value
            );
            if ($fileContents !== null) {
                $body = $fileContents;
            }
        }

        $this->viewParams['frontPage'] = $frontPage;
        $this->viewParams['body'] = old('content', $body);
        $this->viewParams['editorType'] = $frontPage->editor_type->value;
        $this->viewParams['editorTypeLabel'] = $editorTypeLabel;
        $this->viewParams['langCode'] = $frontPage->lang;
        $this->viewParams['langName'] = $languages[$frontPage->lang] ?? $frontPage->lang;
        $this->viewParams['storageOptions'] = ContentStorageType::optionsWithDescription();
        $this->viewParams['storageType'] = old('storage_type', $frontPage->storage_type->value);
        $this->viewParams['fileStorageBasePath'] = $this->contentService->getBasePath();

        return view('admin::front/edit', $this->viewParams);
    }

    /**
     * フロントページ編集の保存
     */
    public function update(AdminFrontEditUpdateRequest $request): RedirectResponse
    {
        $frontPage = FrontPage::findByType('main_content');
        if (! $frontPage) {
            return redirect()->route('admin.front.create');
        }

        $validated = $request->validated();
        $storageType = $validated['storage_type'];
        $oldStorageType = $frontPage->storage_type->value;
        $editorType = $frontPage->editor_type->value;
        $locale = $frontPage->lang;
        $content = $validated['content'] ?? '';

        // 保存方法が変更された場合の処理
        if ($oldStorageType !== $storageType) {
            if ($oldStorageType === ContentStorageType::FILE->value && $storageType === ContentStorageType::DATABASE->value) {
                // ファイル→DB: ファイルを削除（DBには常にバックアップがあるため読み込み不要）
                $this->contentService->deleteFile('main_content', $locale, $editorType);
            }
        }

        // ファイル保存の場合はファイルにも保存
        if ($storageType === ContentStorageType::FILE->value) {
            $this->contentService->saveToFile('main_content', $locale, $editorType, $content);
        }

        // ページを更新（常にDBにもコンテンツを保存 = バックアップ）
        $frontPage->update([
            'content' => $content,
            'storage_type' => $storageType,
        ]);

        return redirect()
            ->route('admin.front.edit')
            ->with('success', __('admin/front.edit.save_success'));
    }

    /**
     * フロントページリセット（レコード削除 + ファイル削除）
     */
    public function destroy(): RedirectResponse
    {
        $frontPage = FrontPage::findByType('main_content');

        if ($frontPage) {
            // ファイル保存の場合、関連ファイルも削除
            if ($frontPage->storage_type === ContentStorageType::FILE) {
                $this->contentService->deleteFile(
                    'main_content',
                    $frontPage->lang,
                    $frontPage->editor_type->value
                );
            }

            $frontPage->delete();
        }

        return redirect()
            ->route('admin.front.index')
            ->with('success', __('admin/front.index.reset_success'));
    }

    /**
     * フロントページ設定画面
     */
    public function settings(): View
    {
        return view('admin::front/settings', $this->viewParams);
    }

    /**
     * フロントページ設定の保存
     */
    public function updateSettings(AdminFrontSettingsUpdateRequest $request): RedirectResponse
    {
        return redirect()->route('admin.front.settings')
            ->with('success', __('admin/front.settings.settings_updated'));
    }

    /**
     * フォームデータを準備
     *
     * @return array{editorOptions: array<string, string>}
     */
    private function prepareFormData(): array
    {
        $editorOptions = [
            ContentEditorType::HTML->value => __('common.content_editor.html'),
            ContentEditorType::MARKDOWN->value => __('common.content_editor.markdown'),
        ];

        return [
            'editorOptions' => $editorOptions,
        ];
    }

    /**
     * テンプレートデータを構築（Alpine.js 用）
     *
     * @return array<string, array<string, array{content: string}>>
     */
    private function buildTemplateData(): array
    {
        $languages = array_keys(config('language.languages', []));
        $templates = [];

        foreach ($languages as $lang) {
            $templates[$lang] = [
                'markdown' => [
                    'content' => trans('admin/front/templates.main_content.content_markdown', [], $lang),
                ],
                'html' => [
                    'content' => trans('admin/front/templates.main_content.content_html', [], $lang),
                ],
            ];
        }

        return $templates;
    }
}
