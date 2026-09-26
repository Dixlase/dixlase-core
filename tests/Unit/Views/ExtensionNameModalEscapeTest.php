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

namespace Tests\Unit\Views;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * `<x-ui-modal>` prints its `message` prop raw (`{!! $message !!}`) because some
 * callers pass deliberate markup. Extension names come from the uploaded
 * plugin.json / theme.json or from the online catalogue, so a name spliced into
 * a message must be escaped first — otherwise a crafted manifest name becomes
 * script in the admin panel of whoever opens the extensions screen.
 */
class ExtensionNameModalEscapeTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function views(): array
    {
        return [
            'plugin installed actions' => ['resources/views/admin/settings/plugins/partials/installed-actions.blade.php'],
            'plugin uninstalled actions' => ['resources/views/admin/settings/plugins/partials/uninstalled-actions.blade.php'],
            'plugin online detail' => ['resources/views/admin/settings/plugins/show-online.blade.php'],
            'theme installed actions' => ['resources/views/admin/settings/themes/partials/installed-actions.blade.php'],
            'theme uninstalled actions' => ['resources/views/admin/settings/themes/partials/uninstalled-actions.blade.php'],
        ];
    }

    #[DataProvider('views')]
    public function test_extension_names_are_escaped_in_modal_messages(string $path): void
    {
        $source = file_get_contents(base_path($path));
        preg_match_all('/:message="[^"]*"/', $source, $matches);

        $this->assertNotEmpty($matches[0], "{$path} is expected to render a modal message.");

        foreach ($matches[0] as $message) {
            if (! preg_match('/\$(card|details)\[\'name\'\]/', $message)) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/e\(\$(card|details)\[\'name\'\]/',
                $message,
                "An extension name reaches a raw modal message unescaped in {$path}: {$message}"
            );
        }
    }
}
