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

    /*
    |--------------------------------------------------------------------------
    | Countries (国名)
    |--------------------------------------------------------------------------
    |
    | ISO 3166-1 alpha-2 コードに基づく国名の翻訳
    |
    */

    'countries' => [
        'JP' => '日本',
        'US' => 'アメリカ合衆国',
        'GB' => 'イギリス',
        'CA' => 'カナダ',
        'AU' => 'オーストラリア',
        'NZ' => 'ニュージーランド',
        'DE' => 'ドイツ',
        'FR' => 'フランス',
        'IT' => 'イタリア',
        'ES' => 'スペイン',
        'NL' => 'オランダ',
        'BE' => 'ベルギー',
        'CH' => 'スイス',
        'AT' => 'オーストリア',
        'SE' => 'スウェーデン',
        'NO' => 'ノルウェー',
        'DK' => 'デンマーク',
        'FI' => 'フィンランド',
        'PL' => 'ポーランド',
        'CZ' => 'チェコ',
        'HU' => 'ハンガリー',
        'RO' => 'ルーマニア',
        'BG' => 'ブルガリア',
        'GR' => 'ギリシャ',
        'PT' => 'ポルトガル',
        'IE' => 'アイルランド',
        'CN' => '中国',
        'KR' => '韓国',
        'TW' => '台湾',
        'HK' => '香港',
        'SG' => 'シンガポール',
        'MY' => 'マレーシア',
        'TH' => 'タイ',
        'VN' => 'ベトナム',
        'PH' => 'フィリピン',
        'ID' => 'インドネシア',
        'IN' => 'インド',
        'PK' => 'パキスタン',
        'BD' => 'バングラデシュ',
        'LK' => 'スリランカ',
        'NP' => 'ネパール',
        'AE' => 'アラブ首長国連邦',
        'SA' => 'サウジアラビア',
        'IL' => 'イスラエル',
        'TR' => 'トルコ',
        'EG' => 'エジプト',
        'ZA' => '南アフリカ',
        'BR' => 'ブラジル',
        'MX' => 'メキシコ',
        'AR' => 'アルゼンチン',
        'CL' => 'チリ',
        'CO' => 'コロンビア',
        'PE' => 'ペルー',
        'RU' => 'ロシア',
        'UA' => 'ウクライナ',
    ],

    /*
    |--------------------------------------------------------------------------
    | Country Calling Codes (国番号)
    |--------------------------------------------------------------------------
    |
    | 国際電話番号の表示用（翻訳不要だが、表示形式を統一）
    |
    */

    'country_codes' => [
        'JP' => '+81 (日本)',
        'US' => '+1 (アメリカ合衆国)',
        'GB' => '+44 (イギリス)',
        'CA' => '+1 (カナダ)',
        'AU' => '+61 (オーストラリア)',
        'NZ' => '+64 (ニュージーランド)',
        'DE' => '+49 (ドイツ)',
        'FR' => '+33 (フランス)',
        'IT' => '+39 (イタリア)',
        'ES' => '+34 (スペイン)',
        'NL' => '+31 (オランダ)',
        'BE' => '+32 (ベルギー)',
        'CH' => '+41 (スイス)',
        'AT' => '+43 (オーストリア)',
        'SE' => '+46 (スウェーデン)',
        'NO' => '+47 (ノルウェー)',
        'DK' => '+45 (デンマーク)',
        'FI' => '+358 (フィンランド)',
        'PL' => '+48 (ポーランド)',
        'CZ' => '+420 (チェコ)',
        'HU' => '+36 (ハンガリー)',
        'RO' => '+40 (ルーマニア)',
        'BG' => '+359 (ブルガリア)',
        'GR' => '+30 (ギリシャ)',
        'PT' => '+351 (ポルトガル)',
        'IE' => '+353 (アイルランド)',
        'CN' => '+86 (中国)',
        'KR' => '+82 (韓国)',
        'TW' => '+886 (台湾)',
        'HK' => '+852 (香港)',
        'SG' => '+65 (シンガポール)',
        'MY' => '+60 (マレーシア)',
        'TH' => '+66 (タイ)',
        'VN' => '+84 (ベトナム)',
        'PH' => '+63 (フィリピン)',
        'ID' => '+62 (インドネシア)',
        'IN' => '+91 (インド)',
        'PK' => '+92 (パキスタン)',
        'BD' => '+880 (バングラデシュ)',
        'LK' => '+94 (スリランカ)',
        'NP' => '+977 (ネパール)',
        'AE' => '+971 (アラブ首長国連邦)',
        'SA' => '+966 (サウジアラビア)',
        'IL' => '+972 (イスラエル)',
        'TR' => '+90 (トルコ)',
        'EG' => '+20 (エジプト)',
        'ZA' => '+27 (南アフリカ)',
        'BR' => '+55 (ブラジル)',
        'MX' => '+52 (メキシコ)',
        'AR' => '+54 (アルゼンチン)',
        'CL' => '+56 (チリ)',
        'CO' => '+57 (コロンビア)',
        'PE' => '+51 (ペルー)',
        'RU' => '+7 (ロシア)',
        'UA' => '+380 (ウクライナ)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Japanese Prefectures (都道府県)
    |--------------------------------------------------------------------------
    |
    | 日本の47都道府県
    |
    */

    'prefectures' => [
        '01' => '北海道',
        '02' => '青森県',
        '03' => '岩手県',
        '04' => '宮城県',
        '05' => '秋田県',
        '06' => '山形県',
        '07' => '福島県',
        '08' => '茨城県',
        '09' => '栃木県',
        '10' => '群馬県',
        '11' => '埼玉県',
        '12' => '千葉県',
        '13' => '東京都',
        '14' => '神奈川県',
        '15' => '新潟県',
        '16' => '富山県',
        '17' => '石川県',
        '18' => '福井県',
        '19' => '山梨県',
        '20' => '長野県',
        '21' => '岐阜県',
        '22' => '静岡県',
        '23' => '愛知県',
        '24' => '三重県',
        '25' => '滋賀県',
        '26' => '京都府',
        '27' => '大阪府',
        '28' => '兵庫県',
        '29' => '奈良県',
        '30' => '和歌山県',
        '31' => '鳥取県',
        '32' => '島根県',
        '33' => '岡山県',
        '34' => '広島県',
        '35' => '山口県',
        '36' => '徳島県',
        '37' => '香川県',
        '38' => '愛媛県',
        '39' => '高知県',
        '40' => '福岡県',
        '41' => '佐賀県',
        '42' => '長崎県',
        '43' => '熊本県',
        '44' => '大分県',
        '45' => '宮崎県',
        '46' => '鹿児島県',
        '47' => '沖縄県',
    ],

];
