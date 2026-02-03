{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@props([
    'name' => 'appearance',
    'value' => '0',
    'enableRealtimeSwitch' => false, // リアルタイム切り替えを有効にするか
    'columns' => 3,
    'color' => 'primary',
    'variant' => 'filled',
    'showCheck' => true,
])

@php
    use App\Enums\AppearanceMode;
    
    $appearanceOptions = [
        [
            'value' => (string) AppearanceMode::Auto->value,
            'label' => __('components.appearance_mode.auto'),
            'description' => __('components.appearance_mode.auto_description'),
            'icon' => 'fas fa-adjust'
        ],
        [
            'value' => (string) AppearanceMode::Light->value,
            'label' => __('components.appearance_mode.light'),
            'description' => __('components.appearance_mode.light_description'),
            'icon' => 'fas fa-sun'
        ],
        [
            'value' => (string) AppearanceMode::Dark->value,
            'label' => __('components.appearance_mode.dark'),
            'description' => __('components.appearance_mode.dark_description'),
            'icon' => 'fas fa-moon'
        ],
    ];
@endphp

@if($enableRealtimeSwitch)
    <div x-data="{
        localTheme: '{{ $value }}',
        savedTheme: '{{ $value }}',
        applyLocalTheme(enableTransition = false) {
            const isDark = this.localTheme === '2' || (this.localTheme === '0' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            
            // トランジションを有効にする
            if (enableTransition) {
                document.documentElement.classList.add('theme-ready');
            }
            
            // ダークモード/ライトモードのクラスを切り替え
            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.classList.toggle('light', !isDark);
        },
        resetToSavedTheme() {
            this.localTheme = this.savedTheme;
            this.applyLocalTheme(false);
        }
    }" x-init="
        // 初期化時に保存された値でDOMをリセット（トランジションなし）
        resetToSavedTheme();
        // localThemeの変更を監視してリアルタイムに反映
        $watch('localTheme', () => applyLocalTheme(true));
    ">
        <x-form-radio-card-group
            :name="$name"
            :options="$appearanceOptions"
            :value="$value"
            xModel="localTheme"
            :columns="$columns"
            :color="$color"
            :variant="$variant"
            :showCheck="$showCheck"
        />
    </div>
@else
    <x-form-radio-card-group
        :name="$name"
        :options="$appearanceOptions"
        :value="$value"
        :columns="$columns"
        :color="$color"
        :variant="$variant"
        :showCheck="$showCheck"
    />
@endif
