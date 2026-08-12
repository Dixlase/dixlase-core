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

namespace Tests\Feature\Security;

use Tests\TestCase;

/**
 * Every authentication path has to rotate the session ID when the session
 * gains privileges, otherwise an ID an attacker fixed in the victim's browser
 * beforehand keeps working afterwards.
 *
 * Password login (LoginTrait) and 2FA completion (TwoFaAuthenticationTrait)
 * both did. Passkey login did not: it called Auth::guard()->login() and went
 * straight on to forget the challenge keys. It was the only entry point
 * missing the rotation, which is what made it look deliberate rather than
 * dropped.
 *
 * Asserted against the source because driving a real WebAuthn assertion in a
 * test needs an authenticator; what has to hold is that the call is present
 * and sits after the login, and that is exactly what the source shows.
 */
class PasskeyLoginSessionRotationTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_passkey_login_rotates_the_session(): void
    {
        $source = $this->source('app/Traits/PasskeyLoginTrait.php');

        $this->assertStringContainsString(
            '$request->session()->regenerate()',
            $source,
            'Passkey login must rotate the session ID, like every other authentication path.'
        );
    }

    /**
     * Order matters: rotating before the login would regenerate an
     * unauthenticated session and leave the authenticated one on the old ID.
     */
    public function test_the_rotation_happens_after_the_login(): void
    {
        $source = $this->source('app/Traits/PasskeyLoginTrait.php');

        $login = strpos($source, 'Auth::guard($guardName)->login($user, true)');
        $rotate = strpos($source, '$request->session()->regenerate()');

        $this->assertNotFalse($login, 'The passkey login call is expected in this trait.');
        $this->assertNotFalse($rotate, 'The rotation is expected in this trait.');
        $this->assertGreaterThan(
            $login,
            $rotate,
            'The session must be rotated after the user is logged in, not before.'
        );
    }

    /**
     * The sibling paths are pinned alongside it. The defect was an
     * inconsistency between them, so the value is in them staying consistent
     * rather than in any one of them individually.
     *
     * @return list<array{0: string, 1: string}>
     */
    public static function authenticationPaths(): array
    {
        return [
            'password login' => ['app/Traits/LoginTrait.php', 'session()->regenerate()'],
            '2FA completion' => ['app/Traits/TwoFa/TwoFaAuthenticationTrait.php', 'session()->regenerate(true)'],
            'passkey login' => ['app/Traits/PasskeyLoginTrait.php', 'session()->regenerate()'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('authenticationPaths')]
    public function test_every_authentication_path_rotates_the_session(string $path, string $call): void
    {
        $this->assertStringContainsString(
            $call,
            $this->source($path),
            "{$path} authenticates a session and must rotate its ID."
        );
    }
}
