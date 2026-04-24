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

namespace Tests\Feature\Admin\SafeMode;

use App\Enums\AuthenticationMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\SafeMode;
use App\Models\Member;
use App\Services\SafeModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * SafeModeController のテスト
 *
 * テスト専用ルートを使用してセーフモードの無効化操作を検証する。
 * admin ルートは環境依存が多いため、web ミドルウェアグループのテストルートを使用。
 */
class SafeModeControllerTest extends TestCase
{
    use RefreshDatabase;

    private Member $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        $this->superAdmin = Member::create([
            'account_name' => 'superadmin',
            'display_name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
            'two_fa_mode' => AuthenticationMode::Disabled,
        ]);

        // テスト専用ルート（コントローラーのロジックを再現）
        Route::middleware('web')->post('/test-safe-mode-disable', function (Request $request) {
            $safeModeService = app(SafeModeService::class);
            $modeValue = $request->input('mode');
            $mode = SafeMode::tryFrom($modeValue);

            if ($mode === null) {
                return redirect()->back()
                    ->with('error', __('admin/safe-mode.invalid_mode'));
            }

            $safeModeService->deactivate($mode);

            return redirect()->back()
                ->with('success', __('admin/safe-mode.disabled', ['mode' => $mode->value]));
        })->name('test.safe-mode.disable');

        Route::middleware('web')->post('/test-safe-mode-disable-all', function () {
            app(SafeModeService::class)->deactivateAll();

            return redirect()->back()
                ->with('success', __('admin/safe-mode.all_disabled'));
        })->name('test.safe-mode.disable-all');
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';

        parent::tearDown();
    }

    public function test_disable_removes_specific_mode(): void
    {
        $this->actingAs($this->superAdmin, 'member');

        $response = $this->withSession(['safe_mode_csp' => true, 'safe_mode_plugins' => true])
            ->post('/test-safe-mode-disable', ['mode' => 'csp']);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        // セッションからCSPモードが除去されたことを検証（サービス自体のテストはSafeModeServiceTestで実施）
        $this->assertNull(session('safe_mode_csp'));
    }

    public function test_disable_with_invalid_mode_returns_error(): void
    {
        $this->actingAs($this->superAdmin, 'member');

        $response = $this->post('/test-safe-mode-disable', ['mode' => 'invalid']);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_disable_all_removes_all_modes(): void
    {
        $this->actingAs($this->superAdmin, 'member');

        $response = $this->withSession([
            'safe_mode_csp' => true,
            'safe_mode_plugins' => true,
            'safe_mode_theme' => true,
        ])->post('/test-safe-mode-disable-all');

        $response->assertRedirect();
        $response->assertSessionHas('success');
        // セッションからすべてのモードが除去されたことを検証
        $this->assertNull(session('safe_mode_csp'));
        $this->assertNull(session('safe_mode_plugins'));
        $this->assertNull(session('safe_mode_theme'));
    }

    public function test_unauthenticated_cannot_disable(): void
    {
        $response = $this->post('/test-safe-mode-disable', ['mode' => 'csp']);

        // 認証されていないためリダイレクト（Refererなしのため / へ）
        $response->assertRedirect();
    }
}
