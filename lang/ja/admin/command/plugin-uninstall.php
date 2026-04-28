<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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
 */

return [

        'description' => 'プラグインをアンインストールし、データベースから削除します（ファイルは保持されます）。',
        'not_found' => 'プラグイン \':pluginName\' は見つかりません。',
        'still_enabled' => 'プラグイン \':pluginName\' は有効化されています。',
        'disable_first' => 'アンインストールする前に、まず `plugin:disable` コマンドでプラグインを無効化してください。',
        'force_disabling' => '--forceオプションが指定されたため、プラグイン \':pluginName\' を強制的に無効化します。',
        'confirm' => 'プラグイン \':pluginName\' をアンインストールしますか？この操作はデータベースからプラグイン情報を削除します。',
        'cancelled' => 'アンインストールがキャンセルされました。',
        'rollback_running' => 'マイグレーションのロールバックを実行中...',
        'rollback_confirm' => 'プラグイン \':pluginName\' に関連するデータベースのテーブルを削除しますか？',
        'rollback_skipped' => 'データベースのロールバックはスキップされました。',
        'files_preserved' => 'プラグインのファイルとディレクトリは保持されました。',
        'database_removed' => 'プラグイン \':pluginName\' をデータベースから削除しました。',
        'completed' => 'プラグイン \':pluginName\' のアンインストールが完了しました。',
        'delete_hint' => 'ファイルを削除するには `php artisan plugin:delete <directory>` コマンドを実行してください。',
];
