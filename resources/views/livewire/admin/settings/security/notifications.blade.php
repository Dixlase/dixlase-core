{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the  implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

<div class="mx-auto">
    <h2 class="text-xl font-bold mb-4">テスト: 最小限のLivewireフォーム</h2>
    
    <form wire:submit="save">
        <div class="mb-4">
            <label class="flex items-center space-x-2">
                <input type="checkbox" wire:model="notificationEnabled" class="rounded">
                <span>エラー通知を有効にする (現在: {{ $notificationEnabled ? 'ON' : 'OFF' }})</span>
            </label>
        </div>
        
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
            保存
        </button>
    </form>
    
    <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-800 rounded">
        <p>デバッグ情報:</p>
        <p>notificationEnabled: {{ $notificationEnabled ? 'true' : 'false' }}</p>
        <p>Livewire ID: {{ $this->getId() }}</p>
    </div>
</div>
