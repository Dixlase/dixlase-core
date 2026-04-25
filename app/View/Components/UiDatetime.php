<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api プラグイン/テーマから使用可能な安定APIです
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
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * UTC で保存された日時を base_settings.display_timezone に変換して
 * <time> 要素として描画するコンポーネント。
 *
 * 使い方:
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
     * @param  \Carbon\Carbon|\DateTimeInterface|string|int|null  $value  入力日時（UTC として解釈）
     * @param  string  $format  PHP date format か DateTimeHelper::FORMATS のキー
     * @param  string|null  $emptyLabel  null/空文字時の代替表示
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
