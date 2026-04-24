<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
    | Relying Party
    |--------------------------------------------------------------------------
    |
    | We will use your application information to inform the device who is the
    | relying party. While only the name is enough, you can further set the
    | a custom domain as ID and even an icon image data encoded as BASE64.
    |
    */

    'relying_party' => [
        'name' => env('WEBAUTHN_NAME', config('app.name')),
        'id' => env('WEBAUTHN_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Origins
    |--------------------------------------------------------------------------
    |
    | By default, only your application domain is used as a valid origin for
    | all ceremonies. If you are using your app as a backend for an app or
    | UI you may set additional origins to check against the ceremonies.
    |
    | For multiple origins, separate them using comma, like `foo,bar`.
    */

    'origins' => env('WEBAUTHN_ORIGINS'),

    /*
    |--------------------------------------------------------------------------
    | Challenge configuration
    |--------------------------------------------------------------------------
    |
    | When making challenges your application needs to push at least 16 bytes
    | of randomness. Since we need to later check them, we'll also store the
    | bytes for a small amount of time inside this current request session.
    |
    | @see https://www.w3.org/TR/webauthn-2/#sctn-cryptographic-challenges
    |
    */

    'challenge' => [
        'bytes' => 16,
        'timeout' => 60,
        'key' => '_webauthn',
    ],

    /*
    |--------------------------------------------------------------------------
    | Credential Model
    |--------------------------------------------------------------------------
    |
    | This is the model that will be used to store WebAuthn credentials.
    | You can use your own model by extending the base WebAuthnCredential.
    |
    */

    'model' => \App\Models\WebAuthnCredential::class,
];
