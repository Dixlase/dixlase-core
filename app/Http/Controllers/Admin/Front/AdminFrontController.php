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
        $fileContents = null;
        if ($frontPage->storage_type->value === 'file') {
            $fileContents = $this->contentService->loadFromFile(
                $frontPage->page_type,
                app()->getLocale(),
                $frontPage->editor_type->value
            );
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
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
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
        $locale = app()->getLocale();
        
        $content = $validated['content'] ?? '';
        
        // コンテンツカラムの準備
        $contentData = [
            'content' => null,
            'content_markdown' => null,
            'content_html' => null,
            'content_blade' => null,
        ];
        
        // 保存方法が変更された場合の処理
        if ($oldStorageType !== $storageType) {
            if ($oldStorageType === 'file' && $storageType === 'database') {
                // ファイル→DB: ファイルからコンテンツを読み込んでDBに保存、ファイルを削除
                $fileContent = $this->contentService->loadFromFile($frontPage->page_type, $locale, $oldEditorType);
                if ($fileContent !== null) {
                    $content = $fileContent;
                }
                $this->contentService->deleteFile($frontPage->page_type, $locale, $oldEditorType);
            }
        }
        
        if ($storageType === 'file') {
            // ファイル保存の場合はコンテンツをファイルに保存
            $this->contentService->saveToFile(
                $frontPage->page_type,
                $locale,
                $validated['editor_type'],
                $content
            );
        } else {
            // DB保存の場合はエディタータイプ別のカラムに保存
            $contentColumn = 'content_' . $validated['editor_type'];
            $contentData[$contentColumn] = $content;
        }
        
        // フロントページを更新
        $frontPage->update([
            'title' => $validated['title'] ?? null,
            'storage_type' => $storageType,
            'editor_type' => $validated['editor_type'],
            ...$contentData,
        ]);

        return redirect()
            ->route('admin.front.edit')
            ->with('success', __('admin/front.design_updated'));
    }

    /**
     * コンテンツ取得API（保存方法・エディタータイプ変更時）
     */
    public function getContent(string $storageType, string $editorType)
    {
        $frontPage = FrontPage::findOrCreateByType('main_content');
        $content = '';

        if ($storageType === 'file') {
            // ファイルからコンテンツを読み込む
            $content = $this->contentService->loadFromFile(
                $frontPage->page_type,
                app()->getLocale(),
                $editorType
            ) ?? '';
        } else {
            // DBからエディタータイプ別のカラムを読み込む
            $contentColumn = 'content_' . $editorType;
            $content = $frontPage->{$contentColumn} ?? '';
        }

        return response()->json(['content' => $content]);
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
            ->with('success', __('admin/front.settings_updated'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }
}
