<?php
/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

namespace App\Services\Csp;

use App\Models\Plugin;
use App\Models\Theme;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * CSP Extension Loader
 * 
 * プラグイン・テーマのplugin.json/theme.jsonからCSP設定を読み取り、
 * CspPolicyRegistryに自動登録するサービス。
 * 
 * 優先順位:
 * 1. 拒否ドメイン（管理者設定） - 最優先でブロック
 * 2. plugin.json/theme.jsonの宣言 - 基本的に自動許可
 * 3. 信頼済みドメイン（管理者設定） - 追加許可
 */
class CspExtensionLoader
{
    protected CspPolicyRegistry $registry;

    /**
     * CSPディレクティブのマッピング
     * plugin.json/theme.jsonのキー => CSPディレクティブ名
     */
    protected array $directiveMapping = [
        'scripts' => 'script-src',
        'styles' => 'style-src',
        'fonts' => 'font-src',
        'images' => 'img-src',
        'connect' => 'connect-src',
        'media' => 'media-src',
        'frames' => 'frame-src',
        'workers' => 'worker-src',
    ];

    public function __construct(CspPolicyRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * 有効なプラグイン・テーマからCSP設定を読み込み、レジストリに登録
     */
    public function loadAll(): void
    {
        $this->loadPlugins();
        $this->loadThemes();
    }

    /**
     * 有効なプラグインからCSP設定を読み込み
     */
    public function loadPlugins(): void
    {
        try {
            $plugins = Plugin::whereNotNull('enabled_at')->get();
            
            foreach ($plugins as $plugin) {
                $this->loadPlugin($plugin->slug);
            }
        } catch (\Exception $e) {
            // データベース未設定時は無視
            Log::debug('CSP: Could not load plugins - ' . $e->getMessage());
        }
    }

    /**
     * 有効なテーマからCSP設定を読み込み
     */
    public function loadThemes(): void
    {
        try {
            // 現在有効なテーマを取得
            $activeThemeId = \DB::table('theme_settings')
                ->where('key', 'enabled_theme_id')
                ->value('value');

            if ($activeThemeId) {
                $theme = Theme::find($activeThemeId);
                if ($theme) {
                    $this->loadTheme($theme->slug);
                }
            }
        } catch (\Exception $e) {
            // データベース未設定時は無視
            Log::debug('CSP: Could not load themes - ' . $e->getMessage());
        }
    }

    /**
     * 特定のプラグインからCSP設定を読み込み
     */
    public function loadPlugin(string $slug): array
    {
        $pluginPath = base_path('plugins/' . $slug);
        $jsonPath = $pluginPath . '/plugin.json';

        return $this->loadFromJson($jsonPath, 'plugin', $slug);
    }

    /**
     * 特定のテーマからCSP設定を読み込み
     */
    public function loadTheme(string $slug): array
    {
        $themePath = base_path('themes/' . $slug);
        $jsonPath = $themePath . '/theme.json';

        return $this->loadFromJson($jsonPath, 'theme', $slug);
    }

    /**
     * JSONファイルからCSP設定を読み込み、レジストリに登録
     */
    protected function loadFromJson(string $jsonPath, string $type, string $slug): array
    {
        if (!File::exists($jsonPath)) {
            return [];
        }

        $cacheKey = "csp_extension_{$type}_{$slug}";
        
        // キャッシュから取得を試みる
        $directives = Cache::remember($cacheKey, 3600, function () use ($jsonPath) {
            $content = File::get($jsonPath);
            $json = json_decode($content, true);

            if (!$json || !isset($json['csp'])) {
                return [];
            }

            return $this->parseCspConfig($json['csp']);
        });

        if (!empty($directives)) {
            $this->registry->addDirectives($directives, "{$type}:{$slug}");
            Log::debug("CSP: Loaded directives from {$type}:{$slug}", $directives);
        }

        return $directives;
    }

    /**
     * CSP設定をパースしてディレクティブ配列に変換
     */
    protected function parseCspConfig(array $cspConfig): array
    {
        $directives = [];

        // external_domains セクションを処理
        if (isset($cspConfig['external_domains']) && is_array($cspConfig['external_domains'])) {
            foreach ($cspConfig['external_domains'] as $key => $domains) {
                if (!is_array($domains)) {
                    continue;
                }

                $directiveName = $this->directiveMapping[$key] ?? null;
                if ($directiveName) {
                    $directives[$directiveName] = array_merge(
                        $directives[$directiveName] ?? [],
                        $this->normalizeDomains($domains)
                    );
                }
            }
        }

        // 後方互換性: 旧形式のキーもサポート
        foreach ($this->directiveMapping as $jsonKey => $directiveName) {
            if (isset($cspConfig[$jsonKey]) && is_array($cspConfig[$jsonKey])) {
                $directives[$directiveName] = array_merge(
                    $directives[$directiveName] ?? [],
                    $this->normalizeDomains($cspConfig[$jsonKey])
                );
            }
        }

        return $directives;
    }

    /**
     * ドメイン配列を正規化
     */
    protected function normalizeDomains(array $domains): array
    {
        return array_map(function ($domain) {
            // プロトコルがない場合はhttpsを追加
            if (!preg_match('/^https?:\/\//', $domain)) {
                return 'https://' . $domain;
            }
            return $domain;
        }, $domains);
    }

    /**
     * 拡張機能のCSP設定を取得（UIでの表示用）
     */
    public function getExtensionCspInfo(string $type, string $slug): array
    {
        $path = $type === 'plugin' 
            ? base_path("plugins/{$slug}/plugin.json")
            : base_path("themes/{$slug}/theme.json");

        if (!File::exists($path)) {
            return [
                'has_csp' => false,
                'domains' => [],
            ];
        }

        $content = File::get($path);
        $json = json_decode($content, true);

        if (!$json || !isset($json['csp'])) {
            return [
                'has_csp' => false,
                'domains' => [],
            ];
        }

        $directives = $this->parseCspConfig($json['csp']);
        $allDomains = [];

        foreach ($directives as $directive => $domains) {
            foreach ($domains as $domain) {
                $allDomains[$domain] = $allDomains[$domain] ?? [];
                $allDomains[$domain][] = $directive;
            }
        }

        return [
            'has_csp' => true,
            'domains' => $allDomains,
            'directives' => $directives,
        ];
    }

    /**
     * 拡張機能のCSPドメインをブロックリストと照合
     * 
     * @param string $type 'plugin' または 'theme'
     * @param string $slug スラッグ
     * @return array ブロックリストにマッチしたドメインの情報
     */
    public function checkAgainstBlocklist(string $type, string $slug): array
    {
        $blocklistService = app(CspBlocklistService::class);
        
        // ブロックリスト照合が無効な場合はスキップ
        if (!$blocklistService->isBlocklistCheckEnabled()) {
            return [];
        }

        // 拡張機能のCSP情報を取得
        $cspInfo = $this->getExtensionCspInfo($type, $slug);
        
        if (!$cspInfo['has_csp'] || empty($cspInfo['domains'])) {
            return [];
        }

        // ドメインリストを抽出
        $domains = array_keys($cspInfo['domains']);
        
        // ブロックリストと照合
        return $blocklistService->checkDomainsAgainstBlocklist($domains);
    }

    /**
     * 複数の拡張機能のCSPドメインをブロックリストと照合
     * 
     * @param array $extensions [['type' => 'plugin', 'slug' => 'xxx'], ...]
     * @return array 拡張機能ごとのマッチ結果
     */
    public function checkMultipleAgainstBlocklist(array $extensions): array
    {
        $results = [];
        
        foreach ($extensions as $ext) {
            $type = $ext['type'] ?? '';
            $slug = $ext['slug'] ?? '';
            
            if ($type && $slug) {
                $matches = $this->checkAgainstBlocklist($type, $slug);
                if (!empty($matches)) {
                    $results[] = [
                        'type' => $type,
                        'slug' => $slug,
                        'matches' => $matches,
                    ];
                }
            }
        }
        
        return $results;
    }

    /**
     * 拡張機能がインラインJSを必要とするかチェック
     * 
     * @param string $type 'plugin' または 'theme'
     * @param string $slug スラッグ
     * @return bool
     */
    public function requiresInlineJs(string $type, string $slug): bool
    {
        $path = $type === 'plugin' 
            ? base_path("plugins/{$slug}/plugin.json")
            : base_path("themes/{$slug}/theme.json");

        if (!File::exists($path)) {
            return false;
        }

        try {
            $content = File::get($path);
            $json = json_decode($content, true);
            
            return (bool) ($json['requires_inline_js'] ?? false);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 拡張機能のCSP対応状態を取得
     * 
     * @param string $type 'plugin' または 'theme'
     * @param string $slug スラッグ
     * @return array CSP対応情報
     */
    public function getCspCompatibility(string $type, string $slug): array
    {
        $path = $type === 'plugin' 
            ? base_path("plugins/{$slug}/plugin.json")
            : base_path("themes/{$slug}/theme.json");

        if (!File::exists($path)) {
            return [
                'status' => 'unknown',
                'requires_inline_js' => false,
                'has_csp_config' => false,
                'csp_ready' => false,
            ];
        }

        try {
            $content = File::get($path);
            $json = json_decode($content, true);
            
            $requiresInlineJs = (bool) ($json['requires_inline_js'] ?? false);
            $hasCspConfig = isset($json['csp']);
            
            // CSP Ready = インラインJS不要 かつ CSP設定がある（または外部リソースを使わない）
            $cspReady = !$requiresInlineJs;
            
            if ($requiresInlineJs) {
                $status = 'inline_required';
            } elseif ($hasCspConfig) {
                $status = 'csp_ready';
            } else {
                $status = 'compatible';
            }
            
            return [
                'status' => $status,
                'requires_inline_js' => $requiresInlineJs,
                'has_csp_config' => $hasCspConfig,
                'csp_ready' => $cspReady,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unknown',
                'requires_inline_js' => false,
                'has_csp_config' => false,
                'csp_ready' => false,
            ];
        }
    }

    /**
     * 厳格モードで拡張機能が有効化可能かチェック
     * 
     * @param string $type 'plugin' または 'theme'
     * @param string $slug スラッグ
     * @return array ['allowed' => bool, 'reason' => string|null]
     */
    public function canEnableInStrictMode(string $type, string $slug): array
    {
        $compatibility = $this->getCspCompatibility($type, $slug);
        
        if ($compatibility['requires_inline_js']) {
            return [
                'allowed' => false,
                'reason' => 'requires_inline_js',
            ];
        }
        
        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * キャッシュをクリア
     */
    public function clearCache(?string $type = null, ?string $slug = null): void
    {
        if ($type && $slug) {
            Cache::forget("csp_extension_{$type}_{$slug}");
        } else {
            // 全キャッシュクリアは個別に行う必要がある
            // プラグイン・テーマの一覧を取得してクリア
            try {
                $plugins = Plugin::all();
                foreach ($plugins as $plugin) {
                    Cache::forget("csp_extension_plugin_{$plugin->slug}");
                }

                $themes = Theme::all();
                foreach ($themes as $theme) {
                    Cache::forget("csp_extension_theme_{$theme->slug}");
                }
            } catch (\Exception $e) {
                // データベース未設定時は無視
            }
        }
    }

    /**
     * plugin.json/theme.jsonにCSPセクションを追加・更新
     */
    public function updateCspConfig(string $type, string $slug, array $cspConfig): bool
    {
        $path = $type === 'plugin' 
            ? base_path("plugins/{$slug}/plugin.json")
            : base_path("themes/{$slug}/theme.json");

        if (!File::exists($path)) {
            return false;
        }

        $content = File::get($path);
        $json = json_decode($content, true);

        if (!$json) {
            return false;
        }

        $json['csp'] = $cspConfig;

        $result = File::put($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        if ($result) {
            $this->clearCache($type, $slug);
        }

        return $result !== false;
    }
}
