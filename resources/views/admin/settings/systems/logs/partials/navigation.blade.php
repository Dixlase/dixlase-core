{{--
    ログナビゲーションパーシャル
    
    @param string $logType - 現在のログタイプ
    @param string|null $currentView - 監査ログの現在のビュー（'db' or 'file'）
    @param string $pageType - ページタイプ（'system' or 'audit'）
--}}

@php
    $currentView = $currentView ?? request('view', 'db');
    $pageType = $pageType ?? 'system';
    
    // 現在のログタイプからカテゴリを判定
    $adminTypes = ['activity', 'error', 'dixlase'];
    $frontTypes = ['front_activity', 'front_error'];
    $securityTypes = ['csp', 'audit'];
    $browserTypes = ['browser'];
    
    $currentCategory = match(true) {
        in_array($logType, $adminTypes) => 'admin',
        in_array($logType, $frontTypes) => 'front',
        in_array($logType, $securityTypes) => 'security',
        in_array($logType, $browserTypes) => 'browser',
        default => 'admin',
    };
@endphp

<!-- Log Type Selection -->
<div class="mb-4">
    @if($pageType === 'system')
        {{-- ファイルログページ用ナビゲーション --}}
        <h2>{{ __('admin/settings/systems/logs/index.log_type_label') }}</h2>
        
        <!-- 大カテゴリボタン -->
        <div class="mb-4">
            <nav class="flex flex-wrap gap-2 justify-center md:justify-start">
                <x-form.button
                    type="link"
                    :variant="$currentCategory === 'admin' ? 'primary' : 'tertiary'"
                    :href="route('admin.settings.systems.logs.files', ['type' => 'activity'])"
                    :label="__('admin/settings/systems/logs/files.admin_logs_label')"
                />
                <x-form.button
                    type="link"
                    :variant="$currentCategory === 'front' ? 'primary' : 'tertiary'"
                    :href="route('admin.settings.systems.logs.files', ['type' => 'front_activity'])"
                    :label="__('admin/settings/systems/logs/files.front_logs_label')"
                />
                <x-form.button
                    type="link"
                    :variant="$currentCategory === 'security' ? 'primary' : 'tertiary'"
                    :href="route('admin.settings.systems.logs.files', ['type' => 'csp'])"
                    :label="__('admin/settings/systems/logs/files.security_logs_label')"
                />
                <x-form.button
                    type="link"
                    :variant="$currentCategory === 'browser' ? 'primary' : 'tertiary'"
                    :href="route('admin.settings.systems.logs.files', ['type' => 'browser'])"
                    :label="__('admin/settings/systems/logs/files.browser_logs_label')"
                />
            </nav>
        </div>
        
        <!-- 小カテゴリボタン -->
        <div class="mb-4">
            <nav class="flex flex-wrap gap-2 justify-center md:justify-start">
                @if($currentCategory === 'admin')
                    @foreach ($adminTypes as $type)
                        <x-form.button
                            type="link"
                            :variant="$logType === $type ? 'success' : 'tertiary'"
                            :href="route('admin.settings.systems.logs.files', ['type' => $type])"
                            :label="__('admin/settings/systems/logs/files.' . $type)"
                        />
                    @endforeach
                @elseif($currentCategory === 'front')
                    @foreach ($frontTypes as $type)
                        <x-form.button
                            type="link"
                            :variant="$logType === $type ? 'success' : 'tertiary'"
                            :href="route('admin.settings.systems.logs.files', ['type' => $type])"
                            :label="__('admin/settings/systems/logs/files.' . $type)"
                        />
                    @endforeach
                @elseif($currentCategory === 'security')
                    @foreach ($securityTypes as $type)
                        <x-form.button
                            type="link"
                            :variant="$logType === $type ? 'success' : 'tertiary'"
                            :href="route('admin.settings.systems.logs.files', ['type' => $type])"
                            :label="__('admin/settings/systems/logs/files.' . $type)"
                        />
                    @endforeach
                {{-- ブラウザカテゴリは小カテゴリが1つのみなので表示しない --}}
                @endif
            </nav>
        </div>
    @else
        {{-- 監査ログページ用ナビゲーション --}}
        {{-- 横のナビゲーションのみ使用するため、ここでは何も表示しない --}}
    @endif
</div>
