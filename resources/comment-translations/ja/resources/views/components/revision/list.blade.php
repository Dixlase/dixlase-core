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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms above.
 */

return [
    '{{--
Shared revision list component.

Can be reused from plugin/theme by passing the following properties:

- $revisions: LengthAwarePaginator<\\Illuminate\\Database\\Eloquent\\Model> — revisions with eager loaded creator relation
- $typeLabels: array<string, string> — translation labels for type (auto / manual / restore_backup)
- $retention: int — current retention count settings (summary badge hidden when 0)
- $protectedCount: int — number of protected revisions
- $backRoute: string — return URL to edit screen, etc.
- $backLabel: string — text for back link
- $showRouteName: string — route name for diff detail screen (e.g. admin.front.revisions.show)
- $restoreRouteName: string — route name for restore action
- $protectRouteName: string — route name for protect toggle
- $parentParams: array — parent parameters to prepend to each route (supports multiple levels)
- $translationPrefix: string — translation key prefix (e.g. admin/front/revisions)
--}}' => '{{--
共通リビジョン一覧コンポーネント。

プラグイン/テーマから以下のプロパティを渡して再利用できる:

- $revisions: LengthAwarePaginator<\\Illuminate\\Database\\Eloquent\\Model> — creator リレーションを eager load 済みのリビジョン
- $typeLabels: array<string, string> — type の翻訳ラベル（auto / manual / restore_backup）
- $retention: int — 現在の保持件数設定（0 のときサマリバッジ非表示）
- $protectedCount: int — 保護中リビジョン件数
- $backRoute: string — 編集画面などへの戻り先 URL
- $backLabel: string — 戻るリンクのテキスト
- $showRouteName: string — 差分詳細画面のルート名（例: admin.front.revisions.show）
- $restoreRouteName: string — 復元アクションのルート名
- $protectRouteName: string — 保護トグルのルート名
- $parentParams: array — 各ルートの先頭に付与する親パラメータ（複数階層対応）
- $translationPrefix: string — 翻訳キープレフィックス（例: admin/front/revisions）
--}}',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '{{--
Shared revision list component.

Can be reused from plugin/theme by passing the following properties:

- $revisions: LengthAwarePaginator<\\Illuminate\\Database\\Eloquent\\Model> — revisions with eager loaded creator relation
- $typeLabels: array<string, string> — translation labels for type (auto / manual / restore_backup)
- $retention: int — current retention count settings (summary badge hidden when 0)
- $protectedCount: int — number of protected revisions
- $backRoute: string — return URL to edit screen, etc.
- $backLabel: string — text for back link
- $showRouteName: string — route name for diff detail screen (e.g. admin.front.revisions.show)
- $restoreRouteName: string — route name for restore action
- $protectRouteName: string — route name for protect toggle
- $parentParams: array — parent parameters to prepend to each route (supports multiple levels)
- $translationPrefix: string — translation key prefix (e.g. admin/front/revisions)
--}}' => 'machine',
    ],
];
