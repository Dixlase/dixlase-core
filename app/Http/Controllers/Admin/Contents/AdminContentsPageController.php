<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers\Admin\Contents;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Page;
use Illuminate\Http\Request;

class AdminContentsPageController extends AdminController
{

    protected $pages_directory;
    //初期設定を行う
    public function __construct()
    {
        // 親クラスのコンストラクタを呼び出す
        parent::__construct();

        // ページのマークダウンファイルを保存するディレクトリ
        $this->pages_directory = config('custom.pages_directory');
        $this->view_params['pages_directory'] = $this->pages_directory;
    }


    public function index()
    {

        $this->view_params['heading'] = 'admin.features.contents.pages.index.heading';


        // ページネーションで取得
        $pages = Page::paginate(10); // 1ページあたり10件表示
        $this->view_params['pages'] = $pages;
        return view(
            'admin.contents.pages.index',
            $this->view_params
        );
    }

    public function create()
    {

        $this->view_params['heading'] = 'admin.features.contents.pages.create.heading';

        return view(
            'admin.contents.pages.create',
            $this->view_params
        );
    }

    public function edit()
    {
        return view(
            'admin.contents.pages.edit',
            $this->view_params
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug',
            'content' => 'nullable|string',
        ]);

        Page::create($validated);

        return redirect()->route(
            'admin.pages.index',
            $this->view_params
        )->with('success', 'Page created successfully!');
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug,' . $page->id,
            'content' => 'nullable|string',
        ]);

        $page->update($validated);

        return redirect()->route('admin.contents.pages.index', $this->view_params)->with('success', 'Page updated successfully!');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.contents.ages.index', $this->view_params)->with('success', 'Page deleted successfully!');
    }
}
