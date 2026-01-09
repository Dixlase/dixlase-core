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
    'twoFaPasskeyMode' => '2', // 全体設定のパスキーモード（0=無効, 1=有効, 2=プロフィール設定に従う）
    'columns' => 3,
    'globalSettingsUrl' => null, // 全体設定へのリンクURL（nullの場合は注意書きを非表示）
])

{{-- 個別設定用のコンポーネントを使用 --}}
<x-two-fa.individual-settings
    :twoFaModeName="$name"
    :twoFaModeValue="$value"
    :twoFaGlobalSetting="$globalSetting"
    :twoFaPasskeyEnabled="$twoFaPasskeyEnabled"
    :twoFaPasskeyMode="$twoFaPasskeyMode"
    :twoFaDefaultMethod="$twoFaDefaultMethod"
    :columns="$columns"
    :globalSettingsUrl="$globalSettingsUrl"
/>
