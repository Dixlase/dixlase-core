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

namespace App\View\Components;

use App\Helpers\DateTimeHelper;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * A datetime field that means a definite instant.
 *
 * The input-side counterpart of <x-ui-datetime>. Dixlase stores and
 * compares datetimes in UTC and converts to the display timezone for
 * display, but an <input type="datetime-local"> submits a wall clock with
 * no offset — it means nothing until a timezone is chosen for it. Every
 * form that wrote that conversion by hand dropped it, so the field alone
 * meant UTC while the rest of the screen showed the display timezone,
 * without saying so.
 *
 * The clock is resolved, not fixed, so one component serves an admin
 * screen and a screen showing a member their own data:
 *
 *   1. the `timezone` prop, when the caller already knows
 *   2. `context="viewer"`, the viewing member's own timezone through a
 *      registered App\Contracts\ViewerTimezoneResolver
 *   3. otherwise the site's display timezone — the default, and today's
 *      behaviour
 *
 * Save the value with DateTimeHelper::fromInput(), resolving the timezone
 * the same way, usually in a Form Request's prepareForValidation() so the
 * rules compare normalized values. A field shown in one timezone and saved
 * as another stops round-tripping, and the failure is silent: the form
 * reads back the value it wrote, and only the rendered page disagrees.
 *
 * Usage:
 *   <x-form-datetime name="published_at" :value="$post->published_at" />
 *   <x-form-datetime name="starts_at" :value="$v" context="viewer" />
 *   <x-form-datetime name="starts_at" :value="$v" :show-timezone="false" />
 */
class FormDatetime extends Component
{
    /**
     * The timezone the field is shown and read in.
     */
    public string $fieldTimezone;

    /**
     * The value as the field wants it, or null when there is none.
     */
    public ?string $fieldValue;

    /**
     * The min / max bounds as the field wants them.
     */
    public ?string $minValue;

    public ?string $maxValue;

    /**
     * @param  \Carbon\CarbonImmutable|\DateTimeInterface|string|int|null  $value  Stored value, interpreted as UTC
     * @param  string|null  $timezone  An explicit identifier, which wins over $context
     * @param  string  $context  'site' (default) or 'viewer'
     * @param  bool  $showTimezone  Name the timezone under the field
     */
    public function __construct(
        mixed $value = null,
        public ?string $name = null,
        public ?string $id = null,
        public ?string $timezone = null,
        public string $context = 'site',
        public bool $showTimezone = true,
        public ?string $min = null,
        public ?string $max = null,
        public bool $required = false,
        public bool $disabled = false,
        public ?string $ariaDescribedby = null,
        public ?string $xModel = null,
    ) {
        $this->fieldTimezone = DateTimeHelper::fieldTimezone($timezone, $context);

        // old() hands back whatever was flashed, which for a normalized
        // field is the UTC string: the same conversion covers it, so a
        // redisplayed form needs no second branch here.
        $this->fieldValue = DateTimeHelper::forInput($value, $this->fieldTimezone);

        // The bounds a datetime-local field compares against are wall
        // clocks in the field's own timezone, so a stored UTC bound is
        // converted too, or the browser refuses the wrong values.
        $this->minValue = DateTimeHelper::forInput($min, $this->fieldTimezone);
        $this->maxValue = DateTimeHelper::forInput($max, $this->fieldTimezone);

        $this->id ??= $this->name;
    }

    public function render(): View
    {
        return view('components.form-datetime');
    }
}
