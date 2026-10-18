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
    | Extension factory search order
    |--------------------------------------------------------------------------
    |
    | Directory names of extensions whose `Plugins\<Directory>\Database\
    | Factories` namespace is searched for a model factory before core's own
    | `Database\Factories`. An extension may ship a factory for a core model
    | — a member, a front page — and a test on a site that has the extension
    | installed has to pick that one up.
    |
    | Empty is the default and means: every extension under plugins/ that
    | ships a factory directory, sorted by directory name so the order does
    | not depend on the filesystem. Core therefore names no extension, which
    | matters because this repository is public and some extensions are not
    | distributed (dixlase-core#619).
    |
    | Set it when the order decides something: two extensions defining the
    | same factory, or a factory that must not win. The value is a
    | comma-separated list of directory names, searched left to right, and
    | core's own factory is the fallback when none of them has one.
    |
    | Tests\TestCase pins the current resolution, so a newly installed
    | extension that shadows a core factory shows up as a failing test
    | rather than as a surprise in an unrelated suite.
    |
    */

    'factory_extensions' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('DLS_TEST_FACTORY_EXTENSIONS', ''))
    ))),

];
