<?php

/**
 * This file is part of Dixlase Core DevKit.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/*
 * Dixlase project glossary for AI-assisted comment translation (Japanese build).
 *
 * The English source ships as the canonical form. When `dls:comment:build`
 * (or `convert-comments.sh ja`) renders a Japanese version of the source,
 * Claude API uses this glossary to keep project-specific or ambiguous
 * terms consistent across files.
 *
 * Scope rules:
 * - Only Dixlase-specific terms or terms whose default AI translation has
 *   drifted across files should be added here.
 * - General programming vocabulary (controller, model, service, database,
 *   etc.) is left to the AI's own knowledge — adding common terms here
 *   bloats the prompt without improving quality.
 * - Keys are the canonical English rendering used in source comments.
 *   Values are the preferred Japanese translation for this project.
 *
 * Adding a new entry:
 * - Add an entry whenever a new Dixlase-specific term emerges OR whenever
 *   you notice the AI choosing inconsistent Japanese variants for the
 *   same English term.
 * - Re-run `dls:comment:translate --force` on affected files to re-translate
 *   under the updated glossary.
 *
 * This file is special: it lives in the same directory as translation
 * dictionaries but is filtered out by `TranslationFileService` (file name
 * starts with `_`) so it does not pollute the translation set itself.
 *
 * Future locales (e.g. zh, ko) will live alongside this file as
 * `resources/comment-translations/{locale}/_glossary.php`, each owning
 * its own preferred translations for the same English keys.
 */

return [
    // ===== Dixlase-specific entities =====
    'Core' => 'コア',                       // Capitalized refers to Dixlase Core
    'plugin' => 'プラグイン',                // General; capitalize at sentence start
    'theme' => 'テーマ',                     // General; capitalize at sentence start
    'admin panel' => '管理画面',             // Not "admin dashboard" / "admin area"
    'member' => 'メンバー',                  // End user (NOT "user", which is for system-level concepts)
    'user' => 'ユーザー',                    // System concept only; for end users, use "member"
    'member role' => 'メンバーロール',       // Permissions for Member → Plugin features
    'plugin permission' => 'プラグイン権限', // Plugin → System resource access
    'role' => 'ロール',                      // Named bundles of permissions
    'permission' => '権限',                  // Single capability

    // ===== Governance terms =====
    'assignment' => '譲渡',                  // CAA context (deprecated since v0.1.0; CLA model is the current standard)
    'license grant' => '許諾',               // CLA context
    'freeze' => '凍結',                      // e.g. API freeze
    'audit' => '監査',
    'stance' => 'スタンス',
    'dual license' => 'デュアルライセンス',
    'commercial license' => '商用ライセンス',
    'copyright' => '著作権',
    'governance' => 'ガバナンス',
    'industry standard' => '業界標準',

    // ===== Context-sensitive verbs/nouns =====
    'retrieve' => '取得する',                // Prefer "retrieve" over "get/fetch" (AI may pick others contextually)
    'display' => '表示する',                 // Prefer "display" over "show/render"
    'settings' => '設定',                    // Not "configuration"
    'list' => '一覧',                        // Not "index" (route names are a separate concern)

    // ===== Dixlase-specific status words =====
    'public' => '公開',                      // Visibility (content publication is "publish")
    'private' => '非公開',
    'draft' => '下書き',
    'frozen' => '凍結中',
    'deprecated' => '廃止',

    // ===== Disambiguation =====
    'administrator' => '管理者',             // Do not abbreviate to "admin" in formal text
    'implement' => '実装する',
    'provide' => '提供する',
];
