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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@props([
    'button_class' => 'flex text-left items-center px-4 py-2 text-sm font-medium rounded-md focus:outline-none w-full',
    'arrow_class' => 'w-4 h-4 ml-auto transform'
])

<div class="flex h-full sm:pt-12">
    {{-- サイドバー本体 --}}
    <div class="flex flex-col w-64 h-full overflow-y-auto bg-white/75 dark:bg-gray-900/75 border-r border-gray-200 dark:border-gray-600 backdrop-blur-sm shadow-md">
        <nav class="flex-1 px-4 py-4 space-y-1" role="navigation" aria-label="Admin navigation menu">
        @foreach (config('admin.nav') as $key => $item)
            @php
                // 現在のルート名を階層ごとに分割
                $current_route_parts = explode('.', $route_name);
                // 権限判定用のキーを生成（このメニュー項目のキー）
                $role_key = $key;
                // 開くべきアコーディオンを判定
                $open_key = 'open_' . $key;
                
                // プラグインルートの場合の判定を改善
                $is_open = false;
                if (strpos($route_name, '::') !== false) {
                    // プラグインルートの場合: admin.plugin-name::admin.controller.action
                    if (strpos($route_name, 'admin.') === 0) {
                        $without_admin = substr($route_name, 6); // 'admin.'を除去
                        [$plugin_namespace, $plugin_route] = explode('::', $without_admin, 2);
                        $plugin_parts = explode('.', $plugin_route);
                        // admin.controller.action の controller部分を取得
                        $is_open = isset($plugin_parts[1]) && $plugin_parts[1] === $key;
                    } else {
                        // 旧形式: plugin-name::admin.controller.action
                        [$plugin_namespace, $plugin_route] = explode('::', $route_name, 2);
                        $plugin_parts = explode('.', $plugin_route);
                        $is_open = isset($plugin_parts[1]) && $plugin_parts[1] === $key;
                    }
                    
                    // プラグインの子項目の場合の特別判定
                    if (!$is_open && isset($item['children'])) {
                        foreach ($item['children'] as $child_item) {
                            if (isset($child_item['route']) && $child_item['route'] === $route_name) {
                                $is_open = true;
                                break;
                            }
                        }
                    }
                } else {
                    // コアルートの場合: admin.controller.action
                    $is_open = isset($current_route_parts[1]) && $current_route_parts[1] === $key;
                }
            @endphp


            <div x-cloak x-data="{ {{ $open_key }} : {{ $is_open ? 'true' : 'false' }} }">
                @php
                    // ダッシュボードとプロフィールは全員アクセス可能
                    $is_public_menu = in_array($key, ['dashboard', 'profile']);
                    
                    // プラグインスラッグを取得
                    $plugin_slug = $item['plugin_slug'] ?? null;
                    
                    // 親項目の権限チェック
                    $has_permission = $is_public_menu || 
                                     \App\Helpers\AdminHelper::canEditMenuOrPlugin($plugin_slug, $role_key) || 
                                     \App\Helpers\AdminHelper::canViewMenuOrPlugin($plugin_slug, $role_key);
                    
                    // 親メニューに権限がない場合、子メニューの権限をチェック
                    if (!$has_permission && isset($item['children']) && is_array($item['children'])) {
                        foreach ($item['children'] as $child_key => $child_item) {
                            $child_role_key = $key . '.' . $child_key;
                            $child_plugin_slug = $child_item['plugin_slug'] ?? $plugin_slug;
                            
                            if (\App\Helpers\AdminHelper::canEditMenuOrPlugin($child_plugin_slug, $child_role_key) || 
                                \App\Helpers\AdminHelper::canViewMenuOrPlugin($child_plugin_slug, $child_role_key)) {
                                $has_permission = true;
                                break;
                            }
                        }
                    }
                    
                    // 親メニューがアコーディオン（ルートなし）で、子メニューが全て非表示の場合は親メニューも非表示
                    if ($has_permission && !isset($item['route']) && isset($item['children']) && is_array($item['children'])) {
                        $has_visible_children = false;
                        foreach ($item['children'] as $child_key => $child_item) {
                            $child_role_key = $key . '.' . $child_key;
                            $child_plugin_slug = $child_item['plugin_slug'] ?? $plugin_slug;
                            
                            $can_edit = \App\Helpers\AdminHelper::canEditMenuOrPlugin($child_plugin_slug, $child_role_key);
                            $can_view = \App\Helpers\AdminHelper::canViewMenuOrPlugin($child_plugin_slug, $child_role_key);
                            
                            // 子項目が表示可能かチェック
                            $child_is_visible = $can_edit || $can_view;
                            
                            // 子項目がさらに孫項目を持つ場合、孫項目の権限もチェック
                            if (!$child_is_visible && isset($child_item['children']) && is_array($child_item['children'])) {
                                foreach ($child_item['children'] as $grandchild_key => $grandchild_item) {
                                    $grandchild_role_key = $child_role_key . '.' . $grandchild_key;
                                    $grandchild_plugin_slug = $grandchild_item['plugin_slug'] ?? $child_plugin_slug;
                                    
                                    $gc_can_edit = \App\Helpers\AdminHelper::canEditMenuOrPlugin($grandchild_plugin_slug, $grandchild_role_key);
                                    $gc_can_view = \App\Helpers\AdminHelper::canViewMenuOrPlugin($grandchild_plugin_slug, $grandchild_role_key);
                                    
                                    if ($gc_can_edit || $gc_can_view) {
                                        $child_is_visible = true;
                                        break;
                                    }
                                }
                            }
                            
                            if ($child_is_visible) {
                                $has_visible_children = true;
                                break;
                            }
                        }
                        
                        if (!$has_visible_children) {
                            $has_permission = false;
                        }
                    }
                @endphp
                @if ($has_permission)
                    @if (isset($item['route']) && is_string($item['route']) && Route::has($item['route']))
                        <a href="{{ route($item['route']) }}"
                        class="{{ $button_class }} {{ $item['route'] === $route_name ? 'bg-gray-200 text-gray-900 font-bold border-blue-500 pl-3 rounded-md hover:bg-gray-300 hover:text-black dark:bg-gray-100 dark:text-black dark:hover:bg-gray-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 hover:text-black dark:hover:bg-gray-700 dark:hover:text-white' }} {{ empty($transitionEnabled) ? '' : 'transition-colors duration-500' }}"
                        role="menuitem">
                            <i class="{{ $item['icon'] }} mr-3" aria-hidden="true"></i>
                            <span>{{ __($item['text']) }}</span>
                        </a>
                    @else
                        <button @click="{{ $open_key }} = !{{ $open_key }}" 
                        class="{{ $button_class }} text-gray-700 dark:text-gray-300 hover:bg-gray-200 hover:text-black dark:hover:bg-gray-700 dark:hover:text-white {{ empty($transitionEnabled) ? '' : 'transition-colors duration-500' }}"
                        aria-expanded="false"
                        :aria-expanded="{{ $open_key }}.toString()">
                            <i class="{{ $item['icon'] }} mr-3" aria-hidden="true"></i>
                            <span>{{ __($item['text']) }}</span>
                            <svg class="{{ $arrow_class }}" :class="{ 'rotate-180': {{ $open_key }} }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    @endif
                @endif

                @if (isset($item['children']) && is_array($item['children']))
                    <div x-show="{{ $open_key }}" x-collapse class="ml-2 space-y-1" role="menu">
                        @foreach ($item['children'] as $child_key => $child_item)
                            @php
                                // 子項目の権限キーを生成（親キー.子キー）
                                $child_role_key = $key . '.' . $child_key;
                                $child_plugin_slug = $child_item['plugin_slug'] ?? $plugin_slug;
                                
                                // 子項目の権限チェック
                                $child_has_permission = \App\Helpers\AdminHelper::canEditMenuOrPlugin($child_plugin_slug, $child_role_key) || 
                                                       \App\Helpers\AdminHelper::canViewMenuOrPlugin($child_plugin_slug, $child_role_key);
                                
                                // 子項目に権限がない場合、孫項目の権限をチェック
                                if (!$child_has_permission && isset($child_item['children']) && is_array($child_item['children'])) {
                                    foreach ($child_item['children'] as $grandchild_key => $grandchild_item) {
                                        $grandchild_role_key = $child_role_key . '.' . $grandchild_key;
                                        $grandchild_plugin_slug = $grandchild_item['plugin_slug'] ?? $child_plugin_slug;
                                        
                                        if (\App\Helpers\AdminHelper::canEditMenuOrPlugin($grandchild_plugin_slug, $grandchild_role_key) || 
                                            \App\Helpers\AdminHelper::canViewMenuOrPlugin($grandchild_plugin_slug, $grandchild_role_key)) {
                                            $child_has_permission = true;
                                            break;
                                        }
                                    }
                                }
                                
                                // 子項目がアコーディオンの場合、表示可能な孫項目があるかチェック
                                if ($child_has_permission && !isset($child_item['route']) && isset($child_item['children']) && is_array($child_item['children'])) {
                                    $has_visible_grandchildren = false;
                                    foreach ($child_item['children'] as $grandchild_key => $grandchild_item) {
                                        $grandchild_role_key = $child_role_key . '.' . $grandchild_key;
                                        $grandchild_plugin_slug = $grandchild_item['plugin_slug'] ?? $child_plugin_slug;
                                        
                                        if (\App\Helpers\AdminHelper::canEditMenuOrPlugin($grandchild_plugin_slug, $grandchild_role_key) || 
                                            \App\Helpers\AdminHelper::canViewMenuOrPlugin($grandchild_plugin_slug, $grandchild_role_key)) {
                                            $has_visible_grandchildren = true;
                                            break;
                                        }
                                    }
                                    
                                    // 表示可能な孫項目がない場合は子項目も非表示
                                    if (!$has_visible_grandchildren) {
                                        $child_has_permission = false;
                                    }
                                }
                            @endphp
                            @if ($child_has_permission)
                                @if (isset($child_item['route']) && is_string($child_item['route']) && Route::has($child_item['route']))
                                    <a href="{{ route($child_item['route']) }}" 
                                    class="{{ $button_class }} {{ $child_item['route'] === $route_name ? 'bg-gray-200 text-gray-900 font-bold border-blue-500 pl-3 rounded-md hover:bg-gray-300 hover:text-black dark:bg-gray-100 dark:text-black dark:hover:bg-gray-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 hover:text-black dark:hover:bg-gray-700 dark:hover:text-white' }} {{ empty($transitionEnabled) ? '' : 'transition-colors duration-500' }}"
                                    role="menuitem">
                                        <i class="{{ $child_item['icon'] }} mr-3" aria-hidden="true"></i>
                                        <span>{{ __($child_item['text']) }}</span>
                                    </a>

                                @else
                                    @php
                                        $current_child_route_parts = explode('.', $route_name);
                                        $open_child_key = 'open_' . $child_key;
                                        $is_open_child = isset($current_child_route_parts[2]) && $current_child_route_parts[2] === $child_key;
                                        
                                        // 孫要素のルートが現在のルートと一致する場合も開く
                                        if (!$is_open_child && isset($child_item['children']) && is_array($child_item['children'])) {
                                            foreach ($child_item['children'] as $gc_item) {
                                                if (isset($gc_item['route']) && $gc_item['route'] === $route_name) {
                                                    $is_open_child = true;
                                                    break;
                                                }
                                            }
                                        }
                                    @endphp

                                    @if(isset($child_item['icon']) && is_string($child_item['icon']) && isset($child_item['text']) && is_string($child_item['text']))
                                    <div x-data="{ {{ $open_child_key }}: {{ $is_open_child ? 'true' : 'false' }} }">
                                        <button @click="{{ $open_child_key }} = !{{ $open_child_key }}" class="{{ $button_class }} sidebar-link">
                                            <i class="{{ $child_item['icon'] }} mr-3"></i>
                                            <span>{{ __($child_item['text']) }}</span>
                                            <svg class="{{ $arrow_class }}" :class="{ 'rotate-180': {{ $open_child_key }} }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        @if (isset($child_item['children']) && is_array($child_item['children']))
                                            <div x-show="{{ $open_child_key }}" x-collapse class="ml-2 space-y-1">
                                                @foreach ($child_item['children'] as $grand_child_key => $grand_child_item)
                                                    @php
                                                        // 孫項目の権限キーを生成（親キー.子キー.孫キー）
                                                        $grand_child_role_key = $key . '.' . $child_key . '.' . $grand_child_key;
                                                        $grand_child_plugin_slug = $grand_child_item['plugin_slug'] ?? $child_plugin_slug;
                                                    @endphp
                                                    @if (isset($grand_child_item['route']) && is_string($grand_child_item['route']) && Route::has($grand_child_item['route']) && isset($grand_child_item['icon']) && is_string($grand_child_item['icon']) && isset($grand_child_item['text']) && is_string($grand_child_item['text']) && (\App\Helpers\AdminHelper::canEditMenuOrPlugin($grand_child_plugin_slug, $grand_child_role_key) || \App\Helpers\AdminHelper::canViewMenuOrPlugin($grand_child_plugin_slug, $grand_child_role_key)))
                                                        <a href="{{ route($grand_child_item['route']) }}"
                                                        class="{{ $button_class }} {{ $grand_child_item['route'] === $route_name ? 'sidebar-link-active' : 'sidebar-link' }}">
                                                            <i class="{{ $grand_child_item['icon'] }} mr-3"></i>
                                                            <span>{{ __($grand_child_item['text']) }}</span>
                                                            @if (!\App\Helpers\AdminHelper::canEditMenuOrPlugin($grand_child_plugin_slug, $grand_child_role_key))
                                                                <span class="text-xs text-gray-400">(閲覧のみ)</span>
                                                            @endif
                                                        </a>
                                                    {{-- 4階層目: 孫項目がさらに子を持つ場合 --}}
                                                    @elseif (isset($grand_child_item['children']) && is_array($grand_child_item['children']) && isset($grand_child_item['icon']) && isset($grand_child_item['text']))
                                                        @php
                                                            $open_grand_child_key = 'open_' . $grand_child_key;
                                                            $is_open_grand_child = false;
                                                            // 曾孫要素のルートが現在のルートと一致する場合は開く
                                                            foreach ($grand_child_item['children'] as $ggc_item) {
                                                                if (isset($ggc_item['route']) && $ggc_item['route'] === $route_name) {
                                                                    $is_open_grand_child = true;
                                                                    break;
                                                                }
                                                            }
                                                        @endphp
                                                        <div x-data="{ {{ $open_grand_child_key }}: {{ $is_open_grand_child ? 'true' : 'false' }} }">
                                                            <button @click="{{ $open_grand_child_key }} = !{{ $open_grand_child_key }}" class="{{ $button_class }} sidebar-link">
                                                                <i class="{{ $grand_child_item['icon'] }} mr-3"></i>
                                                                <span>{{ __($grand_child_item['text']) }}</span>
                                                                <svg class="{{ $arrow_class }}" :class="{ 'rotate-180': {{ $open_grand_child_key }} }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                                </svg>
                                                            </button>
                                                            <div x-show="{{ $open_grand_child_key }}" x-collapse class="ml-2 space-y-1">
                                                                @foreach ($grand_child_item['children'] as $great_grand_child_key => $great_grand_child_item)
                                                                    @php
                                                                        $great_grand_child_role_key = $key . '.' . $child_key . '.' . $grand_child_key . '.' . $great_grand_child_key;
                                                                    @endphp
                                                                    @if (isset($great_grand_child_item['route']) && Route::has($great_grand_child_item['route']) && isset($great_grand_child_item['icon']) && isset($great_grand_child_item['text']) && (\App\Helpers\AdminHelper::canEditMenu($great_grand_child_role_key) || \App\Helpers\AdminHelper::canViewMenu($great_grand_child_role_key)))
                                                                        <a href="{{ route($great_grand_child_item['route']) }}"
                                                                        class="{{ $button_class }} {{ $great_grand_child_item['route'] === $route_name ? 'sidebar-link-active' : 'sidebar-link' }}">
                                                                            <i class="{{ $great_grand_child_item['icon'] }} mr-3"></i>
                                                                            <span>{{ __($great_grand_child_item['text']) }}</span>
                                                                        </a>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    @endif
                                @endif
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </nav>
    </div>

    {{-- タブボタン（モバイルのみ、サイドバーの右端） --}}
    <button @click="openSidebar = !openSidebar"
            class="sm:hidden backdrop-blur-sm dark:bg-gray-900/75 bg-white/75 text-blue-400 dark:text-blue-400 px-1.5 py-4 rounded-r-lg shadow-md border border-l-0 border-gray-300 dark:border-gray-500 transition-colors self-start mt-2"
            aria-label="Toggle sidebar menu">
        <i class="fas text-sm" :class="openSidebar ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
    </button>
</div>
