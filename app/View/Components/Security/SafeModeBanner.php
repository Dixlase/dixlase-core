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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\View\Components\Security;

use App\Services\SafeModeService;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * セーフモードバナーコンポーネント
 *
 * 有効なセーフモードごとにバナーを表示する。
 * SafeModeServiceからアクティブモードを取得し、
 * Bladeテンプレートにプリミティブなデータとして渡す。
 */
class SafeModeBanner extends Component
{
    /** @var array<int, array{value: string, sessionKey: string, bgClass: string, buttonClass: string, linkTextClass: string, iconClass: string, settingsRoute: string, translationPrefix: string}> */
    public array $activeModes;

    public function __construct(SafeModeService $safeModeService)
    {
        $this->activeModes = [];

        foreach ($safeModeService->getActiveModes() as $mode) {
            $this->activeModes[] = [
                'value' => $mode->value,
                'sessionKey' => $mode->sessionKey(),
                'bgClass' => $mode->bannerBgClass(),
                'buttonClass' => $mode->bannerButtonClass(),
                'linkTextClass' => $mode->bannerLinkTextClass(),
                'iconClass' => $mode->iconClass(),
                'settingsRoute' => $mode->settingsRoute(),
                'translationPrefix' => $mode->translationPrefix(),
            ];
        }
    }

    /**
     * コンポーネントを表示するかどうか
     */
    public function shouldRender(): bool
    {
        return ! empty($this->activeModes);
    }

    public function render(): View
    {
        return view('components.security.safe-mode-banner');
    }
}
