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
use App\Http\Requests\Admin\Contents\Pages\AdminContentsPagesStoreRequest;


class AdminContentsPageController extends AdminController
{

    protected $pagesDirectory;
    //初期設定を行う
    public function __construct()
    {
        // 親クラスのコンストラクタを呼び出す
        parent::__construct();

        // ページのマークダウンファイルを保存するディレクトリ
        $this->pagesDirectory = config('custom.pages_directory');
        $this->viewParams['pages_directory'] = $this->pagesDirectory;
    }


    public function index()
    {

        $this->viewParams['heading'] = 'admin.features.contents.pages.index.heading';


        // ページネーションで取得
        $pages = Page::paginate(10); // 1ページあたり10件表示
        $this->viewParams['pages'] = $pages;
        return view(
            'admin.contents.pages.index',
            $this->viewParams
        );
    }

    public function create()
    {

        $this->viewParams['heading'] = 'admin.features.contents.pages.create.heading';

        return view(
            'admin.contents.pages.create',
            $this->viewParams
        );
    }

    public function edit(Page $page)
    {
        // 見出し
        $this->viewParams['heading'] = 'admin.features.contents.pages.edit.heading';

        // 内容を取得
        $this->viewParams['page'] = $page;



        // ビューにデータを渡す
        return view(
            'admin.contents.pages.edit',
            $this->viewParams
        );
    }

    public function store(AdminContentsPagesStoreRequest $request)
    {
        // バリデーションを通過したデータを取得
        $validated = $request->validated();

        // ページを作成し、作成したページのインスタンスを取得
        $page = Page::create($validated);

        return redirect()->route('admin.contents.pages.edit', ['page' => $page->id])->with('success', 'Page created successfully!');
    }

    public function update(AdminContentsPagesStoreRequest $request, Page $page)
    {
        $validated = $request->validated();

        // ページを更新
        $page->update($validated);

        return redirect()->route('admin.contents.pages.edit', ['page' => $page->id])->with('success', 'Page updated successfully!');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.contents.ages.index', $this->viewParams)->with('success', 'Page deleted successfully!');
    }
}
