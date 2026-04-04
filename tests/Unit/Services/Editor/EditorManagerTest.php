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

namespace Tests\Unit\Services\Editor;

use App\Contracts\Plugin\EditorCapableInterface;
use App\DTO\Plugin\CapabilityResolutionResult;
use App\Enums\ContentStorageType;
use App\Services\Editor\EditorManager;
use App\Services\Plugin\PluginServiceResolver;
use Tests\TestCase;

class EditorManagerTest extends TestCase
{
    private function createMockResolver(array $resolvedResults = []): PluginServiceResolver
    {
        $resolver = $this->createMock(PluginServiceResolver::class);
        $resolver->method('resolveAll')->willReturn($resolvedResults);

        return $resolver;
    }

    private function createMockCapability(string $typeSlug = 'gui', string $pluginSlug = 'test-editor'): EditorCapableInterface
    {
        $capability = $this->createMock(EditorCapableInterface::class);
        $capability->method('getPluginSlug')->willReturn($pluginSlug);
        $capability->method('isCapabilityAvailable')->willReturn(true);
        $capability->method('getEditorTypeSlug')->willReturn($typeSlug);
        $capability->method('getEditorLabel')->willReturn(['en' => 'Test Editor', 'ja' => 'テストエディタ']);
        $capability->method('getEditorDescription')->willReturn(['en' => 'Test description', 'ja' => 'テスト説明']);
        $capability->method('getEditorIcon')->willReturn('fas fa-edit');
        $capability->method('getSupportedStorageTypes')->willReturn([ContentStorageType::DATABASE]);
        $capability->method('getEditorViewName')->willReturn('test::editor');
        $capability->method('getPluginDirectoryName')->willReturn('TestEditor');
        $capability->method('getEditorAssets')->willReturn(['js' => ['js/app.js'], 'css' => []]);
        $capability->method('getContentFormat')->willReturn('json');
        $capability->method('renderContent')->willReturn('<p>rendered</p>');

        return $capability;
    }

    public function test_get_available_editors_returns_empty_when_none_registered(): void
    {
        $manager = new EditorManager($this->createMockResolver());
        $this->assertEmpty($manager->getAvailableEditors());
    }

    public function test_get_available_editors_returns_resolved_editors(): void
    {
        $capability = $this->createMockCapability();
        $result = CapabilityResolutionResult::success($capability);
        $manager = new EditorManager($this->createMockResolver([$result]));

        $editors = $manager->getAvailableEditors();
        $this->assertCount(1, $editors);
        $this->assertSame('gui', $editors[0]->typeSlug);
    }

    public function test_has_editor_returns_true_when_available(): void
    {
        $capability = $this->createMockCapability();
        $result = CapabilityResolutionResult::success($capability);
        $manager = new EditorManager($this->createMockResolver([$result]));

        $this->assertTrue($manager->hasEditor('gui'));
        $this->assertFalse($manager->hasEditor('wysiwyg'));
    }

    public function test_get_preferred_editor_returns_single_editor(): void
    {
        $capability = $this->createMockCapability();
        $result = CapabilityResolutionResult::success($capability);
        $manager = new EditorManager($this->createMockResolver([$result]));

        $preferred = $manager->getPreferredEditor('gui');
        $this->assertNotNull($preferred);
        $this->assertSame('gui', $preferred->typeSlug);
    }

    public function test_get_preferred_editor_returns_null_when_none_available(): void
    {
        $manager = new EditorManager($this->createMockResolver());
        $this->assertNull($manager->getPreferredEditor('gui'));
    }

    public function test_render_content_returns_empty_when_no_editor(): void
    {
        $manager = new EditorManager($this->createMockResolver());
        $this->assertSame('', $manager->renderContent('gui', '{"version":1,"blocks":[]}'));
    }

    public function test_get_available_editor_types_returns_unique_types(): void
    {
        $capability = $this->createMockCapability();
        $result = CapabilityResolutionResult::success($capability);
        $manager = new EditorManager($this->createMockResolver([$result]));

        $types = $manager->getAvailableEditorTypes();
        $this->assertSame(['gui'], $types);
    }

    public function test_skips_unresolved_results(): void
    {
        $notFoundResult = CapabilityResolutionResult::notFound();
        $manager = new EditorManager($this->createMockResolver([$notFoundResult]));

        $this->assertEmpty($manager->getAvailableEditors());
    }
}
