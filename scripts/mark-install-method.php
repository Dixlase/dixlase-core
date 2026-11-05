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
 *
 * Record how Dixlase was installed in .env (DIXLASE_INSTALL_METHOD).
 *
 * Usage: php scripts/mark-install-method.php <docker|oneliner|composer> [path/to/.env]
 *
 * Called from composer's `post-create-project-cmd` with "composer", so a
 * `composer create-project` install is told apart from a release ZIP or a
 * git clone. An existing value is left alone: whoever set it first knows
 * best. Framework-independent: runs before the app is bootable.
 */
$method = $argv[1] ?? '';
$envFile = $argv[2] ?? (getcwd() ?: '.').'/.env';

if (! in_array($method, ['docker', 'oneliner', 'composer'], true)) {
    fwrite(STDERR, "Usage: php scripts/mark-install-method.php <docker|oneliner|composer> [path/to/.env]\n");
    exit(1);
}

if (! is_file($envFile)) {
    // Nothing to mark; the wizard falls back to its own detection.
    exit(0);
}

$env = (string) file_get_contents($envFile);

if (preg_match('/^DIXLASE_INSTALL_METHOD=\S/m', $env) === 1) {
    exit(0);
}

if (preg_match('/^DIXLASE_INSTALL_METHOD=.*$/m', $env) === 1) {
    // An empty placeholder: fill it in place.
    $env = (string) preg_replace('/^DIXLASE_INSTALL_METHOD=.*$/m', 'DIXLASE_INSTALL_METHOD='.$method, $env, 1);
} elseif (preg_match('/^#[ \t]*DIXLASE_INSTALL_METHOD=[ \t]*$/m', $env) === 1) {
    // The commented-out placeholder from .env.example: set it there, under its explanation.
    $env = (string) preg_replace('/^#[ \t]*DIXLASE_INSTALL_METHOD=[ \t]*$/m', 'DIXLASE_INSTALL_METHOD='.$method, $env, 1);
} else {
    $env = rtrim($env, "\n")."\n\n# How Dixlase was installed (read by the setup wizard)\nDIXLASE_INSTALL_METHOD=".$method."\n";
}

file_put_contents($envFile, $env);
