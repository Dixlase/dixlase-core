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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
    | Source Code URL (AGPL §13 Compliance)
    |--------------------------------------------------------------------------
    |
    | The URL where users can obtain the source code of the running
    | Dixlase CMS instance, including any modifications. This is
    | required by AGPL v3 Section 13 when Dixlase CMS is made available
    | over a network.
    |
    | Default: The official Dixlase CMS repository.
    | Operators running a modified version MUST override this to point
    | to the repository (or equivalent distribution) of their running code.
    |
    */

    'source_url' => env('DIXLASE_SOURCE_URL', 'https://github.com/Dixlase/dixlase-core'),

    /*
    |--------------------------------------------------------------------------
    | License Label
    |--------------------------------------------------------------------------
    |
    | The short license identifier shown in the admin and public footers.
    | This should reflect the license under which you distribute the
    | running version. It does NOT change the actual license terms;
    | modify your LICENSE file if you need a different license.
    |
    */

    'license_label' => env('DIXLASE_LICENSE_LABEL', 'AGPLv3'),

    'license_url' => env('DIXLASE_LICENSE_URL', 'https://www.gnu.org/licenses/agpl-3.0.html'),

];
