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

namespace App\Http\Controllers\Front;

use App\Enums\ContentEditorType;
use App\Models\FrontPage;
use App\Services\FrontPageContentService;
use Illuminate\Support\Facades\App;

class FrontWelcomeController extends FrontController
{
    protected FrontPageContentService $contentService;

    /**
     * コンストラクタ
     */
    public function __construct(FrontPageContentService $contentService)
    {
        parent::__construct();
        $this->contentService = $contentService;
    }

    /**
     * フロントページを表示
     */
    public function index()
    {
        // フロントページのメインコンテンツを取得
        $frontPage = FrontPage::findByType('main_content');
        $frontContent = null;
        $frontEditorType = 'html';

        $hasCustomJs = false;
        $hasCustomCss = false;

        if ($frontPage) {
            $locale = App::getLocale();
            $frontContent = $this->contentService->getContent($frontPage, $locale);
            $frontEditorType = $frontPage->editor_type->slug() ?? 'html';

            // HTML editor: check if custom JS/CSS exists for external file delivery
            if ($frontPage->editor_type === ContentEditorType::HTML) {
                $hasCustomJs = ! empty($this->contentService->getJsContent($frontPage, $locale));
                $hasCustomCss = ! empty($this->contentService->getCssContent($frontPage, $locale));
            }
        }

        $this->viewParams['frontContent'] = $frontContent;
        $this->viewParams['frontEditorType'] = $frontEditorType;
        $this->viewParams['hasCustomJs'] = $hasCustomJs;
        $this->viewParams['hasCustomCss'] = $hasCustomCss;
        $this->viewParams['customAssetVersion'] = $frontPage?->updated_at?->timestamp ?? 0;

        return view('themes::index', $this->viewParams);
    }
}
