{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

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
    Log navigation partial
    
    @param string $logType - Current log type
    @param string|null $currentView - Current view for audit logs ('db' or 'file')
    @param string $pageType - Page type ('system' or 'audit')
--}}

<!-- Log Type Selection -->
<div class="mb-4">
    @if($pageType === 'system')
        {{-- Navigation for file log page --}}
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
                {{-- Browser category has only one subcategory so don't display it --}}
                @endif
            </nav>
        </div>
    @else
        {{-- Navigation for audit log page --}}
        {{-- Only use horizontal navigation, so display nothing here --}}
    @endif
</div>
