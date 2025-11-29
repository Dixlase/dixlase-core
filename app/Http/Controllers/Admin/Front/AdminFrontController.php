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

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\FrontSetting;
use App\Models\FrontPage;
use App\Models\Media;
use App\Services\FrontPageContentService;
use App\Helpers\LocaleHelper;
use Illuminate\Http\Request;
use App\Contracts\Repositories\FrontSettingRepositoryInterface;

class AdminFrontController extends AdminLoggedinController
{
    /**
     * フロント設定リポジトリ
     */
    protected FrontSettingRepositoryInterface $frontSettingRepository;

    /**
     * フロントページコンテンツサービス
     */
    protected FrontPageContentService $contentService;

    /**
     * コンストラクタ
     */
    public function __construct(
        FrontSettingRepositoryInterface $frontSettingRepository,
        FrontPageContentService $contentService
    ) {
        parent::__construct();
        $this->frontSettingRepository = $frontSettingRepository;
        $this->contentService = $contentService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return view('admin::front/index', $this->viewParams);
    }

    /**
     * フロントページ編集画面
     */
    public function edit()
    {
        // フロントページのメインコンテンツを取得または作成
        $frontPage = FrontPage::findOrCreateByType('main_content');
        
        // ファイル保存の場合、ファイルからコンテンツを読み込む
        $fileContents = [];
        if ($frontPage->storage_type->value === 'file') {
            $locales = LocaleHelper::supportedLocales();
            foreach ($locales as $locale) {
                $fileContent = $this->contentService->loadFromFile(
                    $frontPage->page_type,
                    $locale,
                    $frontPage->editor_type->value
                );
                if ($fileContent !== null) {
                    $fileContents[$locale] = $fileContent;
                }
            }
        }
        
        $this->viewParams['frontPage'] = $frontPage;
        $this->viewParams['fileContents'] = $fileContents;
        
        return view('admin::front/edit', $this->viewParams);
    }

    /**
     * フロントページ編集の保存
     */
    public function updateEdit(Request $request)
    {
        $validated = $request->validate([
            'storage_type' => 'required|in:database,file',
            'editor_type' => 'required|in:gui,markdown,html,blade',
            'translations' => 'required|array',
            'translations.*.title' => 'nullable|string|max:255',
            'translations.*.content' => 'nullable|string',
        ]);
        
        // GUIエディタの場合は強制的にDBに
        $storageType = $validated['storage_type'];
        if ($validated['editor_type'] === 'gui') {
            $storageType = 'database';
        }
        
        // フロントページを取得または作成
        $frontPage = FrontPage::findOrCreateByType('main_content');
        $oldStorageType = $frontPage->storage_type->value;
        $oldEditorType = $frontPage->editor_type->value;
        $locales = LocaleHelper::supportedLocales();
        
        // 保存方法が変更された場合の処理
        if ($oldStorageType !== $storageType) {
            if ($oldStorageType === 'file' && $storageType === 'database') {
                // ファイル→DB: ファイルからコンテンツを読み込んでDBに保存、ファイルを削除
                foreach ($locales as $locale) {
                    $fileContent = $this->contentService->loadFromFile($frontPage->page_type, $locale, $oldEditorType);
                    if ($fileContent !== null && isset($validated['translations'][$locale])) {
                        $validated['translations'][$locale]['content'] = $fileContent;
                    }
                }
                $this->contentService->deleteAllFiles($frontPage->page_type, $oldEditorType, $locales);
            }
        }
        
        // フロントページ本体を更新
        $frontPage->update([
            'storage_type' => $storageType,
            'editor_type' => $validated['editor_type'],
        ]);

        // 翻訳データを更新
        if (isset($validated['translations'])) {
            foreach ($validated['translations'] as $locale => $data) {
                $content = $data['content'] ?? '';
                
                if ($storageType === 'file') {
                    // ファイル保存の場合はコンテンツをファイルに保存
                    $this->contentService->saveToFile(
                        $frontPage->page_type,
                        $locale,
                        $validated['editor_type'],
                        $content
                    );
                    // DBにはコンテンツを保存しない
                    $validated['translations'][$locale]['content'] = null;
                    $validated['translations'][$locale]['content_markdown'] = null;
                    $validated['translations'][$locale]['content_html'] = null;
                    $validated['translations'][$locale]['content_blade'] = null;
                } else {
                    // DB保存の場合はエディタータイプ別のカラムに保存
                    $validated['translations'][$locale]['content'] = null; // 旧カラムはnull
                    $validated['translations'][$locale]['content_markdown'] = null;
                    $validated['translations'][$locale]['content_html'] = null;
                    $validated['translations'][$locale]['content_blade'] = null;
                    
                    // 現在のエディタータイプのカラムにのみ保存
                    $contentColumn = 'content_' . $validated['editor_type'];
                    $validated['translations'][$locale][$contentColumn] = $content;
                }
            }
            $frontPage->setTranslations($validated['translations']);
        }

        return redirect()
            ->route('admin.front.edit')
            ->with('success', __('admin.settings.front.design_updated'));
    }

    /**
     * コンテンツ取得API（保存方法・エディタータイプ変更時）
     */
    public function getContent(string $storageType, string $editorType)
    {
        $frontPage = FrontPage::findOrCreateByType('main_content');
        $locales = LocaleHelper::supportedLocales();
        $contents = [];

        foreach ($locales as $locale) {
            if ($storageType === 'file') {
                // ファイルからコンテンツを読み込む
                $content = $this->contentService->loadFromFile(
                    $frontPage->page_type,
                    $locale,
                    $editorType
                );
                $contents[$locale] = $content ?? '';
            } else {
                // DBからエディタータイプ別のカラムを読み込む
                $translation = $frontPage->translate($locale);
                if ($translation) {
                    $contentColumn = 'content_' . $editorType;
                    $contents[$locale] = $translation->{$contentColumn} ?? '';
                } else {
                    $contents[$locale] = '';
                }
            }
        }

        return response()->json(['contents' => $contents]);
    }

    /**
     * フロントページ設定画面
     */
    public function settings()
    {
        // 設定値を取得
        $settings = [
            'front_ogp_image_id' => $this->frontSettingRepository->get('front_ogp_image_id'),
            'front_description' => $this->frontSettingRepository->get('front_description'),
        ];
        
        // メディア情報を取得
        $frontOgpImage = $settings['front_ogp_image_id'] ? Media::find($settings['front_ogp_image_id']) : null;
        
        $this->viewParams['settings'] = $settings;
        $this->viewParams['frontOgpImage'] = $frontOgpImage;
        
        return view('admin::front/settings', $this->viewParams);
    }

    /**
     * フロントページ設定の保存
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'front_ogp_image_id' => 'nullable|exists:media,id',
            'front_description' => 'nullable|string|max:1000',
        ]);
        
        // 設定を保存
        $this->frontSettingRepository->set('front_ogp_image_id', $request->input('front_ogp_image_id'));
        $this->frontSettingRepository->set('front_description', $request->input('front_description'));
        
        return redirect()->route('admin.front.settings')
            ->with('success', __('admin.settings.front.settings_updated'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }
}
