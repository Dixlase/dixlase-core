<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\DTO\Front;

use Illuminate\Contracts\Support\Renderable;
use JsonSerializable;

/**
 * Front page source render result DTO
 *
 * What a provider hands back from renderFrontPageBody(): the body of the front
 * page, plus the things a body cannot express on its own.
 *
 * A plugin's own front-end view is a whole page — it extends the theme layout, sets
 * the title, and pushes its stylesheets. None of that survives being placed inside a
 * theme's own layout, so those concerns travel here instead and the theme applies
 * them where they belong.
 *
 * The content is trusted HTML: the provider is responsible for escaping it, exactly
 * as it is when it renders its own page.
 *
 * @see \App\Contracts\Front\FrontPageBodyProviderInterface
 */
final readonly class FrontPageBodyResult implements JsonSerializable
{
    /**
     * @param  Renderable|string  $content  The body, without a surrounding layout
     * @param  string|null  $title  Page title for the document head; null keeps the theme's
     * @param  string|null  $description  Meta description; null keeps the theme's
     * @param  array<int, string>  $styleUrls  Stylesheet URLs the body needs
     * @param  array<int, string>  $scriptUrls  Script URLs the body needs
     * @param  string|null  $canonicalUrl  Canonical URL when this body is also served at its
     *                                     own address, so the two do not compete
     * @param  bool  $noStore  True when the response must not be stored by caches —
     *                         an unpublished page shown to a signed-in editor, for one
     * @param  string|null  $adminEditUrl  Where an editor edits this content
     * @param  string|null  $adminEditLabel  Label for that link (recommended: a translation key)
     * @param  array<string, mixed>  $meta  Provider-specific metadata
     */
    public function __construct(
        public Renderable|string $content,
        public ?string $title = null,
        public ?string $description = null,
        public array $styleUrls = [],
        public array $scriptUrls = [],
        public ?string $canonicalUrl = null,
        public bool $noStore = false,
        public ?string $adminEditUrl = null,
        public ?string $adminEditLabel = null,
        public array $meta = [],
    ) {}

    /**
     * Get the body as an HTML string.
     *
     * Renders a Renderable; returns a string as given. A theme echoes this unescaped,
     * so the provider has already escaped whatever came from a user.
     */
    public function html(): string
    {
        return $this->content instanceof Renderable
            ? $this->content->render()
            : $this->content;
    }

    /**
     * Whether the body would render nothing visible.
     *
     * A provider that has content to offer but nothing to show right now — a listing
     * with no published entries — returns a result rather than null, and a theme can
     * use this to skip the surrounding section markup.
     */
    public function isEmpty(): bool
    {
        return trim($this->html()) === '';
    }

    /**
     * Note that this renders the body, so a caller that only wants the metadata
     * should read the properties instead.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'content' => $this->html(),
            'title' => $this->title,
            'description' => $this->description,
            'style_urls' => $this->styleUrls,
            'script_urls' => $this->scriptUrls,
            'canonical_url' => $this->canonicalUrl,
            'no_store' => $this->noStore,
            'admin_edit_url' => $this->adminEditUrl,
            'admin_edit_label' => $this->adminEditLabel,
            'meta' => $this->meta,
        ];
    }
}
