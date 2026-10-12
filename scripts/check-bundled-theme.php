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
 *
 * Standalone script that checks composer.lock locks the official theme at
 * the theme repository's latest release -- its highest stable version tag,
 * which is what Composer resolves (a tag may not have a GitHub Release
 * page yet). Exits 1 when it does not. Run it
 * before tagging a core release; release.yml runs it before building the
 * distribution ZIP. Runs without the Laravel framework.
 *
 * Why it is needed: the release ZIP, the one-liner installer and
 * `composer create-project` all install the theme that composer.lock locks.
 * Nothing else moves that lock when the theme cuts a release, so core
 * v0.1.7 shipped the theme's v0.1.0 code while v0.2.0 was out (#517).
 *
 * Reads GITHUB_TOKEN (or GH_TOKEN) when set, to avoid the anonymous API
 * rate limit.
 */
const THEME_PACKAGE = 'dixlase/dixlase-onepage';
const THEME_REPO = 'Dixlase/theme-dixlase-onepage';

$lockPath = dirname(__DIR__).'/composer.lock';
$lock = json_decode((string) @file_get_contents($lockPath), true);

if (! is_array($lock)) {
    fwrite(STDERR, "Could not read {$lockPath}.\n");
    exit(1);
}

$locked = null;

foreach ($lock['packages'] ?? [] as $package) {
    if (($package['name'] ?? '') === THEME_PACKAGE) {
        $locked = (string) ($package['version'] ?? '');
        break;
    }
}

if ($locked === null) {
    fwrite(STDERR, 'composer.lock does not lock '.THEME_PACKAGE.".\n");
    exit(1);
}

$headers = [
    'Accept: application/vnd.github+json',
    'User-Agent: dixlase-check-bundled-theme',
];
$token = getenv('GITHUB_TOKEN') ?: getenv('GH_TOKEN') ?: '';

if ($token !== '') {
    $headers[] = 'Authorization: Bearer '.$token;
}

$context = stream_context_create(['http' => [
    'header' => implode("\r\n", $headers),
    'ignore_errors' => true,
    'timeout' => 30,
]]);
$body = @file_get_contents('https://api.github.com/repos/'.THEME_REPO.'/tags?per_page=100', false, $context);
$tags = is_string($body) ? json_decode($body, true) : null;
$latest = '';

foreach (is_array($tags) ? $tags : [] as $tag) {
    $name = (string) ($tag['name'] ?? '');

    // Stable versions only: Composer skips pre-release tags here because
    // core's minimum-stability is stable.
    if (preg_match('/^v?\d+\.\d+\.\d+$/', $name) !== 1) {
        continue;
    }

    if ($latest === '' || version_compare(ltrim($name, 'v'), ltrim($latest, 'v'), '>')) {
        $latest = $name;
    }
}

if ($latest === '') {
    fwrite(STDERR, 'Could not read the latest release of '.THEME_REPO.".\n");
    exit(1);
}

if (ltrim($locked, 'v') !== ltrim($latest, 'v')) {
    fwrite(STDERR, 'composer.lock locks '.THEME_PACKAGE." {$locked}, but the theme's latest release is {$latest}.\n");
    fwrite(STDERR, 'Run `composer update '.THEME_PACKAGE."` and commit composer.lock before releasing.\n");
    exit(1);
}

echo 'composer.lock locks '.THEME_PACKAGE." {$locked}, the theme's latest release.\n";
