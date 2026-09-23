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

declare(strict_types=1);

namespace App\Rules;

use App\Support\ExtensionDirectories;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts only a value that can be the directory name of an installed plugin
 * or theme, for the requests that turn a directory name into an install.
 *
 * The install actions interpolate the submitted value straight into
 * `base_path("plugins/{$value}")` and hand it to `dls:plugin:install`, which
 * runs that directory's migrations. The rule was `required|string`, so it
 * carried neither of the two constraints that matter:
 *
 *   - **It is a bare directory name.** `..`, `a/b` and an absolute path all
 *     passed, and the only thing standing between them and the install command
 *     was whether the resulting path happened to exist.
 *   - **It is not a leftover copy.** `plugins/` and `themes/` accumulate
 *     move-aside copies (`Foo.stale.<timestamp>`, `Foo.bak`) that are complete
 *     down to the manifest and `database/migrations/`, and the admin panel
 *     listed them as installable. Installing one wrote a `plugins` row naming
 *     the copy, ran its migrations for real, and registered its routes, while
 *     its ServiceProvider could never resolve because a directory name with a
 *     dot is not a valid namespace segment.
 *
 * The copy judgement is delegated to {@see ExtensionDirectories} so the rule
 * lives in one place; only the structural part is decided here.
 */
class InstalledExtensionDirectory implements ValidationRule
{
    /**
     * Execute validation
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail(__('validation.custom.extension_directory.invalid'));

            return;
        }

        // A bare name only: reject separators, traversal and null bytes before
        // the value can reach a path or a shell argument.
        if ($value !== basename($value) || preg_match('/[\/\\\\\x00]/', $value) === 1) {
            $fail(__('validation.custom.extension_directory.invalid'));

            return;
        }

        // Checked before the identifier test below, which would otherwise
        // reject a copy as merely malformed: `DixlaseOnePage.stale.20260817`
        // deserves "this is a leftover copy", not "invalid name", because that
        // is the case an operator actually meets and can act on.
        if (! ExtensionDirectories::isInstalledName($value)) {
            $fail(__('validation.custom.extension_directory.not_installed_name'));

            return;
        }

        // The name doubles as a PHP namespace segment (`Plugins\{Name}\`), so
        // it is an identifier: ASCII letters and digits, starting with a
        // letter. This catches what the name rule does not -- a space, a
        // hyphen, a leading digit.
        if (preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $value) !== 1) {
            $fail(__('validation.custom.extension_directory.invalid'));
        }
    }
}
