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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
use App\Services\FrontPageContentService;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;

/**
 * Front page custom JS/CSS external file delivery controller
 *
 * Serves user-defined custom JavaScript and CSS as external files
 * to ensure CSP compatibility across all modes.
 */
class FrontCustomAssetController extends Controller
{
    public function __construct(private FrontPageContentService $contentService) {}

    /**
     * Serve custom JavaScript as an external file
     */
    public function script(): Response
    {
        $content = $this->resolveContent('js');

        if ($content === null) {
            abort(404);
        }

        return $this->buildResponse($content, 'application/javascript');
    }

    /**
     * Serve custom CSS as an external file
     */
    public function style(): Response
    {
        $content = $this->resolveContent('css');

        if ($content === null) {
            abort(404);
        }

        return $this->buildResponse($content, 'text/css');
    }

    /**
     * Resolve JS or CSS content from the front page
     */
    private function resolveContent(string $type): ?string
    {
        $frontPage = FrontPage::findByType('main_content');

        if (! $frontPage) {
            return null;
        }

        if ($frontPage->editor_type !== ContentEditorType::HTML) {
            return null;
        }

        $locale = App::getLocale();

        $content = $type === 'js'
            ? $this->contentService->getJsContent($frontPage, $locale)
            : $this->contentService->getCssContent($frontPage, $locale);

        if (empty($content)) {
            return null;
        }

        return $content;
    }

    /**
     * Build a cacheable response with proper headers
     */
    private function buildResponse(string $content, string $contentType): Response
    {
        $etag = '"'.md5($content).'"';

        return response($content, 200, [
            'Content-Type' => $contentType.'; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'ETag' => $etag,
        ]);
    }
}
