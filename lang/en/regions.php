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
    | Countries
    |--------------------------------------------------------------------------
    |
    | Country names based on ISO 3166-1 alpha-2 codes
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
    | International dialing codes with country names
    |
    */

    'country_codes' => [
        'JP' => '+81 (Japan)',
        'US' => '+1 (United States)',
        'GB' => '+44 (United Kingdom)',
        'CA' => '+1 (Canada)',
        'AU' => '+61 (Australia)',
        'NZ' => '+64 (New Zealand)',
        'DE' => '+49 (Germany)',
        'FR' => '+33 (France)',
        'IT' => '+39 (Italy)',
        'ES' => '+34 (Spain)',
        'NL' => '+31 (Netherlands)',
        'BE' => '+32 (Belgium)',
        'CH' => '+41 (Switzerland)',
        'AT' => '+43 (Austria)',
        'SE' => '+46 (Sweden)',
        'NO' => '+47 (Norway)',
        'DK' => '+45 (Denmark)',
        'FI' => '+358 (Finland)',
        'PL' => '+48 (Poland)',
        'CZ' => '+420 (Czech Republic)',
        'HU' => '+36 (Hungary)',
        'RO' => '+40 (Romania)',
        'BG' => '+359 (Bulgaria)',
        'GR' => '+30 (Greece)',
        'PT' => '+351 (Portugal)',
        'IE' => '+353 (Ireland)',
        'CN' => '+86 (China)',
        'KR' => '+82 (South Korea)',
        'TW' => '+886 (Taiwan)',
        'HK' => '+852 (Hong Kong)',
        'SG' => '+65 (Singapore)',
        'MY' => '+60 (Malaysia)',
        'TH' => '+66 (Thailand)',
        'VN' => '+84 (Vietnam)',
        'PH' => '+63 (Philippines)',
        'ID' => '+62 (Indonesia)',
        'IN' => '+91 (India)',
        'PK' => '+92 (Pakistan)',
        'BD' => '+880 (Bangladesh)',
        'LK' => '+94 (Sri Lanka)',
        'NP' => '+977 (Nepal)',
        'AE' => '+971 (United Arab Emirates)',
        'SA' => '+966 (Saudi Arabia)',
        'IL' => '+972 (Israel)',
        'TR' => '+90 (Turkey)',
        'EG' => '+20 (Egypt)',
        'ZA' => '+27 (South Africa)',
        'BR' => '+55 (Brazil)',
        'MX' => '+52 (Mexico)',
        'AR' => '+54 (Argentina)',
        'CL' => '+56 (Chile)',
        'CO' => '+57 (Colombia)',
        'PE' => '+51 (Peru)',
        'RU' => '+7 (Russia)',
        'UA' => '+380 (Ukraine)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Japanese Prefectures
    |--------------------------------------------------------------------------
    |
    | 47 prefectures of Japan
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
