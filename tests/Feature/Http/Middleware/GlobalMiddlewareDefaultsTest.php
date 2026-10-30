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

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\CheckInstallationReady;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Middleware\ValidatePathEncoding;
use Illuminate\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The global middleware stack keeps Laravel's defaults (#642)
 *
 * bootstrap/app.php sets the stack with `use([...])`, which replaces the
 * framework's defaults instead of adding to them. Leaving them out once
 * dropped TrimStrings and ConvertEmptyStringsToNull, so an empty input
 * reached controllers as '' and a nullable column on MySQL rejected it.
 */
class GlobalMiddlewareDefaultsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckInstallationReady::class);

        Route::post('/_test/global-middleware-echo', fn (Request $request) => response()->json([
            'input' => $request->all(),
        ]));
    }

    public function test_the_stack_keeps_laravels_defaults_apart_from_cors(): void
    {
        $global = app(Kernel::class)->getGlobalMiddleware();

        foreach ([
            PreventRequestsDuringMaintenance::class,
            ValidatePathEncoding::class,
            InvokeDeferredCallbacks::class,
            TrustProxies::class,
            ValidatePostSize::class,
            TrimStrings::class,
            ConvertEmptyStringsToNull::class,
        ] as $middleware) {
            $this->assertContains($middleware, $global);
        }

        // Dropped on purpose: the framework's default CORS config opens
        // `api/*` to every origin. See the comment in bootstrap/app.php.
        $this->assertNotContains(HandleCors::class, $global);
        $this->assertSame(PreventRequestsDuringMaintenance::class, $global[0]);
    }

    public function test_an_empty_input_arrives_as_null(): void
    {
        $this->post('/_test/global-middleware-echo', ['note' => '', 'nested' => ['value' => '']])
            ->assertOk()
            ->assertExactJson(['input' => ['note' => null, 'nested' => ['value' => null]]]);
    }

    public function test_input_strings_are_trimmed(): void
    {
        $this->post('/_test/global-middleware-echo', ['name' => '  Example  '])
            ->assertOk()
            ->assertJsonPath('input.name', 'Example');
    }

    public function test_secrets_and_content_keep_their_whitespace(): void
    {
        $values = [
            'password' => ' secret ',
            'mail_password' => ' secret ',
            'db_password' => ' secret ',
            'admin_password' => ' secret ',
            'content' => "    indented code\n",
            'custom_css' => "body {}\n",
            'custom_js' => "\nconsole.log(1);\n",
        ];

        $response = $this->post('/_test/global-middleware-echo', $values)->assertOk();

        foreach ($values as $field => $value) {
            $response->assertJsonPath('input.'.$field, $value);
        }
    }
}
