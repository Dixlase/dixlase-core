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

declare(strict_types=1);

namespace App\Support\Content\Embed;

use InvalidArgumentException;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\Xml;

/**
 * Render an embed for an allowlisted service
 *
 * A YouTube video becomes an iframe on youtube-nocookie.com, and the page's
 * CSP gains that origin in frame-src. An X post or a GitHub repository becomes
 * a link card; no third-party script is loaded. A URL that does not match the
 * service's pattern stays a plain link, and one that is not http(s) stays text.
 */
class ContentEmbedRenderer implements NodeRendererInterface
{
    /** @var list<string> */
    public const SERVICES = ['youtube', 'x', 'github'];

    public const YOUTUBE_ORIGIN = 'https://www.youtube-nocookie.com';

    private const CARD_STYLE = 'display:block;padding:0.75em 1em;border:1px solid rgb(127 127 127 / 0.35);border-radius:0.5em;text-decoration:none';

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (! $node instanceof ContentEmbed) {
            throw new InvalidArgumentException('Incompatible node type: '.$node::class);
        }

        $url = $this->parse($node->url);
        if ($url === null) {
            return '<p>'.Xml::escape('@['.$node->service.']('.$node->url.')').'</p>';
        }

        $html = match ($node->service) {
            'youtube' => $this->youtube($url),
            'x' => $this->xPost($url),
            'github' => $this->github($url),
            default => null,
        };

        return $html ?? '<p><a href="'.Xml::escape($node->url).'">'.Xml::escape($node->url).'</a></p>';
    }

    /**
     * @return array{url: string, host: string, segments: list<string>, query: array<string, mixed>}|null
     */
    private function parse(string $url): ?array
    {
        $parts = parse_url($url);
        if ($parts === false || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || ! isset($parts['host'])) {
            return null;
        }

        parse_str($parts['query'] ?? '', $query);

        return [
            'url' => $url,
            'host' => (string) preg_replace('/^(www|m|mobile)\./', '', strtolower($parts['host'])),
            'segments' => array_values(array_filter(explode('/', $parts['path'] ?? ''), fn (string $segment): bool => $segment !== '')),
            'query' => $query,
        ];
    }

    /**
     * @param  array{url: string, host: string, segments: list<string>, query: array<string, mixed>}  $url
     */
    private function youtube(array $url): ?string
    {
        $segments = $url['segments'];
        $id = match (true) {
            $url['host'] === 'youtu.be' => $segments[0] ?? null,
            ! in_array($url['host'], ['youtube.com', 'youtube-nocookie.com'], true) => null,
            ($segments[0] ?? '') === 'watch' => $url['query']['v'] ?? null,
            in_array($segments[0] ?? '', ['embed', 'shorts', 'live', 'v'], true) => $segments[1] ?? null,
            default => null,
        };
        if (! is_string($id) || ! preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
            return null;
        }

        csp_add_frame_src(self::YOUTUBE_ORIGIN);

        $start = $this->seconds($url['query']['t'] ?? $url['query']['start'] ?? null);
        $src = self::YOUTUBE_ORIGIN.'/embed/'.$id.($start > 0 ? '?start='.$start : '');

        return '<div class="dls-embed dls-embed-youtube" style="position:relative;width:100%;aspect-ratio:16 / 9">'
            .'<iframe src="'.Xml::escape($src).'" title="'.Xml::escape(__('components/content-editor.embed.youtube_title')).'"'
            .' loading="lazy" referrerpolicy="strict-origin-when-cross-origin"'
            .' allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen'
            .' style="position:absolute;inset:0;width:100%;height:100%;border:0"></iframe></div>';
    }

    /**
     * Seconds from a YouTube start time: `90`, `90s` or `1h2m3s`
     */
    private function seconds(mixed $value): int
    {
        if (! is_string($value) || ! preg_match('/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s?)?$/', $value, $m)) {
            return 0;
        }

        return (int) ($m[1] ?? 0) * 3600 + (int) ($m[2] ?? 0) * 60 + (int) ($m[3] ?? 0);
    }

    /**
     * @param  array{url: string, host: string, segments: list<string>, query: array<string, mixed>}  $url
     */
    private function xPost(array $url): ?string
    {
        [$user, $status, $id] = array_pad($url['segments'], 3, '');
        if (! in_array($url['host'], ['x.com', 'twitter.com'], true) || $status !== 'status'
            || ! preg_match('/^[A-Za-z0-9_]{1,15}$/', $user) || ! preg_match('/^\d{1,20}$/', $id)) {
            return null;
        }

        return $this->card(
            'x',
            'https://x.com/'.$user.'/status/'.$id,
            __('components/content-editor.embed.x_title', ['user' => '@'.$user]),
            'X · x.com/'.$user.'/status/'.$id,
        );
    }

    /**
     * @param  array{url: string, host: string, segments: list<string>, query: array<string, mixed>}  $url
     */
    private function github(array $url): ?string
    {
        [$owner, $repo, $kind, $number] = array_pad($url['segments'], 4, '');
        if ($url['host'] !== 'github.com'
            || ! preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})$/', $owner)
            || ! preg_match('/^[A-Za-z0-9._-]{1,100}$/', $repo)) {
            return null;
        }

        $title = $owner.'/'.$repo;
        if (in_array($kind, ['issues', 'pull', 'discussions'], true) && ctype_digit($number)) {
            $title .= '#'.$number;
        }

        $path = implode('/', $url['segments']);

        return $this->card('github', $url['url'], $title, 'GitHub · github.com/'.$path);
    }

    private function card(string $service, string $href, string $title, string $caption): string
    {
        return '<p class="dls-embed dls-embed-card dls-embed-'.$service.'">'
            .'<a href="'.Xml::escape($href).'" target="_blank" rel="noopener noreferrer" style="'.self::CARD_STYLE.'">'
            .'<strong style="display:block">'.Xml::escape($title).'</strong>'
            .'<span style="display:block;font-size:0.875em;opacity:0.75">'.Xml::escape($caption).'</span>'
            .'</a></p>';
    }
}
