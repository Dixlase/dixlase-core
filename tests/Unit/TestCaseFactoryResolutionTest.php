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

namespace Tests\Unit;

use App\Models\Site;
use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Pins how a model factory is resolved for a site that has extensions
 * installed.
 *
 * An extension may ship a factory for a core model, and it wins over core's
 * own. Which extension wins used to be two class names written into
 * TestCase; it is now configurable, with a default that names nothing
 * (dixlase-core#619). These tests hold both halves in place: the
 * configuration is honoured exactly as written, and the default order is
 * stable rather than whatever the filesystem returns.
 */
class TestCaseFactoryResolutionTest extends TestCase
{
    protected function tearDown(): void
    {
        // The memo is static and this test writes to it, so it has to go
        // back to "not yet resolved" or the rest of the process would keep
        // this test's configured order.
        $this->resetFactoryNamespaceMemo();

        parent::tearDown();
    }

    public function test_configured_extensions_are_searched_in_the_order_given(): void
    {
        config(['testing.factory_extensions' => ['AcmeAccounts', 'AcmeTooling']]);
        $this->resetFactoryNamespaceMemo();

        $this->assertSame([
            'Plugins\\AcmeAccounts\\Database\\Factories\\',
            'Plugins\\AcmeTooling\\Database\\Factories\\',
        ], $this->factoryNamespaces());
    }

    public function test_the_default_order_is_sorted_and_free_of_duplicates(): void
    {
        config(['testing.factory_extensions' => []]);
        $this->resetFactoryNamespaceMemo();

        $namespaces = $this->factoryNamespaces();
        $sorted = $namespaces;
        sort($sorted);

        $this->assertSame($sorted, $namespaces, 'the discovered order has to be stable, not filesystem order');
        $this->assertSame(array_values(array_unique($namespaces)), $namespaces);

        foreach ($namespaces as $namespace) {
            $this->assertStringStartsWith('Plugins\\', $namespace);
            $this->assertStringEndsWith('\\Database\\Factories\\', $namespace);
        }
    }

    public function test_a_core_model_that_no_extension_shadows_resolves_to_cores_own_factory(): void
    {
        $this->assertInstanceOf(SiteFactory::class, Factory::factoryForModel(Site::class));
    }

    /**
     * @return list<string>
     */
    private function factoryNamespaces(): array
    {
        $method = new ReflectionMethod(TestCase::class, 'extensionFactoryNamespaces');
        $method->setAccessible(true);

        /** @var list<string> $namespaces */
        $namespaces = $method->invoke(null);

        return $namespaces;
    }

    private function resetFactoryNamespaceMemo(): void
    {
        $property = new ReflectionProperty(TestCase::class, 'extensionFactoryNamespaces');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}
