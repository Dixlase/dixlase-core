<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

/*
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Countries
    |--------------------------------------------------------------------------
    |
    | List of countries (ISO 3166-1 alpha-2 codes)
    | Translation key: common.countries.{code}
    |
    */

    'countries' => [
        'JP' => 'Japan',
        'US' => 'United States',
        'GB' => 'United Kingdom',
        'CA' => 'Canada',
        'AU' => 'Australia',
        'NZ' => 'New Zealand',
        'DE' => 'Germany',
        'FR' => 'France',
        'IT' => 'Italy',
        'ES' => 'Spain',
        'NL' => 'Netherlands',
        'BE' => 'Belgium',
        'CH' => 'Switzerland',
        'AT' => 'Austria',
        'SE' => 'Sweden',
        'NO' => 'Norway',
        'DK' => 'Denmark',
        'FI' => 'Finland',
        'PL' => 'Poland',
        'CZ' => 'Czech Republic',
        'HU' => 'Hungary',
        'RO' => 'Romania',
        'BG' => 'Bulgaria',
        'GR' => 'Greece',
        'PT' => 'Portugal',
        'IE' => 'Ireland',
        'CN' => 'China',
        'KR' => 'South Korea',
        'TW' => 'Taiwan',
        'HK' => 'Hong Kong',
        'SG' => 'Singapore',
        'MY' => 'Malaysia',
        'TH' => 'Thailand',
        'VN' => 'Vietnam',
        'PH' => 'Philippines',
        'ID' => 'Indonesia',
        'IN' => 'India',
        'PK' => 'Pakistan',
        'BD' => 'Bangladesh',
        'LK' => 'Sri Lanka',
        'NP' => 'Nepal',
        'AE' => 'United Arab Emirates',
        'SA' => 'Saudi Arabia',
        'IL' => 'Israel',
        'TR' => 'Turkey',
        'EG' => 'Egypt',
        'ZA' => 'South Africa',
        'BR' => 'Brazil',
        'MX' => 'Mexico',
        'AR' => 'Argentina',
        'CL' => 'Chile',
        'CO' => 'Colombia',
        'PE' => 'Peru',
        'RU' => 'Russia',
        'UA' => 'Ukraine',
    ],

    /*
    |--------------------------------------------------------------------------
    | Country Calling Codes
    |--------------------------------------------------------------------------
    |
    | International dialing codes (country codes)
    | Translation key: common.country_codes.{code}
    |
    */

    'country_codes' => [
        'JP' => '+81',
        'US' => '+1',
        'GB' => '+44',
        'CA' => '+1',
        'AU' => '+61',
        'NZ' => '+64',
        'DE' => '+49',
        'FR' => '+33',
        'IT' => '+39',
        'ES' => '+34',
        'NL' => '+31',
        'BE' => '+32',
        'CH' => '+41',
        'AT' => '+43',
        'SE' => '+46',
        'NO' => '+47',
        'DK' => '+45',
        'FI' => '+358',
        'PL' => '+48',
        'CZ' => '+420',
        'HU' => '+36',
        'RO' => '+40',
        'BG' => '+359',
        'GR' => '+30',
        'PT' => '+351',
        'IE' => '+353',
        'CN' => '+86',
        'KR' => '+82',
        'TW' => '+886',
        'HK' => '+852',
        'SG' => '+65',
        'MY' => '+60',
        'TH' => '+66',
        'VN' => '+84',
        'PH' => '+63',
        'ID' => '+62',
        'IN' => '+91',
        'PK' => '+92',
        'BD' => '+880',
        'LK' => '+94',
        'NP' => '+977',
        'AE' => '+971',
        'SA' => '+966',
        'IL' => '+972',
        'TR' => '+90',
        'EG' => '+20',
        'ZA' => '+27',
        'BR' => '+55',
        'MX' => '+52',
        'AR' => '+54',
        'CL' => '+56',
        'CO' => '+57',
        'PE' => '+51',
        'RU' => '+7',
        'UA' => '+380',
    ],

    /*
    |--------------------------------------------------------------------------
    | Japanese Prefectures
    |--------------------------------------------------------------------------
    |
    | List of Japanese prefectures
    | Translation key: common.prefectures.{code}
    |
    */

    'prefectures' => [
        '01' => 'Hokkaido',
        '02' => 'Aomori',
        '03' => 'Iwate',
        '04' => 'Miyagi',
        '05' => 'Akita',
        '06' => 'Yamagata',
        '07' => 'Fukushima',
        '08' => 'Ibaraki',
        '09' => 'Tochigi',
        '10' => 'Gunma',
        '11' => 'Saitama',
        '12' => 'Chiba',
        '13' => 'Tokyo',
        '14' => 'Kanagawa',
        '15' => 'Niigata',
        '16' => 'Toyama',
        '17' => 'Ishikawa',
        '18' => 'Fukui',
        '19' => 'Yamanashi',
        '20' => 'Nagano',
        '21' => 'Gifu',
        '22' => 'Shizuoka',
        '23' => 'Aichi',
        '24' => 'Mie',
        '25' => 'Shiga',
        '26' => 'Kyoto',
        '27' => 'Osaka',
        '28' => 'Hyogo',
        '29' => 'Nara',
        '30' => 'Wakayama',
        '31' => 'Tottori',
        '32' => 'Shimane',
        '33' => 'Okayama',
        '34' => 'Hiroshima',
        '35' => 'Yamaguchi',
        '36' => 'Tokushima',
        '37' => 'Kagawa',
        '38' => 'Ehime',
        '39' => 'Kochi',
        '40' => 'Fukuoka',
        '41' => 'Saga',
        '42' => 'Nagasaki',
        '43' => 'Kumamoto',
        '44' => 'Oita',
        '45' => 'Miyazaki',
        '46' => 'Kagoshima',
        '47' => 'Okinawa',
    ],

];
