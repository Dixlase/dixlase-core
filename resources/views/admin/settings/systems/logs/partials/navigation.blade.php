{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
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

{{--
    ログナビゲーションパーシャル
    
    @param string $logType - 現在のログタイプ
    @param string|null $currentView - 監査ログの現在のビュー（'db' or 'file'）
    @param string $pageType - ページタイプ（'system' or 'audit'）
--}}

<!-- Log Type Selection -->
<div class="mb-4">
    @if($pageType === 'system')
        {{-- ファイルログページ用ナビゲーション --}}
        <h2>{{ __('admin/settings/systems/logs/index.log_type_label') }}</h2>
        
        <!-- 大カテゴリボタン -->
        <div class="mb-4">
            <nav class="flex flex-wrap gap-2 justify-center md:justify-start">
                <x-form-button
                    type="link"
                    :variant="$currentCategory === 'admin' ? 'primary' : 'tertiary'"
                    :href="route('admin.settings.systems.logs.files', ['type' => 'activity'])"
                    :label="__('admin/settings/systems/logs/files.admin_logs_label')"
                />
                <x-form-button
                    type="link"
                    :variant="$currentCategory === 'front' ? 'primary' : 'tertiary'"
                    :href="route('admin.settings.systems.logs.files', ['type' => 'front_activity'])"
                    :label="__('admin/settings/systems/logs/files.front_logs_label')"
                />
                <x-form-button
                    type="link"
                    :variant="$currentCategory === 'security' ? 'primary' : 'tertiary'"
                    :href="route('admin.settings.systems.logs.files', ['type' => 'csp'])"
                    :label="__('admin/settings/systems/logs/files.security_logs_label')"
                />
                <x-form-button
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
                        <x-form-button
                            type="link"
                            :variant="$logType === $type ? 'success' : 'tertiary'"
                            :href="route('admin.settings.systems.logs.files', ['type' => $type])"
                            :label="__('admin/settings/systems/logs/files.' . $type)"
                        />
                    @endforeach
                @elseif($currentCategory === 'front')
                    @foreach ($frontTypes as $type)
                        <x-form-button
                            type="link"
                            :variant="$logType === $type ? 'success' : 'tertiary'"
                            :href="route('admin.settings.systems.logs.files', ['type' => $type])"
                            :label="__('admin/settings/systems/logs/files.' . $type)"
                        />
                    @endforeach
                @elseif($currentCategory === 'security')
                    @foreach ($securityTypes as $type)
                        <x-form-button
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
