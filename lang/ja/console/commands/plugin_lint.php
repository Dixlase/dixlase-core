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
    'add_csp_nonce_or_separate_js' => '<script @cspNonce> を付与するか外部 JS ファイルに分離してください',
    'align_permissions_with_state' => 'dls:plugin:sync --write で permissions を実態に合わせます',
    'auto_fix_author_id_set' => '  <fg=green>✓ auto-fix:</> author_id を \':default\' に設定しました',
    'auto_fix_authority_key_id_set' => '  <fg=green>✓ auto-fix:</> authority_key_id を \':default\' に設定しました',
    'auto_fix_results_note' => '  <fg=cyan>※ auto-fix 適用後の結果です。残った項目は手動修正が必要です。</>',
    'auto_fix_sync_permissions_declares' => '  <fg=green>✓ auto-fix:</> permissions / declares を同期します（:plugin）',
    'auto_insert_config_defaults' => 'dls:plugin:lint --fix で config の default を自動挿入します',
    'auto_update_permissions' => 'dls:plugin:sync --write で permissions を自動更新できます',
    'directory_namespace_mismatch' => '  <fg=red>✗ plugins/:directory が plugin.json の宣言する名前空間と一致していません。plugins/:expected であるべきです</>',
    'directory_namespace_mismatch_hint' => '    composer.local.json の PSR-4 前置きはディレクトリ名から作られ、Composer は前置きを大小文字を区別して照合するため、このプラグイン自身のクラスは最適化済みクラスマップ経由でしか解決しません。ディレクトリを :expected に改名し、plugins の行を更新してから `:command` を実行してください。',
    'health_score_display' => '  <fg=:color>ヘルススコア: :score / 100</>',
    'move_inline_styles_to_css' => 'インラインスタイルを CSS ファイルに移動してください',
    'no_issues_detected' => '  <fg=green>✓ 検出された問題はありません</>',
    'plugin_health_check' => '<fg=cyan>🔍 :plugin の健全性チェック</>',
    'set_security_preset_development' => '開発中であれば security_preset を development にすると減点を回避できます',
    'sign_with_dixlase_signer' => 'DixlaseSigner で署名すると本番で 100 点を達成できます',
];
