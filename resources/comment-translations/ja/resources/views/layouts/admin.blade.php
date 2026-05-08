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
 *       Dixlase Plugin and Theme Exception (see LICENSE-EXCEPTIONS for
 *       full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms above.
 */

return [
    '{{-- Admin panel banner stack (maintenance / safe mode / system warnings) --}}' => '{{-- 管理画面バナースタック（メンテナンス / セーフモード / システム警告） --}}',
    '{{-- Common modal for CSRF session expiration (opens automatically when fetch returns 419) --}}' => '{{-- CSRF セッション切れ時の共通モーダル（fetch が 419 を返すと自動で開く） --}}',
    '{{-- Notification component (load before other scripts) --}}' => '{{-- 通知コンポーネント（他のスクリプトより先に読み込み） --}}',
    '{{-- Prevent FOUC: Apply dark mode class immediately before CSS and Alpine.js load --}}' => '{{-- FOUC防止: CSSやAlpine.jsの読み込み前に即座にダークモードクラスを適用 --}}',
    '{{-- Prevent FOUC: Apply margin and sidebar display immediately based on sidebar state --}}' => '{{-- FOUC防止: サイドバー状態に応じてマージンとサイドバー表示を即座に適用 --}}',
    '{{-- Propagate banner stack height to offset of admin bar, sidebar, and main content --}}' => '{{-- バナースタックの高さを管理バー・サイドバー・本文のオフセットに伝播させる --}}',
    '{{-- Right sidebar overlay on mobile --}}' => '{{-- モバイル時の右サイドバーオーバーレイ --}}',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '{{-- Admin panel banner stack (maintenance / safe mode / system warnings) --}}' => 'machine',
        '{{-- Common modal for CSRF session expiration (opens automatically when fetch returns 419) --}}' => 'machine',
        '{{-- Notification component (load before other scripts) --}}' => 'machine',
        '{{-- Prevent FOUC: Apply dark mode class immediately before CSS and Alpine.js load --}}' => 'machine',
        '{{-- Prevent FOUC: Apply margin and sidebar display immediately based on sidebar state --}}' => 'machine',
        '{{-- Propagate banner stack height to offset of admin bar, sidebar, and main content --}}' => 'machine',
        '{{-- Right sidebar overlay on mobile --}}' => 'machine',
    ],
];
