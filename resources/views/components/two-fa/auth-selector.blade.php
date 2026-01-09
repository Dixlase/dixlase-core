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
    'name' => 'two_fa_mode',
    'value' => '0',
    'globalSetting' => null, // 全体設定の値（0=無効, 1=異なる端末時のみ, 2=常に有効, 3=プロフィール設定に従う）
    'excludeUseProfileSetting' => false, // プロフィール設定に従う選択肢を除外するか
    'twoFaPasskeyGloballyEnabled' => false, // パスキーが全体で有効か
    'twoFaPasskeyEnabled' => true, // パスキーが個別に有効か
    'twoFaDefaultMethod' => '0', // デフォルトの二段階認証方法（0=メール, 1=パスキー）
    'columns' => 3,
    'globalSettingsUrl' => null, // 全体設定へのリンクURL（nullの場合は注意書きを非表示）
])

{{-- 1. 二段階認証モード --}}
<x-two-fa.mode-selector
    :name="$name"
    :value="$value"
    :globalSetting="$globalSetting"
    :excludeUseProfileSetting="$excludeUseProfileSetting"
    :columns="$columns"
/>

{{-- 2. 二段階認証方法（メール認証・パスキー設定） --}}
<x-two-fa.method-selector
    name="two_fa_passkey_mode"
    :value="(string) ($twoFaPasskeyEnabled ? '1' : '0')"
    :columns="3"
    :globalSettingsUrl="$globalSettingsUrl"
/>

{{-- 3. デフォルトの認証方法（パスキーが有効な場合のみ表示） --}}
@if($twoFaPasskeyGloballyEnabled || $twoFaPasskeyEnabled)
    <x-two-fa.default-method
        :twoFaPasskeyEnabled="$twoFaPasskeyEnabled"
        :twoFaDefaultMethod="$twoFaDefaultMethod"
        :columns="2"
    />
@endif
