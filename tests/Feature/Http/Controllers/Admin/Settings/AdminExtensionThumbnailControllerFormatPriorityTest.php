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

namespace Tests\Feature\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\Settings\AdminExtensionThumbnailController;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pins the format-priority contract of the installed-extension
 * thumbnail endpoint.
 *
 * The presenter (see ExtensionCardPresenterThumbnailTest) intentionally
 * emits a format-agnostic URL — extension picking (webp > png > jpg >
 * jpeg) happens inside `AdminExtensionThumbnailController::show()`.
 * These tests exercise the controller directly with combinations of
 * fixture files so a future refactor cannot silently re-order the
 * probe.
 */
class AdminExtensionThumbnailControllerFormatPriorityTest extends TestCase
{
    private const PLUGIN_FIXTURE = '__test_thumbnail_ctrl__';

    protected function tearDown(): void
    {
        $this->removeFixtures();

        parent::tearDown();
    }

    public static function formatPriorityScenarios(): array
    {
        return [
            'webp wins over every other format' => [
                ['webp', 'png', 'jpg', 'jpeg'],
                'image/webp',
            ],
            'png wins when webp absent' => [
                ['png', 'jpg', 'jpeg'],
                'image/png',
            ],
            'jpg wins over jpeg' => [
                ['jpg', 'jpeg'],
                'image/jpeg',
            ],
            'jpeg is the last resort' => [
                ['jpeg'],
                'image/jpeg',
            ],
        ];
    }

    #[DataProvider('formatPriorityScenarios')]
    public function test_priority_order(array $formatsPresent, string $expectedMime): void
    {
        $this->removeFixtures();
        foreach ($formatsPresent as $ext) {
            $this->writePluginThumbnail($ext);
        }

        $response = $this->invokeShow('plugins', self::PLUGIN_FIXTURE);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedMime, $response->headers->get('Content-Type'));
    }

    public function test_serves_extension_root_thumbnail_when_only_root_present(): void
    {
        // Recommended location — sits alongside plugin.json / theme.json,
        // safe from build tools that empty resources/assets/ before writing
        // new build output. Must be served even when resources/assets/
        // does not exist at all.
        $this->writePluginRootThumbnail('png', 'root-bytes');

        $response = $this->invokeShow('plugins', self::PLUGIN_FIXTURE);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        // BinaryFileResponse streams from disk; the bytes must be the
        // ones we wrote at the recommended location, not the legacy one.
        $bytes = $this->readResponseBody($response);
        $this->assertSame('root-bytes', $bytes);
    }

    public function test_extension_root_wins_over_legacy_resources_assets(): void
    {
        // Both locations carry a thumbnail of the same format. Controller
        // must prefer the recommended location — the byte content proves
        // which file was actually opened. Same-format-both-locations is
        // the typical migration state (extension author dropped the new
        // file at the root, has not yet removed the old one).
        $this->writePluginRootThumbnail('png', 'root-bytes');
        $this->writePluginThumbnail('png', 'legacy-bytes');

        $response = $this->invokeShow('plugins', self::PLUGIN_FIXTURE);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertSame(
            'root-bytes',
            $this->readResponseBody($response),
            'recommended (extension root) location must win over legacy (resources/assets/) location',
        );
    }

    public function test_format_priority_holds_across_locations(): void
    {
        // A webp at the legacy path outranks a png at the recommended
        // path — format priority is the OUTER loop, source location is
        // the INNER loop, so webp always beats png regardless of where
        // each format sits. Pins the loop nesting.
        $this->writePluginRootThumbnail('png', 'root-png-bytes');
        $this->writePluginThumbnail('webp', 'legacy-webp-bytes');

        $response = $this->invokeShow('plugins', self::PLUGIN_FIXTURE);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/webp', $response->headers->get('Content-Type'));
        $this->assertSame('legacy-webp-bytes', $this->readResponseBody($response));
    }

    public function test_returns_404_when_no_thumbnail_file_present(): void
    {
        // No fixture files written — the directory itself doesn't exist.
        // abort(404) throws NotFoundHttpException; the 404 lives on
        // getStatusCode(), not getCode() (which is always 0 for the
        // Symfony-side HTTP exception hierarchy).
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->invokeShow('plugins', 'this-extension-does-not-exist-'.uniqid());
    }

    public function test_rejects_unknown_extension_type(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        // `custom` is not in the {plugins, themes} allowlist — controller
        // must refuse even if the route regex were ever loosened.
        $this->invokeShow('custom', self::PLUGIN_FIXTURE);
    }

    public function test_rejects_directory_with_path_traversal_characters(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->invokeShow('plugins', '..');
    }

    public function test_returns_304_on_matching_if_none_match(): void
    {
        $this->writePluginThumbnail('png');

        // First request to learn the ETag the controller emits.
        $first = $this->invokeShow('plugins', self::PLUGIN_FIXTURE);
        $etag = $first->headers->get('ETag');
        $this->assertNotNull($etag);

        $second = $this->invokeShow('plugins', self::PLUGIN_FIXTURE, ['If-None-Match' => $etag]);
        $this->assertSame(304, $second->getStatusCode());
        $this->assertSame($etag, $second->headers->get('ETag'));
    }

    private function invokeShow(string $type, string $directory, array $headers = []): \Symfony\Component\HttpFoundation\Response
    {
        $request = Request::create('/stub', 'GET');
        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        $controller = new AdminExtensionThumbnailController();

        return $controller->show($request, $type, $directory);
    }

    private function writePluginThumbnail(string $extension, ?string $body = null): void
    {
        $dir = base_path('plugins/'.self::PLUGIN_FIXTURE.'/resources/assets');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        // A minimal PNG-ish body is fine — the controller streams bytes
        // as-is; content validity is not asserted, only the selection.
        file_put_contents("{$dir}/thumbnail.{$extension}", $body ?? "fixture:{$extension}");
    }

    /**
     * Write a thumbnail at the RECOMMENDED extension-root location
     * (alongside plugin.json) rather than the legacy resources/assets/
     * path. Used to pin the "root wins over legacy" precedence.
     */
    private function writePluginRootThumbnail(string $extension, ?string $body = null): void
    {
        $dir = base_path('plugins/'.self::PLUGIN_FIXTURE);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents("{$dir}/thumbnail.{$extension}", $body ?? "fixture-root:{$extension}");
    }

    /**
     * Read the streamed body of a BinaryFileResponse without leaving
     * bytes on stdout. sendContent() writes to PHP's output buffer,
     * so we wrap it in ob_start / ob_get_clean to capture and return.
     */
    private function readResponseBody(\Symfony\Component\HttpFoundation\Response $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }

    private function removeFixtures(): void
    {
        $root = base_path('plugins/'.self::PLUGIN_FIXTURE);
        if (is_dir($root)) {
            $this->removeDirectory($root);
        }
    }

    private function removeDirectory(string $path): void
    {
        $items = scandir($path);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path.DIRECTORY_SEPARATOR.$item;
            if (is_dir($full) && ! is_link($full)) {
                $this->removeDirectory($full);
            } else {
                @unlink($full);
            }
        }
        @rmdir($path);
    }
}
