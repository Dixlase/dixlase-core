/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * メンテナンス設定ページ用JavaScript
 */

// ラジオボタン変更時にAlpineデータを更新
document.addEventListener('alpine:init', () => {
    document.querySelectorAll('input[name="maintenance_auto_release"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            const container = document.querySelector('[x-data]');
            if (container && container.__x) {
                container.__x.$data.autoRelease = e.target.value;
            }
        });
    });
});

// プレビュー機能をグローバルスコープに定義
window.previewMaintenance = function () {
    const el = document.getElementById('maintenance-settings');
    const previewUrl = el ? el.dataset.previewUrl : '';
    const defaultMessage = el ? el.dataset.defaultMessage : '';

    const message = document.querySelector('[name="maintenance_message"]').value;
    const releaseAt = document.querySelector('[name="maintenance_release_at"]').value;

    const params = new URLSearchParams({
        message: message || defaultMessage,
    });

    if (releaseAt) {
        params.append('release_at', releaseAt);
    }

    window.open(previewUrl + '?' + params.toString(), '_blank');
};
