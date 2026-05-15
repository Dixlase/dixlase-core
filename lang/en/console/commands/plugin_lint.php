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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
    'add_csp_nonce_or_separate_js' => 'Add <script @cspNonce> or separate into external JS file',
    'align_permissions_with_state' => 'Align permissions with actual state using dls:plugin:sync --write',
    'auto_fix_author_id_set' => '  <fg=green>✓ auto-fix:</> Set author_id to \':default\'',
    'auto_fix_authority_key_id_set' => '  <fg=green>✓ auto-fix:</> Set authority_key_id to \':default\'',
    'auto_fix_results_note' => '  <fg=cyan>※ Results after auto-fix applied. Remaining items require manual correction.</>',
    'auto_fix_sync_permissions_declares' => '  <fg=green>✓ auto-fix:</> Syncing permissions / declares (:plugin)',
    'auto_insert_config_defaults' => 'Auto-insert config defaults with dls:plugin:lint --fix',
    'auto_update_permissions' => 'Auto-update permissions with dls:plugin:sync --write',
    'health_score_display' => '  <fg=:color>Health Score: :score / 100</>',
    'move_inline_styles_to_css' => 'Move inline styles to CSS file',
    'no_issues_detected' => '  <fg=green>✓ No issues detected</>',
    'plugin_health_check' => '<fg=cyan>🔍 Health check for :plugin</>',
    'set_security_preset_development' => 'Set security_preset to development during development to avoid point deductions',
    'sign_with_dixlase_signer' => 'Sign with DixlaseSigner to achieve 100 points in production',
];
