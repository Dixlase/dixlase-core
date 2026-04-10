<?php

namespace Tests\Unit\Services;

use App\Services\ShortcodeManager;
use Tests\TestCase;

class ShortcodeManagerTest extends TestCase
{
    private ShortcodeManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ShortcodeManager(app());
    }

    public function test_parse_returns_string(): void
    {
        $result = $this->manager->parse('Hello World');

        $this->assertIsString($result);
    }

    public function test_parse_without_shortcodes_returns_original(): void
    {
        $content = '<p>No shortcodes here</p>';

        $result = $this->manager->parse($content);

        $this->assertEquals($content, $result);
    }

    public function test_parse_empty_string(): void
    {
        $result = $this->manager->parse('');

        $this->assertEquals('', $result);
    }

    public function test_add_registers_shortcode(): void
    {
        $this->manager->add('test_sc', ShortcodeManagerTestShortcode::class);

        $result = $this->manager->parse('[test_sc]');
        $this->assertIsString($result);
    }
}

class ShortcodeManagerTestShortcode
{
    public function render(array $attributes = [], ?string $content = null): string
    {
        return '<div>Test Output</div>';
    }
}
