<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
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

namespace Tests\Unit\Services;

use App\Enums\MemberRole;
use App\Services\PermissionRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * A permission node can be a route in its own right and the parent of
 * sub-actions that need stricter roles -- a trash screen an editor may open,
 * holding a permanent-delete action only an admin may run.
 *
 * The walker used to return a node the moment it carried access_roles, without
 * looking at its children. Every sub-action then inherited the parent's roles
 * while the config read as if it restricted them, which is the failure mode
 * worth a test: nothing errors, the screens work, and the restriction is
 * simply absent.
 *
 * Measured against DixlasePages before the fix:
 *
 *     pages.trash.empty          declared ADMIN  -> resolved EDITOR
 *     pages.trash.force-destroy  declared ADMIN  -> resolved EDITOR
 */
class PermissionRegistryNestedKeyTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private static function permissions(): array
    {
        return [
            'pages' => [
                'children' => [
                    // Leaf with no children: deeper keys must still inherit it,
                    // which is how `settings.update` gets its roles.
                    'settings' => [
                        'access_roles' => MemberRole::SUPER_ADMIN->value,
                        'view_roles' => MemberRole::ADMIN->value,
                    ],
                    // Both a route and a parent.
                    'trash' => [
                        'access_roles' => MemberRole::EDITOR->value,
                        'view_roles' => MemberRole::EDITOR->value,
                        'children' => [
                            'restore' => [
                                'access_roles' => MemberRole::EDITOR->value,
                                'view_roles' => MemberRole::EDITOR->value,
                            ],
                            'empty' => [
                                'access_roles' => MemberRole::ADMIN->value,
                                'view_roles' => MemberRole::ADMIN->value,
                            ],
                        ],
                    ],
                    // Parent only: no bare route, so no access_roles.
                    'revisions' => [
                        'children' => [
                            'protect' => [
                                'access_roles' => MemberRole::ADMIN->value,
                                'view_roles' => MemberRole::ADMIN->value,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function resolve(string $menuKey): ?array
    {
        $method = new ReflectionMethod(PermissionRegistry::class, 'getDefaultFromNestedArray');
        $method->setAccessible(true);

        return $method->invoke(null, self::permissions(), $menuKey);
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function nestedKeys(): array
    {
        return [
            'parent that is also a route' => ['pages.trash', MemberRole::EDITOR->value],
            'child matching the parent' => ['pages.trash.restore', MemberRole::EDITOR->value],
            'child stricter than the parent' => ['pages.trash.empty', MemberRole::ADMIN->value],
            'child under a parent-only node' => ['pages.revisions.protect', MemberRole::ADMIN->value],
            'leaf' => ['pages.settings', MemberRole::SUPER_ADMIN->value],
        ];
    }

    #[DataProvider('nestedKeys')]
    public function test_a_child_is_not_given_its_parents_roles(string $menuKey, int $expected): void
    {
        $resolved = $this->resolve($menuKey);

        $this->assertNotNull($resolved, "{$menuKey} must resolve to a definition.");
        $this->assertSame(
            $expected,
            $resolved['access_roles'],
            "{$menuKey} must resolve to the roles its own entry declares, not the nearest ancestor's."
        );
    }

    /**
     * Inheritance still has to work where nothing deeper is declared:
     * `settings` has no children, so `settings.update` takes its roles. Losing
     * this would silently drop every such route to the ADMIN fallback.
     */
    public function test_a_key_below_a_leaf_still_inherits_that_leaf(): void
    {
        $resolved = $this->resolve('pages.settings.update');

        $this->assertNotNull($resolved, 'A key below a leaf must still resolve.');
        $this->assertSame(MemberRole::SUPER_ADMIN->value, $resolved['access_roles']);
    }

    /**
     * A key that names a child which does not exist must not fall back to the
     * parent -- that is the inheritance the fix removes. Returning null sends
     * the caller to its own ADMIN default, which is the fail-closed side.
     */
    public function test_an_undeclared_sibling_does_not_inherit_the_parent(): void
    {
        $this->assertNull(
            $this->resolve('pages.trash.purge-everything'),
            'An undeclared action under a parent with children must not inherit the parent.'
        );
    }

    public function test_an_unknown_key_resolves_to_nothing(): void
    {
        $this->assertNull($this->resolve('pages.nonexistent'));
        $this->assertNull($this->resolve('nonexistent'));
    }
}
