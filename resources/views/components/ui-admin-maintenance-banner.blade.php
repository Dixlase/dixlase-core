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
