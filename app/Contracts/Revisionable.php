<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
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

declare(strict_types=1);

namespace App\Contracts;

/**
 * 各プラグイン/テーマは自身のリビジョンテーブルと Eloquent モデルを持ちつつ、
 * このインターフェースを実装することで共通の `RevisionService` によって
 * 履歴記録・復元・自動削除・保護を統一的に扱える。
 *
 * テーブル命名規則:
 * - コア: `{entity}_revisions`（例: `front_page_revisions`）
 * - プラグイン: `dls_plg_{slug}_{entity}_revisions`
 * - テーマ: `dls_thm_{slug}_{entity}_revisions`
 *
 * 各リビジョンテーブルは以下のカラムを持つこと:
 * - id (bigint, PK)
 * - {foreignKey} (bigint, 親コンテンツへの FK、CASCADE DELETE)
 * - snapshot (json, `revisionableFields()` のスナップショット)
 * - type (string, 'auto' | 'manual' | 'restore_backup')
 * - note (string, nullable)
 * - is_protected (boolean, default false)
 * - created_by (bigint, nullable FK to members)
 * - created_at (timestamp)
 */
interface Revisionable
{
    /**
     * リビジョンを格納する Eloquent モデルの完全修飾クラス名を返す。
     *
     * 例: `\App\Models\FrontPageRevision::class`
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    public function revisionModel(): string;

    /**
     * リビジョンテーブルに存在する、親コンテンツを指す外部キーカラム名を返す。
     *
     * 例: 'front_page_id'
     */
    public function revisionForeignKey(): string;

    /**
     * スナップショットに含めるコンテンツ側の属性名リスト。
     *
     * ここで列挙したカラムのみが `snapshot` JSON に保存され、
     * 復元時にも同じカラムのみが書き戻される。
     *
     * @return list<string>
     */
    public function revisionableFields(): array;
}
