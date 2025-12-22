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
    | バリデーション言語行
    |--------------------------------------------------------------------------
    |
    | 以下の言語行は、バリデータークラスで使用されるデフォルトのエラーメッセージを含んでいます。
    | これらのルールのいくつかには、サイズルールなど、複数のバージョンがあります。
    | ここで各メッセージを調整することができます。
    |
    */

    'accepted' => ':attribute フィールドは受け入れられなければなりません。',
    'accepted_if' => ':other が :value のとき、:attribute フィールドは受け入れられなければなりません。',
    'active_url' => ':attribute フィールドは有効なURLでなければなりません。',
    'after' => ':attribute フィールドは :date の後の日付でなければなりません。',
    'after_or_equal' => ':attribute フィールドは :date 以降の日付でなければなりません。',
    'alpha' => ':attribute フィールドは文字のみを含む必要があります。',
    'alpha_dash' => ':attribute フィールドは文字、数字、ダッシュ、およびアンダースコアのみを含む必要があります。',
    'alpha_num' => ':attribute フィールドは文字と数字のみを含む必要があります。',
    'array' => ':attribute フィールドは配列でなければなりません。',
    'ascii' => ':attribute フィールドは単一バイトの英数字と記号のみを含む必要があります。',
    'before' => ':attribute フィールドは :date の前の日付でなければなりません。',
    'before_or_equal' => ':attribute フィールドは :date の前または同じ日付でなければなりません。',
    'between' => [
        'array' => ':attribute フィールドは :min から :max のアイテムを持たなければなりません。',
        'file' => ':attribute フィールドは :min から :max キロバイトでなければなりません。',
        'numeric' => ':attribute フィールドは :min から :max の間でなければなりません。',
        'string' => ':attribute フィールドは :min から :max 文字でなければなりません。',
    ],
    'boolean' => ':attribute フィールドは真または偽でなければなりません。',
    'can' => ':attribute フィールドには不正な値が含まれています。',
    'confirmed' => ':attribute フィールドの確認が一致しません。',
    'contains' => ':attribute フィールドには必須の値が欠けています。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attribute フィールドは有効な日付でなければなりません。',
    'date_equals' => ':attribute フィールドは :date と等しい日付でなければなりません。',
    'date_format' => ':attribute フィールドはフォーマット :format に一致しなければなりません。',
    'decimal' => ':attribute フィールドは :decimal 桁の小数を持たなければなりません。',
    'declined' => ':attribute フィールドは拒否されなければなりません。',
    'declined_if' => ':other が :value のとき、:attribute フィールドは拒否されなければなりません。',
    'different' => ':attribute フィールドと :other は異ならなければなりません。',
    'digits' => ':attribute フィールドは :digits 桁でなければなりません。',
    'digits_between' => ':attribute フィールドは :min から :max 桁でなければなりません。',
    'dimensions' => ':attribute フィールドの画像の寸法が無効です。',
    'distinct' => ':attribute フィールドには重複した値があります。',
    'doesnt_end_with' => ':attribute フィールドは次のいずれかで終わってはいけません: :values。',
    'doesnt_start_with' => ':attribute フィールドは次のいずれかで始まってはいけません: :values。',
    'email' => ':attribute フィールドは有効なメールアドレスでなければなりません。',
    'ends_with' => ':attribute フィールドは次のいずれかで終わらなければなりません: :values。',
    'enum' => '選択された :attribute は無効です。',
    'exists' => '選択された :attribute は無効です。',
    'extensions' => ':attribute フィールドは次のいずれかの拡張子を持たなければなりません: :values。',
    'file' => ':attribute フィールドはファイルでなければなりません。',
    'filled' => ':attribute フィールドには値が必要です。',
    'gt' => [
        'array' => ':attribute フィールドは :value より多くのアイテムを持たなければなりません。',
        'file' => ':attribute フィールドは :value キロバイトより大きくなければなりません。',
        'numeric' => ':attribute フィールドは :value より大きくなければなりません。',
        'string' => ':attribute フィールドは :value より多くの文字を持たなければなりません。',
    ],
    'gte' => [
        'array' => ':attribute フィールドは :value アイテム以上を持たなければなりません。',
        'file' => ':attribute フィールドは :value キロバイト以上でなければなりません。',
        'numeric' => ':attribute フィールドは :value 以上でなければなりません。',
        'string' => ':attribute フィールドは :value 以上の文字を持たなければなりません。',
    ],
    'hex_color' => ':attribute フィールドは有効な16進数の色でなければなりません。',
    'image' => ':attribute フィールドは画像でなければなりません。',
    'in' => '選択された :attribute は無効です。',
    'in_array' => ':attribute フィールドは :other に存在しなければなりません。',
    'integer' => ':attribute フィールドは整数でなければなりません。',
    'ip' => ':attribute フィールドは有効なIPアドレスでなければなりません。',
    'ipv4' => ':attribute フィールドは有効なIPv4アドレスでなければなりません。',
    'ipv6' => ':attribute フィールドは有効なIPv6アドレスでなければなりません。',
    'json' => ':attribute フィールドは有効なJSON文字列でなければなりません。',
    'list' => ':attribute フィールドはリストでなければなりません。',
    'lowercase' => ':attribute フィールドは小文字でなければなりません。',
    'lt' => [
        'array' => ':attribute フィールドは :value より少ないアイテムを持たなければなりません。',
        'file' => ':attribute フィールドは :value キロバイトより小さくなければなりません。',
        'numeric' => ':attribute フィールドは :value より小さくなければなりません。',
        'string' => ':attribute フィールドは :value より少ない文字を持たなければなりません。',
    ],
    'lte' => [
        'array' => ':attribute フィールドは :value アイテムより多く持ってはいけません。',
        'file' => ':attribute フィールドは :value キロバイト以下でなければなりません。',
        'numeric' => ':attribute フィールドは :value 以下でなければなりません。',
        'string' => ':attribute フィールドは :value 以下の文字を持たなければなりません。',
    ],
    'mac_address' => ':attribute フィールドは有効なMACアドレスでなければなりません。',
    'max' => [
        'array' => ':attribute フィールドは :max アイテムより多く持ってはいけません。',
        'file' => ':attribute フィールドは :max キロバイト以下でなければなりません。',
        'numeric' => ':attribute フィールドは :max より大きくてはいけません。',
        'string' => ':attribute フィールドは :max 文字以下でなければなりません。',
    ],
    'max_digits' => ':attribute フィールドは :max 桁より多く持ってはいけません。',
    'mimes' => ':attribute フィールドは次のいずれかのタイプのファイルでなければなりません: :values。',
    'mimetypes' => ':attribute フィールドは次のいずれかのタイプのファイルでなければなりません: :values。',
    'min' => [
        'array' => ':attribute フィールドは少なくとも :min アイテムを持たなければなりません。',
        'file' => ':attribute フィールドは少なくとも :min キロバイトでなければなりません。',
        'numeric' => ':attribute フィールドは少なくとも :min でなければなりません。',
        'string' => ':attribute フィールドは少なくとも :min 文字でなければなりません。',
    ],
    'min_digits' => ':attribute フィールドは少なくとも :min 桁でなければなりません。',
    'multiple_of' => ':attribute フィールドは :value の倍数でなければなりません。',
    'not_accepted' => ':attribute フィールドは拒否されるべきです。',
    'not_in' => '選択された :attribute は無効です。',
    'not_regex' => ':attribute フィールドの形式が無効です。',
    'numeric' => ':attribute フィールドは数字でなければなりません。',
    'password' => [
        'letters' => '少なくとも1つの文字を含める必要があります。',
        'mixed' => '少なくとも1つの大文字と小文字を含める必要があります。',
        'numbers' => '少なくとも1つの数字を含める必要があります。',
        'symbols' => '少なくとも1つの記号を含める必要があります。',
        'uncompromised' => 'このパスワードはデータ漏洩の中で見つかりました。別のものを選んでください。',
    ],
    'present' => ':attribute フィールドは存在していなければなりません。',
    'prohibited' => ':attribute フィールドは禁止されています。',
    'prohibited_if' => ':other が :value のとき、:attribute フィールドは禁止されています。',
    'prohibited_unless' => ':other が :values にない限り、:attribute フィールドは禁止されています。',
    'prohibits' => ':other フィールドの存在を禁止します。',
    'regex' => ':attribute フィールドの形式が無効です。',
    'required' => ':attribute フィールドは必須です。',
    'required_array_keys' => ':attribute フィールドには次のキーが含まれていなければなりません: :values。',
    'required_if' => ':other が :value のとき、:attribute フィールドは必須です。',
    'required_if_accepted' => ':other フィールドが受け入れられている場合、:attribute フィールドは必須です。',
    'required_unless' => ':other が :values にない限り、:attribute フィールドは必須です。',
    'required_with' => ':values が存在する場合、:attribute フィールドは必須です。',
    'required_with_all' => ':values が存在する場合、:attribute フィールドは必須です。',
    'required_without' => ':values が存在しない場合、:attribute フィールドは必須です。',
    'required_without_all' => ':values がすべて存在しない場合、:attribute フィールドは必須です。',
    'same' => ':attribute フィールドは :other と一致しなければなりません。',
    'size' => [
        'array' => ':attribute フィールドは :size アイテムでなければなりません。',
        'file' => ':attribute フィールドは :size キロバイトでなければなりません。',
        'numeric' => ':attribute フィールドは :size でなければなりません。',
        'string' => ':attribute フィールドは :size 文字でなければなりません。',
    ],
    'starts_with' => ':attribute フィールドは次のいずれかで始まらなければなりません: :values。',
    'string' => ':attribute フィールドは文字列でなければなりません。',
    'timezone' => ':attribute フィールドは有効なゾーンでなければなりません。',
    'unique' => ':attribute フィールドはすでに使用されています。',
    'uploaded' => ':attribute フィールドのアップロードに失敗しました。',
    'url' => ':attribute フィールドは有効なURLでなければなりません。',
    'uuid' => ':attribute フィールドは有効なUUIDでなければなりません。',

    /*
    |--------------------------------------------------------------------------
    | カスタムバリデーション言語行
    |--------------------------------------------------------------------------
    |
    | ここでは、属性に対するカスタムバリデーションメッセージを指定できます。
    | 名前付け規則 "attribute.rule" を使用して行を指定します。これにより、
    | 特定の属性ルールに対するカスタムメッセージを迅速に指定できます。
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
        'default_two_factor_method' => [
            'in_enabled_methods' => 'デフォルトの二段階認証方法は有効な方法の中から選択してください。',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | カスタムバリデーション属性
    |--------------------------------------------------------------------------
    |
    | 次の言語行は、属性のプレースホルダーを「Eメールアドレス」のように、
    | より読者に優しい表現に置き換えるために使用されます。
    | これはメッセージをより表現力豊かにするのに役立ちます。
    |
    */

    'attributes' => [
        // 基本フィールド
        'login' => 'メールアドレスまたはアカウント名',
        'email' => 'メールアドレス',
        'email_confirmation' => 'メールアドレス（確認用）',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード確認',
        'current_password' => '現在のパスワード',
        'new_password' => '新しいパスワード',
        'new_password_confirmation' => '新しいパスワード確認',
        'name' => '名前',
        'title' => 'タイトル',
        'content' => '内容',
        'description' => '説明',
        'token' => 'トークン',
        
        // プロフィール・セキュリティ関連
        'appearance' => '外観設定',
        'login_notification_mode' => 'ログイン通知設定',
        'two_factor_mode' => '二段階認証設定',
        'two_factor_method' => '二段階認証方法',
        
        // インストール関連
        'site_name' => 'サイト名',
        'admin_email' => '管理者メールアドレス',
        'admin_password' => '管理者パスワード',
        'admin_password_confirmation' => '管理者パスワード確認',
        'admin_url' => '管理画面URL',
        'force_ssl' => 'SSLの強制',
        'allowed_admin_ips' => '管理画面で許可するIPアドレス',
        'blocked_admin_ips' => '管理画面でブロックするIPアドレス',
        'allowed_front_ips' => 'フロントページで許可するIPアドレス',
        'blocked_front_ips' => 'フロントページでブロックするIPアドレス',
        'db_connection' => 'データベース接続',
        'db_host' => 'データベースホスト',
        'db_port' => 'データベースポート',
        'db_database' => 'データベース名',
        'db_username' => 'データベースユーザー名',
        'db_password' => 'データベースパスワード',
    ],

    /*
    |--------------------------------------------------------------------------
    | パスワード辞書攻撃対策メッセージ
    |--------------------------------------------------------------------------
    */

    'pwned_password_found' => 'このパスワードは過去に :count 回データ漏洩で発見されており、安全ではありません。別のパスワードを選択してください。',
    'pwned_password_api_error' => 'パスワード安全性チェック中にエラーが発生しましたが、パスワードは受け入れられました。',
    'password_uppercase_required' => 'パスワードには大文字を含める必要があります。',
    'password_symbol_required' => 'パスワードには記号を含める必要があります。',

];
