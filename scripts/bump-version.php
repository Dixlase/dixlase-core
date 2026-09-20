#!/usr/bin/env php
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

declare(strict_types=1);

/**
 * Bump the core version in every place that must stay in lockstep.
 *
 * The VERSION file is authoritative (read by the install baseline in
 * InstallConfirmController and by VersionDriftService), while dixlase.json
 * carries a descriptive "version". They are easy to update independently and
 * then drift; this script updates both from one command so a release can
 * never bump one and forget the other. VersionManifestConsistencyTest fails
 * CI if they ever disagree.
 *
 * Usage:
 *   php scripts/bump-version.php <x.y.z>
 *   php scripts/bump-version.php 0.3.27
 *   php scripts/bump-version.php 0.3.27-dryrun-5
 */
$root = dirname(__DIR__);

$new = $argv[1] ?? '';

// Accept semver plus the project's pre-release suffixes (e.g. -dryrun-5).
if (! preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $new)) {
    fwrite(STDERR, "Usage: php scripts/bump-version.php <x.y.z>\n");
    fwrite(STDERR, "  version must be semver-ish, e.g. 0.3.27 or 0.3.27-dryrun-5\n");
    exit(1);
}

// 1. VERSION file (single line + trailing newline, matching readVersionFromDisk()).
$versionPath = $root.'/VERSION';
if (file_put_contents($versionPath, $new."\n") === false) {
    fwrite(STDERR, "Failed to write {$versionPath}\n");
    exit(1);
}

// 2. dixlase.json "version". Targeted replacement of the first "version" key
//    only, so the file's formatting and unescaped unicode (the JA description)
//    are preserved — a full json_decode/encode round-trip would reflow both.
$manifestPath = $root.'/dixlase.json';
$json = file_get_contents($manifestPath);
if ($json === false) {
    fwrite(STDERR, "Failed to read {$manifestPath}\n");
    exit(1);
}

$count = 0;
$updated = preg_replace(
    '/("version"\s*:\s*")[^"]*(")/',
    '${1}'.$new.'${2}',
    $json,
    1,
    $count
);

if ($updated === null || $count !== 1) {
    fwrite(STDERR, "Failed to update \"version\" in dixlase.json (matched {$count} times, expected 1)\n");
    exit(1);
}

if (file_put_contents($manifestPath, $updated) === false) {
    fwrite(STDERR, "Failed to write {$manifestPath}\n");
    exit(1);
}

fwrite(STDOUT, "Bumped core version to {$new}:\n");
fwrite(STDOUT, "  VERSION\n");
fwrite(STDOUT, "  dixlase.json\n");
