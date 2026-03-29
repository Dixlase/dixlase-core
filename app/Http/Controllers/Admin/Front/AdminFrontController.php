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

use App\Contracts\Repositories\FrontSettingRepositoryInterface;
use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
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
        $templates = $this->buildTemplateData();

        // ユーザーのプロフィール言語をデフォルト値として使用
        $userLocale = auth()->user()?->locale?->value ?? array_key_first($languages);

        $editorManager = app(EditorManager::class);
        $enabledByPlugin = $editorManager->getAvailableEditorTypes();
        $guiEditorInfo = ContentEditorPresenter::guiEditorInfo();

        $this->viewParams['languages'] = $languages;
        $this->viewParams['editorCardOptions'] = ContentEditorType::radioCardOptions(ContentStorageType::DATABASE, [], $enabledByPlugin);
        $this->viewParams['editorOptions'] = ContentEditorType::optionsFor(ContentStorageType::DATABASE);
        $this->viewParams['storageOptions'] = ContentStorageType::optionsWithDescription();
        $this->viewParams['templates'] = $templates;
        $this->viewParams['defaultLang'] = $userLocale;
        $this->viewParams['defaultStorageType'] = ContentStorageType::DATABASE->slug();
        $this->viewParams['fileStorageBasePath'] = 'storage/app/private/'.$this->contentService->getBasePath();
        $this->viewParams['guiEditorInfo'] = $guiEditorInfo;
        $this->viewParams['guiEditorAssetHtml'] = $guiEditorInfo ? ContentEditorPresenter::editorAssetHtml($guiEditorInfo) : '';

        return view('admin::front/create', $this->viewParams);
    }

    /**
     * フロントページ作成保存
     */
    public function store(AdminFrontCreateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $storageTypeEnum = ContentStorageType::fromSlug($validated['storage_type']);
        $editorTypeEnum = ContentEditorType::fromSlug($validated['editor_type']);
        $editorTypeSlug = $editorTypeEnum->slug();
        $content = $validated['content'] ?? '';
        $customJs = $validated['custom_js'] ?? null;
        $customCss = $validated['custom_css'] ?? null;

        // HTML エディタ以外は JS/CSS を無視
        if ($editorTypeEnum !== ContentEditorType::HTML) {
            $customJs = null;
            $customCss = null;
        }

        // ファイル保存の場合はファイルにも保存
        if ($storageTypeEnum === ContentStorageType::FILE) {
            $this->contentService->saveToFile(
                'main_content',
                $validated['lang'],
                $editorTypeSlug,
                $content
            );

            // HTML エディタ時は JS/CSS ファイルも保存
            if ($editorTypeEnum === ContentEditorType::HTML) {
                if ($customJs !== null && $customJs !== '') {
                    $this->contentService->saveJsToFile('main_content', $validated['lang'], $customJs);
                }
                if ($customCss !== null && $customCss !== '') {
                    $this->contentService->saveCssToFile('main_content', $validated['lang'], $customCss);
                }
            }
        }

        // コンテンツ作成（常にDBにもコンテンツを保存 = バックアップ）
        FrontPage::create([
            'page_type' => 'main_content',
            'lang' => $validated['lang'],
            'content' => $content,
            'custom_js' => $customJs,
            'custom_css' => $customCss,
            'editor_type' => $editorTypeEnum,
            'storage_type' => $storageTypeEnum,
            'status' => ContentStatus::PUBLISHED,
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

        // エディタータイプのラベル
        $editorTypeLabel = __($frontPage->editor_type->translationKey());

        // ファイル保存の場合、ファイルからコンテンツを読み込む
        $body = $frontPage->content;
        if ($frontPage->storage_type === ContentStorageType::FILE) {
            $fileContents = $this->contentService->loadFromFile(
                'main_content',
                $frontPage->lang,
                $frontPage->editor_type->slug()
            );
            if ($fileContents !== null) {
                $body = $fileContents;
            }
        }

        // HTML エディタ時は JS/CSS コンテンツも読み込む
        $isHtmlEditor = $frontPage->editor_type === ContentEditorType::HTML;
        $customJs = null;
        $customCss = null;
        if ($isHtmlEditor) {
            $customJs = $this->contentService->getJsContent($frontPage, $frontPage->lang);
            $customCss = $this->contentService->getCssContent($frontPage, $frontPage->lang);
        }

        $this->viewParams['frontPage'] = $frontPage;
        $this->viewParams['body'] = old('content', $body);
        $this->viewParams['customJs'] = old('custom_js', $customJs);
        $this->viewParams['customCss'] = old('custom_css', $customCss);
        $this->viewParams['isHtmlEditor'] = $isHtmlEditor;
        $this->viewParams['editorType'] = $frontPage->editor_type->slug();
        $this->viewParams['editorTypeLabel'] = $editorTypeLabel;
        $this->viewParams['editorTypeIcon'] = $frontPage->editor_type->iconClass();
        $this->viewParams['editorTypeColor'] = $frontPage->editor_type->iconColor();
        $this->viewParams['editorTypeDescription'] = __($frontPage->editor_type->descriptionKey());
        $this->viewParams['langCode'] = $frontPage->lang;
        $this->viewParams['langName'] = $languages[$frontPage->lang] ?? $frontPage->lang;
        $guiEditorInfo = ContentEditorPresenter::guiEditorInfo();
        $isGuiEditor = $frontPage->editor_type === ContentEditorType::GUI;

        $this->viewParams['storageOptions'] = ContentStorageType::optionsWithDescription();
        $this->viewParams['storageType'] = old('storage_type', $frontPage->storage_type->slug());
        $this->viewParams['fileStorageBasePath'] = 'storage/app/private/'.$this->contentService->getBasePath();
        $this->viewParams['isGuiEditor'] = $isGuiEditor;
        $this->viewParams['guiEditorInfo'] = $guiEditorInfo;
        $this->viewParams['guiEditorAssetHtml'] = $guiEditorInfo ? ContentEditorPresenter::editorAssetHtml($guiEditorInfo) : '';

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
        $newStorageTypeEnum = ContentStorageType::fromSlug($validated['storage_type']);
        $oldStorageTypeEnum = $frontPage->storage_type;
        $editorTypeSlug = $frontPage->editor_type->slug();
        $locale = $frontPage->lang;
        $content = $validated['content'] ?? '';
        $isHtmlEditor = $frontPage->editor_type === ContentEditorType::HTML;
        $customJs = $isHtmlEditor ? ($validated['custom_js'] ?? null) : null;
        $customCss = $isHtmlEditor ? ($validated['custom_css'] ?? null) : null;

        // 保存方法が変更された場合の処理
        if ($oldStorageTypeEnum !== $newStorageTypeEnum) {
            if ($oldStorageTypeEnum === ContentStorageType::FILE && $newStorageTypeEnum === ContentStorageType::DATABASE) {
                // ファイル→DB: ファイルを削除（DBには常にバックアップがあるため読み込み不要）
                $this->contentService->deleteFile('main_content', $locale, $editorTypeSlug);
                if ($isHtmlEditor) {
                    $this->contentService->deleteJsFile('main_content', $locale);
                    $this->contentService->deleteCssFile('main_content', $locale);
                }
            }
        }

        // ファイル保存の場合はファイルにも保存
        if ($newStorageTypeEnum === ContentStorageType::FILE) {
            $this->contentService->saveToFile('main_content', $locale, $editorTypeSlug, $content);

            // HTML エディタ時は JS/CSS ファイルも保存
            if ($isHtmlEditor) {
                if ($customJs !== null && $customJs !== '') {
                    $this->contentService->saveJsToFile('main_content', $locale, $customJs);
                } else {
                    $this->contentService->deleteJsFile('main_content', $locale);
                }
                if ($customCss !== null && $customCss !== '') {
                    $this->contentService->saveCssToFile('main_content', $locale, $customCss);
                } else {
                    $this->contentService->deleteCssFile('main_content', $locale);
                }
            }
        }

        // ページを更新（常にDBにもコンテンツを保存 = バックアップ）
        $updateData = [
            'content' => $content,
            'storage_type' => $newStorageTypeEnum,
        ];
        if ($isHtmlEditor) {
            $updateData['custom_js'] = $customJs;
            $updateData['custom_css'] = $customCss;
        }
        $frontPage->update($updateData);

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
                    $frontPage->editor_type->slug()
                );

                // HTML エディタ時は JS/CSS ファイルも削除
                if ($frontPage->editor_type === ContentEditorType::HTML) {
                    $this->contentService->deleteJsFile('main_content', $frontPage->lang);
                    $this->contentService->deleteCssFile('main_content', $frontPage->lang);
                }
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
