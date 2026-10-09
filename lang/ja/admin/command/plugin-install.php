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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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
    'description' => 'プラグインをインストールし、データベースに登録し、マイグレーションを実行し、オートロードを更新します。',
    'stale_download' => '\':slug\' の v:latest が公開されています(ダウンロード済みのものは v:version)。最新を入れるには、削除してからダウンロードし直してください。',
    'installed' => 'プラグイン :pluginName をインストールしました。',
    'migrating' => 'マイグレーションを実行中...',
    'seeding' => 'シーダーを実行中...',
    'enable_confirm' => 'プラグイン :pluginName を有効化しますか?',
    'enable_skipped' => 'プラグイン :pluginName は有効化されませんでした。後で有効化するには `php artisan dls:plugin:enable :pluginName` を実行してください。',
    'composer_parse_error' => 'composer.json の解析に失敗しました: :error',
];
