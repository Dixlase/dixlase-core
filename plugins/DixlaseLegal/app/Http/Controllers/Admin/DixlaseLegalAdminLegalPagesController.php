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

use App\Services\LegalPageService;
use App\Traits\AdminInterfaceTrait;
use App\Traits\AdminLoggedInTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Plugins\DixlaseLegal\App\Http\Requests\Admin\DixlaseLegalUpdateLegalPagesRequest;

/**
 * 法務ページURL管理コントローラー
 */
class DixlaseLegalAdminLegalPagesController extends Controller
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
     * 法務ページURL設定画面を表示
     */
    public function index(): View
    {
        $pageTypes = $this->legalPageService->getPageTypes();

        // 各ページ種別のURL・翻訳済みデータをビュー用に準備
        $pages = [];
        foreach ($pageTypes as $slug => $type) {
            $pages[$slug] = [
                'name' => __($type['name']),
                'description' => __($type['description']),
                'icon' => $type['icon'],
                'required' => $type['required'] ?? false,
                'url' => $this->legalPageService->url($slug),
            ];
        }

        return view('dixlase-legal::admin.legal-pages.index', array_merge($this->viewParams, [
            'pages' => $pages,
        ]));
    }

    /**
     * 法務ページURLを一括保存
     */
    public function update(DixlaseLegalUpdateLegalPagesRequest $request): RedirectResponse
    {
        $urls = $request->validated()['urls'] ?? [];

        foreach ($urls as $slug => $url) {
            $this->legalPageService->setUrl($slug, $url);
        }

        return redirect()
            ->route('dixlase-legal::admin.legal-pages.index')
            ->with('success', __('dixlase-legal::admin/legal-pages/index.save_success'));
    }
}
