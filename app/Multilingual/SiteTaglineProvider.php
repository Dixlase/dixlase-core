<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Multilingual;

use App\Contracts\Multilingual\TranslatableContentProvider;
use App\Helpers\ConfigHelper;
use App\Helpers\LocaleHelper;

/**
 * @api Registered by DixlaseMultilingual against `core:site-tagline`.
 *
 * Primary-locale value source for the core `site_tagline` singleton
 * translatable type.
 *
 * `site_tagline` is a single site-wide setting (see
 * {@see ConfigHelper::getSiteTagline()}) that appears in the document
 * `<title>` on the site root. DixlaseMultilingual registers this
 * provider against `core:site-tagline` (singleton cardinality) so that
 * per-locale translations can be authored in the central translation
 * editor while the primary-locale value remains sourced from
 * `SiteSetting`. The provider is read-only — the multilingual plugin
 * writes translations to its own table and calls this only to answer
 * "what should we show when no translation exists for the requested
 * locale?".
 *
 * Mirrors the plugin-side pattern used by
 * `Plugins\DixlaseInquiry\App\Multilingual\InquirySettingsProvider`,
 * but lives in core because `site_tagline` is a core setting and the
 * registration comes from DixlaseMultilingual's
 * `registerCoreTranslatableTypes()` rather than a plugin manifest.
 */
class SiteTaglineProvider implements TranslatableContentProvider
{
    /**
     * Only the `tagline` field is exposed. Returning null for any other
     * field lets the multilingual plugin skip it rather than serving a
     * stale primary value.
     */
    public function getPrimaryValue(string $field): ?string
    {
        if ($field !== 'tagline') {
            return null;
        }

        $value = ConfigHelper::getSiteTagline();

        // An empty string means the operator has not set a tagline;
        // treat that as "no primary value" so the translation editor
        // does not display an empty row as if it were the source text.
        return $value === '' ? null : $value;
    }

    /**
     * The tagline is stored in the site's primary locale. Fall back to
     * null when LocaleHelper is unavailable (very early boot); the
     * multilingual plugin treats null as "no primary locale configured"
     * and treats every enabled locale as translatable.
     */
    public function getPrimaryLocale(): ?string
    {
        try {
            return LocaleHelper::getSiteDefaultLocale();
        } catch (\Throwable) {
            return null;
        }
    }
}
