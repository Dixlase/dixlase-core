<?php

namespace Tests\Unit\Services\Admin;

use App\Services\Admin\AdminNavigationManager;
use Tests\TestCase;

/**
 * Covers merging of plugin navigation into Core's admin.navigation,
 * especially the cross-plugin case where two plugins declare the SAME
 * top-level section (each with `_insert_after`) and expect their children
 * to be combined rather than one overwriting the other.
 */
class AdminNavigationManagerTest extends TestCase
{
    private AdminNavigationManager $manager;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new AdminNavigationManager();

        config(['admin.navigation' => [
            'front' => ['text' => 'front.text', 'children' => []],
        ]]);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    /**
     * Write a navigation array to a temp file that mergeNavigationFile() can
     * `require`, mimicking a plugin's config/admin/navigation.php.
     *
     * @param  array<string, mixed>  $nav
     */
    private function navFile(array $nav): string
    {
        $path = tempnam(sys_get_temp_dir(), 'nav');
        file_put_contents($path, '<?php return '.var_export($nav, true).';');
        $this->tempFiles[] = $path;

        return $path;
    }

    public function test_two_plugins_sharing_an_inserted_section_merge_their_children(): void
    {
        $pluginA = $this->navFile([
            'cookie' => [
                '_insert_after' => 'front',
                'text' => 'plugin-a::cookie.text',
                'icon' => 'fas fa-fw fa-cookie-bite',
                'children' => [
                    'cookie-settings' => [
                        'text' => 'plugin-a::cookie.settings',
                        'route' => 'plugin-a.cookie.settings',
                    ],
                ],
            ],
        ]);

        $pluginB = $this->navFile([
            'cookie' => [
                '_insert_after' => 'front',
                'text' => 'plugin-b::cookie.text',
                'icon' => 'fas fa-fw fa-cookie-bite',
                'children' => [
                    'cookie-consents' => [
                        'text' => 'plugin-b::cookie.consents',
                        'route' => 'plugin-b.cookie.consents',
                    ],
                ],
            ],
        ]);

        $this->manager->mergeNavigationFile($pluginA);
        $this->manager->mergeNavigationFile($pluginB);

        $children = config('admin.navigation.cookie.children');
        $this->assertIsArray($children);
        // Before the fix, the second plugin's ordered insert overwrote the
        // section and this child was lost.
        $this->assertArrayHasKey('cookie-settings', $children);
        $this->assertArrayHasKey('cookie-consents', $children);

        // The section keeps the position from its first declaration.
        $this->assertSame(['front', 'cookie'], array_keys(config('admin.navigation')));

        // First declaration's section-level properties are preserved, and no
        // ordering hint leaks into the stored config.
        $this->assertSame('plugin-a::cookie.text', config('admin.navigation.cookie.text'));
        $this->assertArrayNotHasKey('_insert_after', config('admin.navigation.cookie'));
    }

    public function test_new_section_is_inserted_after_its_target(): void
    {
        config(['admin.navigation' => [
            'front' => ['text' => 'front'],
            'tail' => ['text' => 'tail'],
        ]]);

        $file = $this->navFile([
            'mid' => ['_insert_after' => 'front', 'text' => 'mid'],
        ]);

        $this->manager->mergeNavigationFile($file);

        $this->assertSame(['front', 'mid', 'tail'], array_keys(config('admin.navigation')));
    }

    public function test_existing_child_properties_are_overwritten_but_siblings_kept(): void
    {
        $base = $this->navFile([
            'cookie' => [
                '_insert_after' => 'front',
                'text' => 'cookie',
                'children' => [
                    'settings' => ['text' => 'old', 'route' => 'r.settings'],
                ],
            ],
        ]);
        $override = $this->navFile([
            'cookie' => [
                'text' => 'cookie',
                'children' => [
                    'settings' => ['text' => 'new'],
                    'log' => ['text' => 'log', 'route' => 'r.log'],
                ],
            ],
        ]);

        $this->manager->mergeNavigationFile($base);
        $this->manager->mergeNavigationFile($override);

        $children = config('admin.navigation.cookie.children');
        $this->assertSame('new', $children['settings']['text']);
        $this->assertSame('r.settings', $children['settings']['route']);
        $this->assertArrayHasKey('log', $children);
    }
}
