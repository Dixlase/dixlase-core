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
     * Constructor
     */
    public function __construct(
        protected FrontSettingRepositoryInterface $frontSettingRepository,
        protected FrontPageContentService $contentService,
        protected ContentPreviewService $previewService,
    ) {
        parent::__construct();
    }

    /**
     * Front page master (list)
     */
    public function index(): View
    {
        $this->setDescription(__('admin/front.index.description'));

        $frontPage = FrontPage::findByType('main_content');
        $languages = config('language.languages', []);

        // Editor type labels
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

        // Render front page content for preview
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
     * Front page creation form
     */
    public function create(): View|RedirectResponse
    {
        // Redirect to edit if content exists
        $existing = FrontPage::findByType('main_content');
        if ($existing) {
            return redirect()->route('admin.front.edit');
        }

        $this->setDescription(__('admin/front.create.description'));

        $languages = collect(config('language.languages', []))->mapWithKeys(
            fn ($label, $code) => [$code => __("common.{$code}")]
        )->all();
        $templates = $this->buildTemplateData();

        // Use user's profile language as default value
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
     * Save front page creation
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
     * Front page edit form
     */
    public function edit(): View|RedirectResponse
    {
        $this->setDescription(__('admin/front.edit.description'));

        $frontPage = FrontPage::findByType('main_content');

        // Redirect to front page master (list) if content does not exist
        if (! $frontPage) {
            return redirect()->route('admin.front.index');
        }

        $languages = config('language.languages', []);

        // Editor type labels
        $editorTypeLabel = __($frontPage->editor_type->translationKey());

        // If file-based, load content from file
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

        // Load JS/CSS content as well when using HTML editor
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
     * Preview frame for iframe (display front page with theme layout)
     *
     * Loaded in iframe within admin panel edit screen
     * Display with the same appearance as front page using theme's layouts.app,
     * and update content in real-time via postMessage
     */
    public function previewFrame(Request $request): View
    {
        // Override CSP frame-ancestors to 'self' (allow iframe embedding)
        request()->attributes->set('csp_frame_ancestors_self', true);

        // Load theme settings (same logic as FrontController)
        $themeSettings = $this->loadThemeSettingsForPreview();

        // Get and render initial front page content
        $frontPage = FrontPage::findByType('main_content');
        $initialRenderedContent = '';
        $hasCustomCss = false;
        $hasCustomJs = false;
        $customAssetVersion = 0;

        if ($frontPage) {
            $rawContent = $this->contentService->getContent($frontPage, $frontPage->lang);
            if ($rawContent) {
                $initialRenderedContent = $this->previewService->render($rawContent, $frontPage->editor_type);
            }

            // Mirror FrontWelcomeController: HTML editor serves JS/CSS via separate routes.
            if ($frontPage->editor_type === ContentEditorType::HTML) {
                $hasCustomJs = ! empty($this->contentService->getJsContent($frontPage, $frontPage->lang));
                $hasCustomCss = ! empty($this->contentService->getCssContent($frontPage, $frontPage->lang));
            }

            $customAssetVersion = $frontPage->updated_at?->timestamp ?? 0;
        }

        return view('themes::admin.preview-frame', [
            'themeSettings' => $themeSettings,
            'initialRenderedContent' => $initialRenderedContent,
            'hasCustomCss' => $hasCustomCss,
            'hasCustomJs' => $hasCustomJs,
            'customAssetVersion' => $customAssetVersion,
            // Bare mode: render front content only, omit hero/contact (used when
            // embedded inside another preview that already mocks those sections).
            'bareContent' => $request->boolean('bare'),
        ]);
    }

    /**
     * Load theme settings for preview frame
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
     * Return preview HTML for content being edited (AJAX)
     */
    public function preview(Request $request): JsonResponse
    {
        $content = $request->input('content', '');
        $editorTypeSlug = $request->input('editor_type', 'html');

        $renderedHtml = $this->previewService->renderFromSlug($content, $editorTypeSlug);

        return response()->json(['html' => $renderedHtml]);
    }

    /**
     * Save front page edits
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
     * Reset front page (delete records + delete files)
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
     * Front page settings screen
     */
    public function settings(): View
    {
        return view('admin::front/settings', $this->viewParams);
    }

    /**
     * Save front page settings
     */
    public function updateSettings(AdminFrontSettingsUpdateRequest $request): RedirectResponse
    {
        return redirect()->route('admin.front.settings')
            ->with('success', __('admin/front.settings.settings_updated'));
    }

    /**
     * Build template data (for Alpine.js)
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
