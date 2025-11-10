<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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

namespace App\Contracts\Repositories;

/**
 * メディア設定リポジトリインターフェース
 * 
 * メディア関連の設定（許可ファイルタイプ、最大サイズなど）を管理します。
 * 配列値は自動的にJSON形式で保存されます。
 */
interface MediaSettingRepositoryInterface extends SettingRepositoryInterface
{
    // 共通メソッドはSettingRepositoryInterfaceから継承
    // 必要に応じてメディア設定固有のメソッドをここに追加
}
