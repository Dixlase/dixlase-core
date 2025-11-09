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
use App\Models\Media;
use Illuminate\Http\Request;
use App\Contracts\Repositories\FrontSettingRepositoryInterface;

class AdminFrontController extends AdminLoggedinController
{
    /**
     * フロント設定リポジトリ
     */
    protected FrontSettingRepositoryInterface $frontSettingRepository;

    /**
     * コンストラクタ
     */
    public function __construct(FrontSettingRepositoryInterface $frontSettingRepository)
    {
        parent::__construct();
        $this->frontSettingRepository = $frontSettingRepository;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return view('admin::front/index', $this->viewParams);
    }

    public function design()
    {
        return view('admin::front/design', $this->viewParams);
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
