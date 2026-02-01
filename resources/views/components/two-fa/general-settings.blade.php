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
    'twoFaModeName' => 'two_fa_force_mode',
    'twoFaModeValue' => '3',
    'twoFaPasskeyModeName' => 'two_fa_passkey_mode',
    'twoFaPasskeyModeValue' => '2',
    'columns' => 4,
])

{{-- 全体設定画面用の二段階認証設定コンポーネント --}}
<div>
    {{-- 1. 二段階認証モード --}}
    <x-two-fa.mode-selector
        :name="$twoFaModeName"
        :value="old($twoFaModeName, (string) $twoFaModeValue)"
        :globalSetting="null"
        :excludeUseProfileSetting="false"
        :columns="$columns"
        xModel="twoFaMode"
    />

    {{-- 2. 二段階認証方法（メール認証・パスキー設定） --}}
    <div :class="{ 'opacity-50 pointer-events-none': !twoFaEnabled }">
        <x-two-fa.method-selector
            :name="$twoFaPasskeyModeName"
            :value="old($twoFaPasskeyModeName, (string) $twoFaPasskeyModeValue)"
            :columns="3"
            xModel="passkeyMode"
        />
    </div>
</div>
