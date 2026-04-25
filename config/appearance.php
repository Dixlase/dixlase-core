<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
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

    /*
    |--------------------------------------------------------------------------
    | 外観モード設定
    |--------------------------------------------------------------------------
    |
    | 管理画面の外観モード（ライト/ダーク）に関する設定です。
    | トランジション効果やテーマ別のCSSクラスを定義します。
    |
    */

    // トランジション効果のクラス
    'transition_class' => 'transition-colors duration-1000',

    // 外観モード別のCSSクラス定義
    'appearance_class' => [
        'layout' => [
            'body' => 'bg-white text-gray-900 dark:bg-gray-950 dark:text-white transition-colors duration-300',
            'header' => 'bg-gray-200 dark:bg-gray-800 border-gray-300 dark:border-gray-700',
            'logo' => 'text-gray-900 dark:text-white',
            'aside' => 'bg-gray-100 dark:bg-gray-800 text-gray-900 border-r border-gray-300  dark:text-white dark:border-r dark:border-gray-700 '.config('appearance.transition_class'),
            'main' => 'bg-white text-gray-900 dark:bg-black dark:text-white',
            'title' => 'bg-gray-100 text-gray-800 border-b border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white',
            'heading' => 'text-gray-800 dark:text-white',
            'nav_link' => 'text-gray-700 hover:text-black dark:text-gray-300 dark:hover:text-white',
            'button_admin_user' => 'text-gray-500 bg-white hover:text-gray-700 dark:text-gray-300 dark:bg-gray-800 dark:hover:text-white',
            'button_hamburger' => 'text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700',
            'responsive_navigation_menu' => 'text-gray-700 hover:text-black dark:text-gray-300 dark:hover:text-white',
            'option_1' => 'border-gray-200 dark:border-gray-700',
            'option_2' => 'text-gray-800 dark:text-white',
            'option_3' => 'text-gray-500 dark:text-gray-400',
            'save_button' => 'bg-gray-200 dark:bg-gray-800 border-gray-300 dark:border-gray-700 ',
        ],
        'sidebar' => [
            'normal' => 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 hover:text-black dark:hover:bg-gray-700 dark:hover:text-white',
            'active' => 'bg-gray-200 text-gray-900 font-bold border-blue-500 pl-3 rounded-md hover:bg-gray-300 hover:text-black dark:bg-gray-100 dark:text-black dark:hover:bg-gray-600',
        ],
        'table' => [
            'table' => 'w-full text-sm text-left rtl:text-right mb-4',
            'thead' => 'text-xs uppercase bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-white',
            'tr' => 'odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700',
            'td' => 'px-4 py-4 border-b dark:border-gray-700',
            'row_hover' => 'hover:bg-gray-100 dark:hover:bg-gray-600',
            'row_selected' => 'bg-gray-200 dark:bg-gray-600',
            'row_selected_hover' => 'hover:bg-gray-200 dark:hover:bg-gray-600',
            'cell' => 'border-b border-gray-200 dark:border-gray-700',
            'cell_selected' => 'border-b border-gray-200 dark:border-gray-700',
            'cell_selected_hover' => 'border-b border-gray-200 dark:border-gray-700',
        ],
        'link' => 'text-indigo-600 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-500',
    ],

];
