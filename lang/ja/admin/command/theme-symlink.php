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
    'description' => 'テーマアセットのシンボリックリンクを管理します',
    'invalid_action' => '無効なアクションです。"create" または "remove" を使用してください。',
    'theme_required' => 'テーマ引数は --all を指定しない場合は必須です。',
    'theme_and_all_conflict' => 'テーマ引数と --all は同時に指定できません。どちらか一方を使用してください。',
    'created' => 'テーマのシンボリックリンクを作成しました: :theme',
    'removed' => 'テーマのシンボリックリンクを削除しました: :theme',
    'all_summary_create' => 'テーマのシンボリックリンクを整理しました — 作成: :created 件、スキップ: :skipped 件（既にリンク済みまたは resources/assets なし）。',
    'all_summary_remove' => 'テーマのシンボリックリンクを削除しました — 合計: :removed 件。',
];
