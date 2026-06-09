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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms above.
 */

return [
    '{{--
Radio button card group component with labels and descriptions

Usage example:
<x-form-radio-card-group
    name="preset"
    :options="[
        [\'value\' => \'strict\', \'label\' => \'厳格\', \'description\' => \'最も安全な設定\', \'icon\' => \'fas fa-shield-alt\', \'color\' => \'green\'],
        [\'value\' => \'balanced\', \'label\' => \'バランス\', \'description\' => \'推奨設定\', \'icon\' => \'fas fa-balance-scale\', \'color\' => \'blue\'],
        [\'value\' => \'development\', \'label\' => \'開発\', \'description\' => \'開発用\', \'icon\' => \'fas fa-code\', \'color\' => \'yellow\', \'badge\' => \'開発専用\'],
    ]"
    :value="$currentValue"
    xModel="preset"
    :columns="4"
    color="primary"
    variant="filled"
    :showCheck="true"
/>

Each element in options array:
- value: (required) Radio button value
- label: (required) Display label
- description: (optional) Description text
- icon: (optional) FontAwesome icon class
- color: (optional) Individual option color (overrides global settings)
- badge: (optional) Badge text (e.g., development only)
- badgeColor: (optional) Badge color (yellow, red, green, blue, gray) Default: yellow
- disabled: (optional) Disabled flag
- features: (optional) Feature list (array)

Global properties:
- color: Color when selected (primary, secondary, success, warning, danger, blue, green, yellow, orange, red, purple, gray)
- variant: Style (filled=with background color, outlined=border only)
- showCheck: Whether to display check icon
--}}' => '{{--
ラベルと説明付きのラジオボタンカードグループコンポーネント

使用例:
<x-form-radio-card-group
    name="preset"
    :options="[
        [\'value\' => \'strict\', \'label\' => \'厳格\', \'description\' => \'最も安全な設定\', \'icon\' => \'fas fa-shield-alt\', \'color\' => \'green\'],
        [\'value\' => \'balanced\', \'label\' => \'バランス\', \'description\' => \'推奨設定\', \'icon\' => \'fas fa-balance-scale\', \'color\' => \'blue\'],
        [\'value\' => \'development\', \'label\' => \'開発\', \'description\' => \'開発用\', \'icon\' => \'fas fa-code\', \'color\' => \'yellow\', \'badge\' => \'開発専用\'],
    ]"
    :value="$currentValue"
    xModel="preset"
    :columns="4"
    color="primary"
    variant="filled"
    :showCheck="true"
/>

オプション配列の各要素:
- value: (必須) ラジオボタンの値
- label: (必須) 表示ラベル
- description: (任意) 説明文
- icon: (任意) FontAwesomeアイコンクラス
- color: (任意) 個別オプションの色（グローバル設定を上書き）
- badge: (任意) バッジテキスト（開発専用など）
- badgeColor: (任意) バッジの色 (yellow, red, green, blue, gray) デフォルト: yellow
- disabled: (任意) 無効化フラグ
- features: (任意) 機能リスト（配列）

グローバルプロパティ:
- color: 選択時の色 (primary, secondary, success, warning, danger, blue, green, yellow, orange, red, purple, gray)
- variant: スタイル (filled=背景色あり, outlined=ボーダーのみ)
- showCheck: チェックアイコンを表示するか
--}}',
    '{{-- Border overlay --}}' => '{{-- ボーダーオーバーレイ --}}',
    '{{-- Check icon --}}' => '{{-- チェックアイコン --}}',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '{{--
Radio button card group component with labels and descriptions

Usage example:
<x-form-radio-card-group
    name="preset"
    :options="[
        [\'value\' => \'strict\', \'label\' => \'厳格\', \'description\' => \'最も安全な設定\', \'icon\' => \'fas fa-shield-alt\', \'color\' => \'green\'],
        [\'value\' => \'balanced\', \'label\' => \'バランス\', \'description\' => \'推奨設定\', \'icon\' => \'fas fa-balance-scale\', \'color\' => \'blue\'],
        [\'value\' => \'development\', \'label\' => \'開発\', \'description\' => \'開発用\', \'icon\' => \'fas fa-code\', \'color\' => \'yellow\', \'badge\' => \'開発専用\'],
    ]"
    :value="$currentValue"
    xModel="preset"
    :columns="4"
    color="primary"
    variant="filled"
    :showCheck="true"
/>

Each element in options array:
- value: (required) Radio button value
- label: (required) Display label
- description: (optional) Description text
- icon: (optional) FontAwesome icon class
- color: (optional) Individual option color (overrides global settings)
- badge: (optional) Badge text (e.g., development only)
- badgeColor: (optional) Badge color (yellow, red, green, blue, gray) Default: yellow
- disabled: (optional) Disabled flag
- features: (optional) Feature list (array)

Global properties:
- color: Color when selected (primary, secondary, success, warning, danger, blue, green, yellow, orange, red, purple, gray)
- variant: Style (filled=with background color, outlined=border only)
- showCheck: Whether to display check icon
--}}' => 'machine',
        '{{-- Border overlay --}}' => 'machine',
        '{{-- Check icon --}}' => 'machine',
    ],
];
