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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

use App\Actions\FrontPage\CreateFrontPageAction;
use App\Actions\FrontPage\DeleteFrontPageAction;
use App\Actions\FrontPage\UpdateFrontPageAction;
use App\Actors\MemberActor;
use App\Contracts\Repositories\FrontSettingRepositoryInterface;
use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use App\Helpers\AdminHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Front\AdminFrontCreateRequest;
use App\Http\Requests\Admin\Front\AdminFrontEditUpdateRequest;
use App\Http\Requests\Admin\Front\AdminFrontSettingsUpdateRequest;
use App\Models\FrontPage;
use App\Presenters\Admin\ContentEditorPresenter;
use App\Services\ContentPreviewService;
use App\Services\Editor\EditorManager;
use App\Services\FrontPageContentService;
use App\Services\FrontPageRevisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminFrontController extends AdminLoggedInController
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected FrontSettingRepositoryInterface $frontSettingRepository,
        protected FrontPageContentService $contentService,
        protected ContentPreviewService $previewService,
    ) {
        parent::__construct();
    }

    /**
     * フロントページマスター（一覧）
     */
    public function index(): View
    {
        $this->setDescription(__('admin/front.index.description'));

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

        // フロントページコンテンツをプレビュー用にレンダリング
        $previewContent = null;
        if ($frontPage && $frontPage->isPublished()) {
            $rawContent = $this->contentService->getContent($frontPage, $frontPage->lang);
            if ($rawContent) {
                $previewContent = $this->previewService->render($rawContent, $frontPage->editor_type);
            }
        }
        $this->viewParams['previewContent'] = $previewContent;
        $this->viewParams['previewFrameUrl'] = route('admin.front.preview-frame');

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

        $this->setDescription(__('admin/front.create.description'));

        $languages = collect(config('language.languages', []))->mapWithKeys(
            fn ($label, $code) => [$code => __("common.{$code}")]
        )->all();
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
        $this->viewParams['previewFrameUrl'] = route('admin.front.preview-frame');
        $this->viewParams['previewUrl'] = route('admin.front.preview');

        return view('admin::front/create', $this->viewParams);
    }

    /**
     * フロントページ作成保存
     */
    public function store(AdminFrontCreateRequest $request): RedirectResponse
    {
        $actor = new MemberActor(AdminHelper::getMember());
        app(CreateFrontPageAction::class)->execute($actor, $request->validated());

        return redirect()
            ->route('admin.front.edit')
            ->with('success', __('admin/front.create.create_success'));
    }

    /**
     * フロントページ編集フォーム
     */
    public function edit(): View|RedirectResponse
    {
        $this->setDescription(__('admin/front.edit.description'));

        $frontPage = FrontPage::findByType('main_content');

        // コンテンツ未存在時はフロントページマスター（一覧）にリダイレクト
        if (! $frontPage) {
            return redirect()->route('admin.front.index');
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
        $this->viewParams['previewUrl'] = route('admin.front.preview');
        $this->viewParams['previewFrameUrl'] = route('admin.front.preview-frame');
        $this->viewParams['editorTypeValue'] = $frontPage->editor_type->slug();

        return view('admin::front/edit', $this->viewParams);
    }

    /**
     * iframe用プレビューフレーム（テーマレイアウトでフロントページを表示）
     *
     * 管理画面の編集画面内iframeに読み込まれる。
     * テーマの layouts.app を使用してフロントページと同じ見た目で表示し、
     * postMessage でコンテンツをリアルタイム更新する。
     */
    public function previewFrame(): View
    {
        // CSP frame-ancestors を 'self' に上書き（iframe埋め込み許可）
        request()->attributes->set('csp_frame_ancestors_self', true);

        // テーマ設定を読み込む（FrontController と同じロジック）
        $themeSettings = $this->loadThemeSettingsForPreview();

        // フロントページの初期コンテンツを取得・レンダリング
        $frontPage = FrontPage::findByType('main_content');
        $initialRenderedContent = '';

        if ($frontPage) {
            $rawContent = $this->contentService->getContent($frontPage, $frontPage->lang);
            if ($rawContent) {
                $initialRenderedContent = $this->previewService->render($rawContent, $frontPage->editor_type);
            }
        }

        return view('themes::admin.preview-frame', [
            'themeSettings' => $themeSettings,
            'initialRenderedContent' => $initialRenderedContent,
        ]);
    }

    /**
     * プレビューフレーム用にテーマ設定を読み込む
     */
    protected function loadThemeSettingsForPreview(): object
    {
        try {
            $activeThemeId = \Illuminate\Support\Facades\DB::table('theme_settings')
                ->where('key', 'enabled_theme_id')
                ->value('value');

            if (! $activeThemeId) {
                return (object) [];
            }

            $theme = \Illuminate\Support\Facades\DB::table('themes')->find($activeThemeId);
            if (! $theme) {
                return (object) [];
            }

            $settingsTableName = 'thm_'.strtolower(str_replace('-', '_', $theme->slug)).'_settings';

            $settings = \Illuminate\Support\Facades\DB::table($settingsTableName)
                ->get()
                ->pluck('value', 'name');

            return (object) $settings->toArray();
        } catch (\Exception $e) {
            return (object) [];
        }
    }

    /**
     * 編集中コンテンツのプレビュー用HTMLを返す（AJAX）
     */
    public function preview(Request $request): JsonResponse
    {
        $content = $request->input('content', '');
        $editorTypeSlug = $request->input('editor_type', 'html');

        $renderedHtml = $this->previewService->renderFromSlug($content, $editorTypeSlug);

        return response()->json(['html' => $renderedHtml]);
    }

    /**
     * フロントページ編集の保存
     */
    public function update(AdminFrontEditUpdateRequest $request): RedirectResponse
    {
        $frontPage = FrontPage::findByType('main_content');
        if (! $frontPage) {
            return redirect()->route('admin.front.index');
        }

        $actor = new MemberActor(AdminHelper::getMember());
        (new UpdateFrontPageAction($frontPage, $this->contentService, app(FrontPageRevisionService::class)))->execute($actor, $request->validated());

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
            $actor = new MemberActor(AdminHelper::getMember());
            (new DeleteFrontPageAction($frontPage, $this->contentService))->execute($actor, []);
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
                    'custom_css' => trans('admin/front/templates.main_content.content_css', [], $lang),
                    'custom_js' => trans('admin/front/templates.main_content.content_js', [], $lang),
                ],
            ];
        }

        return $templates;
    }
}
