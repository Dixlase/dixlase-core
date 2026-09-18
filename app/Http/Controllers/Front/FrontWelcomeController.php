<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Http\Controllers\Front;

use App\Enums\ContentEditorType;
use App\Models\FrontPage;
use App\Services\Editor\EditorManager;
use App\Services\FrontPageContentService;
use Illuminate\Support\Facades\App;

class FrontWelcomeController extends FrontController
{
    protected FrontPageContentService $contentService;

    /**
     * Constructor
     */
    public function __construct(FrontPageContentService $contentService)
    {
        parent::__construct();
        $this->contentService = $contentService;
    }

    /**
     * Display front page
     */
    public function index()
    {
        // Get main content for front page
        $frontPage = FrontPage::findByType('main_content');
        $frontContent = null;
        $frontEditorType = 'html';

        $hasCustomJs = false;
        $hasCustomCss = false;
        $customJs = '';
        $customCss = '';

        if ($frontPage) {
            $locale = App::getLocale();
            $frontContent = $this->contentService->getContent($frontPage, $locale);
            $frontEditorType = $frontPage->editor_type->slug() ?? 'html';

            // GUI editor: render JSON content to HTML for front display
            if ($frontPage->editor_type === ContentEditorType::GUI && $frontContent) {
                $editorManager = app(EditorManager::class);
                $renderedHtml = $editorManager->renderContent('gui', $frontContent);
                if ($renderedHtml !== '') {
                    $frontContent = $renderedHtml;
                    $frontEditorType = 'html';
                }
            }

            // HTML editor: check if custom JS/CSS exists for external file delivery
            if ($frontPage->editor_type === ContentEditorType::HTML) {
                $customJs = (string) $this->contentService->getJsContent($frontPage, $locale);
                $customCss = (string) $this->contentService->getCssContent($frontPage, $locale);
                $hasCustomJs = $customJs !== '';
                $hasCustomCss = $customCss !== '';
            }
        }

        $this->viewParams['frontContent'] = $frontContent;
        $this->viewParams['frontEditorType'] = $frontEditorType;
        $this->viewParams['hasCustomJs'] = $hasCustomJs;
        $this->viewParams['hasCustomCss'] = $hasCustomCss;
        $this->viewParams['customAssetVersion'] = self::customAssetVersion($frontPage, $customCss, $customJs);

        return view('themes::index', $this->viewParams);
    }

    /**
     * Cache-busting token for the `/front/custom-style.css` and
     * `/front/custom-script.js` URLs.
     *
     * Derived from the served CSS / JS bodies (plus the row timestamp), not
     * from `updated_at` alone: with file storage the CSS / JS are edited on
     * disk without touching the row, so a timestamp-only token never
     * changed and browsers kept the stale stylesheet for the full
     * `max-age=3600` of the asset response. Shared with the admin preview
     * frame so both render against the same token.
     */
    public static function customAssetVersion(?FrontPage $frontPage, string $css, string $js): string
    {
        if (! $frontPage) {
            return '0';
        }

        $stamp = (string) ($frontPage->updated_at?->timestamp ?? 0);

        if ($css === '' && $js === '') {
            return $stamp;
        }

        return sprintf('%u', crc32($stamp."\n".$css."\n".$js));
    }
}
