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

namespace App\View\Components;

use App\Services\SystemWarningService;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * 汎用システム警告バナーコンポーネント
 *
 * SystemWarningService に登録された警告判定結果を元に、
 * レベル別（error/warning/info）の色分け済みバナーを積み重ね表示する。
 * 明示的に $banners 配列をprops指定することも可能。
 */
class UiSystemBanner extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $banners;

    /** @var array<string, array{bg: string, border: string, textOnBg: string}> */
    protected array $levelStyles = [
        'error' => [
            'bg' => 'bg-red-600',
            'border' => 'border-red-700',
            'textOnBg' => 'text-red-600',
        ],
        'warning' => [
            'bg' => 'bg-yellow-500',
            'border' => 'border-yellow-600',
            'textOnBg' => 'text-yellow-700',
        ],
        'info' => [
            'bg' => 'bg-blue-600',
            'border' => 'border-blue-700',
            'textOnBg' => 'text-blue-700',
        ],
    ];

    /**
     * @param  array<int, array<string, mixed>>|null  $banners
     */
    public function __construct(SystemWarningService $service, ?array $banners = null)
    {
        $source = $banners ?? $service->getActiveBanners();

        $this->banners = array_map(fn (array $banner): array => $this->normalize($banner), $source);
    }

    /**
     * バナーデータを描画用に正規化する
     *
     * @param  array<string, mixed>  $banner
     * @return array<string, mixed>
     */
    protected function normalize(array $banner): array
    {
        $level = $banner['level'] ?? 'info';
        if (! isset($this->levelStyles[$level])) {
            $level = 'info';
        }

        $actions = [];
        foreach ($banner['actions'] ?? [] as $action) {
            $style = (string) ($action['style'] ?? 'secondary');
            $textOnBg = $this->levelStyles[$level]['textOnBg'];
            $btnClass = match ($style) {
                'danger' => 'bg-red-800 hover:bg-red-900 text-white',
                'primary' => 'bg-white '.$textOnBg.' hover:bg-gray-100',
                default => 'bg-white/20 hover:bg-white/30 text-white border border-white/40',
            };
            $actions[] = [
                'label' => (string) ($action['label'] ?? ''),
                'url' => (string) ($action['url'] ?? '#'),
                'style' => $style,
                'method' => strtoupper((string) ($action['method'] ?? 'GET')),
                'btnClass' => $btnClass,
            ];
        }

        return [
            'level' => $level,
            'icon' => (string) ($banner['icon'] ?? 'fas fa-exclamation-triangle'),
            'title' => (string) ($banner['title'] ?? ''),
            'message' => (string) ($banner['message'] ?? ''),
            'actions' => $actions,
            'bgClass' => $this->levelStyles[$level]['bg'],
            'borderClass' => $this->levelStyles[$level]['border'],
            'textOnBgClass' => $this->levelStyles[$level]['textOnBg'],
        ];
    }

    public function shouldRender(): bool
    {
        return $this->banners !== [];
    }

    public function render(): View
    {
        return view('components.ui-system-banner');
    }
}
