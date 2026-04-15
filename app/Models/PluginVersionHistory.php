<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * プラグインバージョン履歴モデル
 *
 * サプライチェーン攻撃防御のため、プラグインのインストール・アップデート・ロールバックを記録する。
 * β以降の異常検出（変更量異常・オーナー変更検出）の基礎データになる。
 */
class PluginVersionHistory extends Model
{
    /**
     * @var string
     */
    protected $table = 'plugin_version_history';

    public const METHOD_INSTALL = 'install';

    public const METHOD_UPDATE = 'update';

    public const METHOD_ROLLBACK = 'rollback';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'plugin_slug',
        'old_version',
        'new_version',
        'old_signing_key_id',
        'new_signing_key_id',
        'old_author_id',
        'new_author_id',
        'files_changed_count',
        'lines_added',
        'lines_removed',
        'signing_key_changed',
        'author_id_changed',
        'installation_method',
        'installed_from_url',
        'applied_by_id',
        'applied_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'files_changed_count' => 'integer',
        'lines_added' => 'integer',
        'lines_removed' => 'integer',
        'signing_key_changed' => 'boolean',
        'author_id_changed' => 'boolean',
        'applied_at' => 'datetime',
    ];

    /**
     * 適用した管理者（Member）との関連
     */
    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'applied_by_id');
    }
}
