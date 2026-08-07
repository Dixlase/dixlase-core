<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Http\Controllers\Admin\Settings;

use App\Models\ExtensionSource;
use App\Services\Extension\ExtensionSourceManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serve `resources/assets/thumbnail.{webp,png,jpg,jpeg}` for an installed
 * plugin or theme, decoupled from the `public/assets/{plugins,themes}/{Dir}`
 * symlink whose lifecycle is tied to enable/disable.
 *
 * The plugin / theme admin lists want to show the extension's bundled
 * thumbnail as soon as its files are on disk — before it is enabled, and
 * for the whole time it is disabled — but the enable/disable symlink is
 * intentionally scoped to runtime asset exposure (JS / CSS from a disabled
 * plugin should stop being web-served). Reading the file directly from
 * `plugins/{Dir}/resources/assets/thumbnail.*` sidesteps the symlink and
 * lets the card image stay visible for every installed extension without
 * changing that runtime posture.
 *
 * Only image files named exactly `thumbnail.<ext>` are served. Anything
 * else — `plugin.json`, `composer.json`, `signature.sig`, arbitrary
 * `resources/…` paths — is never reachable through this endpoint.
 */
class AdminExtensionThumbnailController extends Controller
{
    /** Extension image formats probed in priority order (modern/small first). */
    private const EXTENSIONS = ['webp', 'png', 'jpg', 'jpeg'];

    private const MIME_TYPES = [
        'webp' => 'image/webp',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
    ];

    public function show(Request $request, string $type, string $directory): Response
    {
        // The route regex already restricts `$type` and `$directory`, but
        // re-validate here so the controller is safe if it is ever wired
        // to a broader route.
        if (! in_array($type, ['plugins', 'themes'], true)) {
            abort(404);
        }
        if ($directory === '' || ! preg_match('/^[A-Za-z0-9_-]+$/', $directory)) {
            abort(404);
        }

        foreach (self::EXTENSIONS as $extension) {
            $absolute = base_path("{$type}/{$directory}/resources/assets/thumbnail.{$extension}");
            if (! is_file($absolute)) {
                continue;
            }

            $etag = '"'.substr((string) hash_file('xxh128', $absolute), 0, 16).'"';
            $ifNoneMatch = $request->headers->get('If-None-Match');
            if ($ifNoneMatch !== null && $ifNoneMatch === $etag) {
                return response('', 304, [
                    'ETag' => $etag,
                    'Cache-Control' => 'private, max-age=3600',
                ]);
            }

            $response = new BinaryFileResponse($absolute);
            $response->headers->set('Content-Type', self::MIME_TYPES[$extension]);
            $response->headers->set('Cache-Control', 'private, max-age=3600');
            $response->headers->set('ETag', $etag);
            // Force inline so browsers render the image instead of
            // suggesting a download for image/webp requests.
            $response->setContentDisposition('inline', "thumbnail.{$extension}");

            return $response;
        }

        abort(404);
    }

    /**
     * Serve a thumbnail for an extension that is available from an
     * external source but not yet installed locally.
     *
     * The "Add plugin" / "Add theme" pages list candidates fetched from
     * the configured extension sources. Those thumbnails live inside a
     * remote repository — often a private one — and would fail to load
     * if the browser tried to fetch them directly:
     *
     *   - `raw.githubusercontent.com` for a private repo returns 404
     *     for unauthenticated requests, and there is no standard way
     *     for a browser to attach a GitHub token to an <img> load.
     *   - The manifest may override the standard `thumbnail.png` path,
     *     so guessing the URL client-side is fragile.
     *
     * This endpoint delegates to the source provider, which fetches the
     * image bytes server-side using the same credential the rest of the
     * source flow uses, and streams them back through the admin origin.
     * The result is cached briefly so the plugin list doesn't cost one
     * GitHub API call per card per render.
     *
     * A fetch failure falls back to the default SVG rather than 404, so
     * the card list still renders cleanly when a repo lacks a thumbnail
     * or the auth is misconfigured (the tokens page is where the
     * operator diagnoses that; the thumbnail is not the place to shout).
     */
    public function showOnline(
        Request $request,
        ExtensionSource $source,
        string $type,
        string $slug,
        ExtensionSourceManager $manager,
    ): Response {
        if (! in_array($type, ['plugin', 'theme'], true)) {
            abort(404);
        }
        if ($slug === '' || ! preg_match('/^[a-z0-9-]+$/', $slug)) {
            abort(404);
        }
        if (! $source->is_enabled) {
            return $this->fallbackThumbnail($type);
        }

        $cacheKey = "extension-thumbnail-online:{$source->id}:{$type}:{$slug}";
        // 30-minute TTL: fresh enough that a re-published thumbnail
        // shows up soon, long enough that the admin plugin-list doesn't
        // hammer the source API on every reload.
        //
        // The image bytes are base64-encoded in the cache payload rather
        // than stored raw. The default `dls_cache` store is a utf8mb4
        // MySQL column, which rejects the raw PNG magic (`\x89PNG…`)
        // even when the value is serialized — the surrounding
        // serialize() envelope still contains the non-UTF-8 bytes
        // inline. Base64 keeps the payload text-only and about 33 %
        // bigger; a ~400 KB thumbnail becomes ~550 KB, well inside the
        // TEXT column limit for the sizes any extension realistically
        // ships as its card image.
        $cached = Cache::remember($cacheKey, 1800, function () use ($source, $type, $slug, $manager) {
            try {
                $provider = $manager->makeProvider($source);
                $fetched = $provider->fetchThumbnail($slug, $type);
            } catch (\Throwable) {
                return;
            }

            if (! is_array($fetched) || ! isset($fetched['content'], $fetched['mime'])) {
                return;
            }

            return [
                'content_b64' => base64_encode($fetched['content']),
                'mime' => $fetched['mime'],
            ];
        });

        if (! is_array($cached) || ! isset($cached['content_b64'], $cached['mime'])) {
            return $this->fallbackThumbnail($type);
        }

        $bytes = base64_decode($cached['content_b64'], true);
        if ($bytes === false) {
            return $this->fallbackThumbnail($type);
        }

        $etag = '"'.substr(md5($bytes), 0, 16).'"';
        $ifNoneMatch = $request->headers->get('If-None-Match');
        if ($ifNoneMatch !== null && $ifNoneMatch === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'private, max-age=1800',
            ]);
        }

        return response($bytes, 200, [
            'Content-Type' => $cached['mime'],
            'Cache-Control' => 'private, max-age=1800',
            'ETag' => $etag,
        ]);
    }

    /**
     * Redirect to the bundled default image so the card list still
     * renders a picture when the source fetch produces nothing.
     */
    private function fallbackThumbnail(string $type): Response
    {
        $asset = $type === 'theme' ? 'assets/images/theme-default.svg' : 'assets/images/plugin-default.svg';

        return redirect(asset($asset));
    }
}
