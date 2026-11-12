<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace Tests\Feature\Services\Install;

use App\Services\Install\InstallFinalizer;
use App\Services\Security\IntegrityKeyring;
use App\Support\Install\EnvFile;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Core #665: a new install gets an integrity key, and the chain built at the
 * end of the install is keyed with it.
 *
 * finalize() rewrites .env, so it is not called here; the key decision is
 * called directly and the ordering is read from the source.
 */
class InstallIntegrityKeyTest extends TestCase
{
    public function test_a_new_install_gets_a_first_version_key(): void
    {
        if (EnvFile::read('INTEGRITY_KEY') !== '') {
            $this->markTestSkipped('This checkout\'s .env already carries an integrity key.');
        }
        config(['security.integrity_keys.current' => null]);

        $key = $this->newIntegrityKey();

        $this->assertNotNull($key);
        $this->assertSame(IntegrityKeyring::FIRST_VERSION, IntegrityKeyring::versionOf($key));
    }

    public function test_a_key_that_is_already_configured_is_kept(): void
    {
        config(['security.integrity_keys.current' => IntegrityKeyring::generate(2)]);

        $this->assertNull($this->newIntegrityKey());
    }

    public function test_the_key_is_in_effect_before_the_install_chain_is_built(): void
    {
        $source = (string) file_get_contents(app_path('Services/Install/InstallFinalizer.php'));

        $configured = strpos($source, "config(['security.integrity_keys.current' => \$integrityKey]);");
        $built = strpos($source, '$this->buildAuditLogHashChain();');

        $this->assertNotFalse($configured);
        $this->assertNotFalse($built);
        $this->assertLessThan($built, $configured);
    }

    private function newIntegrityKey(): ?string
    {
        $method = new ReflectionMethod(InstallFinalizer::class, 'newIntegrityKey');

        return $method->invoke(app(InstallFinalizer::class));
    }
}
