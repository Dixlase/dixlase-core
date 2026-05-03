<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Http\Controllers\Admin\Settings\Systems;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\Plugin;
use App\Models\Theme;
use App\Services\Extension\ExtensionSourceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

/**
 * 統合アップデート管理ページ。
 *
 * コア / プラグイン / テーマの更新可能状況を一画面で確認・適用する。
 * - GET  /admin/.../system/updates           ページ表示（古い場合は自動チェック）
 * - POST /admin/.../system/updates/check     強制再チェック → 同ページへリダイレクト
 * - POST /admin/.../system/updates/apply     選択された対象を順次更新
 */
class AdminSystemUpdatesController extends AdminLoggedInController
{
    /**
     * 自動チェックを発動する閾値（秒）。これより前にチェックされていなければロード時に再チェックする。
     */
    protected const STALE_THRESHOLD_SECONDS = 21600; // 6 時間

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * アップデート管理ページを表示。
     *
     * - 最終チェックから STALE_THRESHOLD_SECONDS 以上経過していたら自動再チェック
     * - クエリ ?check=1 で強制再チェック
     * - クエリ ?target=plugin:slug or theme:slug で対象を初期選択
     */
    public function index(Request $request, ExtensionSourceManager $manager)
    {
        $forceCheck = $request->boolean('check');
        $target = $this->parseTarget((string) $request->query('target', ''));

        $shouldCheck = $forceCheck || $this->isStale();
        if ($shouldCheck) {
            try {
                $manager->checkUpdates();
            } catch (\Throwable $e) {
                // チェック失敗は致命的ではない（既存 last_version_check / available_version は残るので画面は表示できる）
                report($e);
            }
        }

        $plugins = Plugin::query()
            ->whereNotNull('available_version')
            ->orderBy('slug')
            ->get(['id', 'slug', 'name', 'version', 'available_version', 'directory'])
            ->map(fn (Plugin $p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'currentVersion' => $p->version,
                'availableVersion' => $p->available_version,
                'directory' => $p->directory,
                'preselected' => $target['type'] === 'plugin' && $target['slug'] === $p->slug,
            ])
            ->values()
            ->all();

        $themes = Theme::query()
            ->whereNotNull('available_version')
            ->orderBy('slug')
            ->get(['id', 'slug', 'name', 'version', 'available_version', 'directory'])
            ->map(fn (Theme $t) => [
                'id' => $t->id,
                'slug' => $t->slug,
                'name' => $t->name,
                'currentVersion' => $t->version,
                'availableVersion' => $t->available_version,
                'directory' => $t->directory,
                'preselected' => $target['type'] === 'theme' && $target['slug'] === $t->slug,
            ])
            ->values()
            ->all();

        // コアセクションのプレースホルダ（Phase 2 別セッションで実装される予定）
        $core = [
            'available' => false,
            'current_version' => config('app.version', null),
            'available_version' => null,
            'message_key' => 'admin/settings/systems/updates.core.not_implemented',
        ];

        $lastCheckedAt = $this->getLastCheckedAt();

        $this->viewParams['heading'] = __('admin/settings/systems/updates.heading');
        $this->viewParams['plugins'] = $plugins;
        $this->viewParams['themes'] = $themes;
        $this->viewParams['core'] = $core;
        $this->viewParams['lastCheckedAt'] = $lastCheckedAt;
        $this->viewParams['lastCheckedAtFormatted'] = $lastCheckedAt?->format('Y/m/d H:i');
        $this->viewParams['totalCount'] = count($plugins) + count($themes);

        return view('admin::settings.systems.updates.index', $this->viewParams);
    }

    /**
     * 強制チェック → 同ページへリダイレクト。
     */
    public function check(ExtensionSourceManager $manager)
    {
        try {
            $manager->checkUpdates();

            return redirect()->route('admin.settings.systems.updates.index')
                ->with('success', __('admin/settings/systems/updates.messages.check_done'));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.settings.systems.updates.index')
                ->with('error', __('admin/settings/systems/updates.messages.check_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * 選択された対象を順次更新。
     *
     * Form data:
     *   plugins[] = id (selected plugin IDs)
     *   themes[]  = id (selected theme IDs)
     */
    public function apply(Request $request)
    {
        $request->validate([
            'plugins' => 'array',
            'plugins.*' => 'integer',
            'themes' => 'array',
            'themes.*' => 'integer',
        ]);

        $pluginIds = $request->input('plugins', []);
        $themeIds = $request->input('themes', []);

        if (empty($pluginIds) && empty($themeIds)) {
            return redirect()->route('admin.settings.systems.updates.index')
                ->with('info', __('admin/settings/systems/updates.messages.no_selection'));
        }

        $succeeded = 0;
        $failed = 0;

        // プラグイン更新（既存 dls:plugin:update CLI に委譲）
        foreach ($pluginIds as $id) {
            $plugin = Plugin::query()->find($id);
            if (! $plugin || ! $plugin->hasUpdateAvailable()) {
                continue;
            }
            $code = Artisan::call('dls:plugin:update', [
                'slug' => $plugin->slug,
                '--force' => true,
            ]);
            $code === 0 ? $succeeded++ : $failed++;
        }

        // テーマ更新
        foreach ($themeIds as $id) {
            $theme = Theme::query()->find($id);
            if (! $theme || ! $theme->hasUpdateAvailable()) {
                continue;
            }
            $code = Artisan::call('dls:theme:update', [
                'slug' => $theme->slug,
                '--force' => true,
            ]);
            $code === 0 ? $succeeded++ : $failed++;
        }

        $total = $succeeded + $failed;
        $summary = __('admin/settings/systems/updates.messages.apply_summary', [
            'total' => $total,
            'succeeded' => $succeeded,
            'failed' => $failed,
        ]);

        return redirect()->route('admin.settings.systems.updates.index')
            ->with($failed === 0 ? 'success' : 'error', $summary);
    }

    /**
     * `?target=plugin:slug` 形式のクエリを解析。
     *
     * @return array{type: ?string, slug: ?string}
     */
    protected function parseTarget(string $target): array
    {
        if ($target === '') {
            return ['type' => null, 'slug' => null];
        }
        if (! str_contains($target, ':')) {
            return ['type' => null, 'slug' => null];
        }
        [$type, $slug] = explode(':', $target, 2);
        if (! in_array($type, ['plugin', 'theme'], true) || $slug === '') {
            return ['type' => null, 'slug' => null];
        }

        return ['type' => $type, 'slug' => $slug];
    }

    /**
     * 全インストール済み拡張機能の最終チェック時刻のうち最も古いもの。
     */
    protected function getLastCheckedAt(): ?Carbon
    {
        $candidates = array_filter([
            Plugin::query()->min('last_version_check'),
            Theme::query()->min('last_version_check'),
        ]);

        if (empty($candidates)) {
            return null;
        }

        return Carbon::parse(min($candidates));
    }

    /**
     * 自動チェック発動の判定（最終チェックが古いまたは未実施）。
     */
    protected function isStale(): bool
    {
        $last = $this->getLastCheckedAt();
        if ($last === null) {
            return true;
        }

        return $last->lt(now()->subSeconds(self::STALE_THRESHOLD_SECONDS));
    }
}
