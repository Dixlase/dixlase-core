<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\View\Components;

use App\Helpers\DateTimeHelper;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Convert datetime stored in UTC to site_settings.display_timezone and
 * render it as a <time> element.
 *
 * Usage:
 *   <x-ui-datetime :value="$model->created_at" />
 *   <x-ui-datetime :value="$dt" format="date" />
 *   <x-ui-datetime :value="$dt" format="Y/m/d H:i" empty-label="—" />
 */
class UiDatetime extends Component
{
    public ?string $display;

    public ?string $isoUtc;

    public ?string $titleText;

    /**
     * @param  \Carbon\Carbon|\DateTimeInterface|string|int|null  $value  Input datetime (interpreted as UTC)
     * @param  string  $format  PHP date format or a DateTimeHelper::FORMATS key
     * @param  string|null  $emptyLabel  Fallback label shown when the value is null or empty
     */
    public function __construct(
        mixed $value = null,
        public string $format = 'datetime',
        public ?string $emptyLabel = null,
    ) {
        $this->display = DateTimeHelper::display($value, $format);
        $this->isoUtc = DateTimeHelper::toIsoUtc($value);
        $this->titleText = DateTimeHelper::display($value, 'full');
    }

    public function render(): View
    {
        return view('components.ui-datetime');
    }
}
