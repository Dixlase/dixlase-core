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
 * Start PHP's built-in server for a local install (#909).
 *
 * Usage: php scripts/serve.php [--host=127.0.0.1] [--port=8000]
 *
 * A short entry point for the command the post-create message used to
 * print in full. It is the server the install wizard needs; `php artisan
 * serve` is not (#879): its .env watcher restarts the server and cuts off
 * the request that wrote .env, and with --no-reload it keeps the .env it
 * read at start-up, so the wizard's database settings never take effect.
 *
 * - Runs from public/ with the router script `artisan serve` uses, which
 *   treats the current directory as the web root.
 * - variables_order=EGPCS keeps PATH for the commands the wizard runs
 *   (composer dump-autoload fails without it).
 * - max_execution_time=300 gives the final step, which migrates, enough time.
 * - PHP_CLI_SERVER_WORKERS=4, except on Windows, which does not support it,
 *   so one slow request does not stall the others.
 *
 * Restart it after a core update or rollback: both recreate public/, and a
 * server started there keeps serving the old directory.
 */
$root = dirname(__DIR__);
$public = $root.'/public';
$router = $root.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';

$options = getopt('', ['host:', 'port:', 'help']);

if (isset($options['help'])) {
    fwrite(STDOUT, 'Usage: php scripts/serve.php [--host=127.0.0.1] [--port=8000]'.PHP_EOL);

    exit(0);
}

$host = is_string($options['host'] ?? null) && $options['host'] !== '' ? $options['host'] : '127.0.0.1';
$port = $options['port'] ?? '8000';

if (! is_string($port) || ! ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
    fwrite(STDERR, 'The port must be a number between 1 and 65535.'.PHP_EOL);

    exit(1);
}

if (! is_file($router)) {
    fwrite(STDERR, 'vendor/ is missing. Run `composer install` first.'.PHP_EOL);

    exit(1);
}

$env = getenv();
if (PHP_OS_FAMILY !== 'Windows' && ! isset($env['PHP_CLI_SERVER_WORKERS'])) {
    $env['PHP_CLI_SERVER_WORKERS'] = '4';
}

$address = (str_contains($host, ':') ? '['.$host.']' : $host).':'.$port;

fwrite(STDOUT, PHP_EOL.'  Dixlase is being served at http://'.$address.PHP_EOL);
fwrite(STDOUT, '  Press Ctrl+C to stop.'.PHP_EOL.PHP_EOL);

$process = proc_open(
    [
        PHP_BINARY,
        '-d', 'variables_order=EGPCS',
        '-d', 'max_execution_time=300',
        '-S', $address,
        $router,
    ],
    [0 => STDIN, 1 => STDOUT, 2 => STDERR],
    $pipes,
    $public,
    $env,
);

if (! is_resource($process)) {
    fwrite(STDERR, 'Could not start the PHP built-in server.'.PHP_EOL);

    exit(1);
}

exit(proc_close($process));
