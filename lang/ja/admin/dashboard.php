<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

return [
    'heading' => 'ダッシュボード',
    'description' => 'サイトの概要を確認できます。',
    'method_change_modal' => [
        'title' => '認証方法の変更',
        'message' => '今回は「:used_method」で認証しましたが、現在のデフォルト認証方法は「:current_method」です。',
        'question' => '今回使用した「:used_method」をデフォルトの認証方法に変更しますか？',
        'switch_button' => 'はい、変更する',
        'keep_button' => 'いいえ、現在のままにする',
    ],
    'method_switched_success' => 'デフォルトの認証方法を変更しました。',
    'method_switch_failed' => '認証方法の変更に失敗しました。',
];
