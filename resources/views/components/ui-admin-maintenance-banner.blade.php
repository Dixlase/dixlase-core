{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-ui-admin-maintenance-banner />

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

{{--
管理画面用のメンテナンスモード中バナー（スティッキー表示）
--}}
@php
    // データベースが存在しない場合は何も表示しない
    try {
        // メンテナンスモード設定を取得
        $maintenanceMode = DB::table('base_settings')
            ->where('name', 'maintenance_mode')
            ->value('value');
        
        $maintenanceMessage = DB::table('base_settings')
            ->where('name', 'maintenance_message')
            ->value('value');
        
        $maintenanceReleaseAt = DB::table('base_settings')
            ->where('name', 'maintenance_release_at')
            ->value('value');
        
        $showBanner = $maintenanceMode === '1';
    } catch (\Exception $e) {
        $showBanner = false;
    }
@endphp

@if($showBanner)
<div id="admin-maintenance-banner" class="fixed left-0 right-0 bg-yellow-500 dark:bg-yellow-600 text-white px-4 py-3 shadow-md z-[9999]" style="top: 0;">
    <div class="max-w-full mx-auto flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <i class="fas fa-exclamation-triangle text-xl"></i>
            <div>
                <div class="font-semibold">
                    {{ __('maintenance.banner_title') }}
                </div>
                <div class="text-sm opacity-90">
                    {{ $maintenanceMessage ?? __('maintenance.default_message') }}
                    @if($maintenanceReleaseAt)
                        <span class="ml-2">
                            {{ __('maintenance.expected_release') }}: {{ \Carbon\Carbon::parse($maintenanceReleaseAt)->format('Y/m/d H:i') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
        <a href="{{ route('admin.settings.base.maintenance') }}" class="px-4 py-2 bg-white text-yellow-600 dark:text-yellow-700 rounded hover:bg-gray-100 transition text-sm font-medium whitespace-nowrap">
            {{ __('maintenance.manage_settings') }}
        </a>
    </div>
</div>

@endif
