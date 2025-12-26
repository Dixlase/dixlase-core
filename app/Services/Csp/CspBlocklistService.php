<?php
/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 */

namespace App\Services\Csp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CSP Blocklist Service
 * 
 * 外部のブロックリストソースから悪意あるドメインや
 * トラッキングドメインを取得するサービス。
 * 
 * ブロックリストのソースはconfig/csp.phpで設定可能。
 */
class CspBlocklistService
{
    /**
     * 利用可能なブロックリストソース（configから読み込み）
     */
    protected array $sources;

    /**
     * キャッシュ時間（秒）
     */
    protected int $cacheTtl;

    public function __construct()
    {
        $this->sources = config('csp.blocklist_sources', []);
        $this->cacheTtl = config('csp.blocklist_cache_ttl', 86400);
    }

    /**
     * 利用可能なブロックリストカテゴリを取得
     */
    public function getAvailableCategories(): array
    {
        $locale = app()->getLocale();
        $categories = [];
        
        foreach ($this->sources as $key => $source) {
            // 言語に応じた名前と説明を取得（フォールバック付き）
            $name = $locale === 'en' && isset($source['name_en']) 
                ? $source['name_en'] 
                : ($source['name'] ?? $key);
            $description = $locale === 'en' && isset($source['description_en']) 
                ? $source['description_en'] 
                : ($source['description'] ?? '');
            
            $categories[$key] = [
                'name' => $name,
                'description' => $description,
            ];
        }
        return $categories;
    }

    /**
     * 指定カテゴリのブロックリストを取得
     */
    public function getBlocklist(string $category, bool $forceRefresh = false): array
    {
        if (!isset($this->sources[$category])) {
            return [];
        }

        $cacheKey = "csp_blocklist_{$category}";

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $domains = [];
        $source = $this->sources[$category];

        foreach ($source['lists'] as $url) {
            try {
                $listDomains = $this->fetchAndParseList($url);
                $domains = array_merge($domains, $listDomains);
            } catch (\Exception $e) {
                Log::warning("CSP Blocklist: Failed to fetch {$url}", ['error' => $e->getMessage()]);
            }
        }

        $domains = array_unique($domains);
        $domains = array_values(array_filter($domains));

        // キャッシュに保存
        Cache::put($cacheKey, $domains, $this->cacheTtl);

        return $domains;
    }

    /**
     * 全カテゴリのブロックリストを取得
     */
    public function getAllBlocklists(bool $forceRefresh = false): array
    {
        $all = [];
        foreach (array_keys($this->sources) as $category) {
            $all[$category] = $this->getBlocklist($category, $forceRefresh);
        }
        return $all;
    }

    /**
     * 選択されたカテゴリのドメインを結合して取得
     */
    public function getSelectedBlocklists(array $categories, bool $forceRefresh = false): array
    {
        $domains = [];
        foreach ($categories as $category) {
            $domains = array_merge($domains, $this->getBlocklist($category, $forceRefresh));
        }
        return array_unique($domains);
    }

    /**
     * 指定されたドメインがブロックリストに含まれているかチェック
     * 
     * @param array $domainsToCheck チェックするドメインの配列
     * @param array|null $categories チェックするカテゴリ（nullの場合は有効な全カテゴリ）
     * @return array マッチしたドメインとカテゴリの情報
     */
    public function checkDomainsAgainstBlocklist(array $domainsToCheck, ?array $categories = null): array
    {
        // 有効なカテゴリを取得
        $enabledCategories = $categories ?? $this->getEnabledCategories();
        
        if (empty($enabledCategories)) {
            return [];
        }

        $matches = [];
        
        foreach ($enabledCategories as $category) {
            $blocklist = $this->getBlocklist($category);
            
            foreach ($domainsToCheck as $domain) {
                // ドメインを正規化
                $normalizedDomain = $this->normalizeDomain($domain);
                
                if (in_array($normalizedDomain, $blocklist, true)) {
                    $matches[] = [
                        'domain' => $domain,
                        'category' => $category,
                        'category_name' => $this->getCategoryName($category),
                    ];
                }
            }
        }

        return $matches;
    }

    /**
     * 有効なブロックリストカテゴリを取得（セキュリティ設定から）
     */
    public function getEnabledCategories(): array
    {
        try {
            $enabled = \App\Models\SecuritySetting::get('csp_blocklist_enabled_categories', '');
            if (empty($enabled)) {
                return [];
            }
            return array_filter(explode(',', $enabled));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * ドメインを正規化（URLからホスト部分を抽出）
     */
    protected function normalizeDomain(string $domain): string
    {
        // URLの場合はホスト部分を抽出
        if (preg_match('/^https?:\/\//', $domain)) {
            $parsed = parse_url($domain);
            $domain = $parsed['host'] ?? $domain;
        }
        
        // www.を除去
        $domain = preg_replace('/^www\./', '', $domain);
        
        return strtolower(trim($domain));
    }

    /**
     * カテゴリ名を取得
     */
    protected function getCategoryName(string $category): string
    {
        $locale = app()->getLocale();
        $source = $this->sources[$category] ?? null;
        
        if (!$source) {
            return $category;
        }

        return $locale === 'en' && isset($source['name_en']) 
            ? $source['name_en'] 
            : ($source['name'] ?? $category);
    }

    /**
     * ブロックリスト照合が有効かどうか
     */
    public function isBlocklistCheckEnabled(): bool
    {
        try {
            return (bool) \App\Models\SecuritySetting::get('csp_blocklist_check_enabled', false);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * ブロックリスト検出時のアクションを取得
     * 
     * @return string 'warn' または 'block'
     */
    public function getBlocklistAction(): string
    {
        try {
            $actionValue = \App\Models\SecuritySetting::get('csp_blocklist_action', (string) \App\Enums\CspBlocklistAction::default()->value);
            $action = \App\Enums\CspBlocklistAction::fromValue($actionValue);
            return $action ? $action->toString() : \App\Enums\CspBlocklistAction::default()->toString();
        } catch (\Exception $e) {
            return \App\Enums\CspBlocklistAction::default()->toString();
        }
    }

    /**
     * ブロックリスト検出時にブロックするかどうか
     */
    public function shouldBlockOnMatch(): bool
    {
        return $this->getBlocklistAction() === 'block';
    }

    /**
     * リストをフェッチしてパース
     */
    protected function fetchAndParseList(string $url): array
    {
        $response = Http::timeout(30)->get($url);

        if (!$response->successful()) {
            throw new \Exception("HTTP {$response->status()}");
        }

        $content = $response->body();
        return $this->parseHostsFile($content);
    }

    /**
     * hostsファイル形式をパース
     */
    protected function parseHostsFile(string $content): array
    {
        $domains = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            // コメント行をスキップ
            if (empty($line) || str_starts_with($line, '#') || str_starts_with($line, '!')) {
                continue;
            }

            // hosts形式: 0.0.0.0 domain.com または 127.0.0.1 domain.com
            if (preg_match('/^(?:0\.0\.0\.0|127\.0\.0\.1)\s+(.+)$/i', $line, $matches)) {
                $domain = trim($matches[1]);
                // コメント部分を除去
                $domain = preg_replace('/#.*$/', '', $domain);
                $domain = trim($domain);
                
                if ($this->isValidDomain($domain)) {
                    $domains[] = $domain;
                }
                continue;
            }

            // ドメインのみの形式
            if ($this->isValidDomain($line)) {
                $domains[] = $line;
            }
        }

        return $domains;
    }

    /**
     * 有効なドメイン名かチェック
     */
    protected function isValidDomain(string $domain): bool
    {
        // 空、localhost、IPアドレスを除外
        if (empty($domain) || $domain === 'localhost') {
            return false;
        }

        // IPアドレスを除外
        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            return false;
        }

        // 基本的なドメイン形式チェック
        if (!preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]*[a-z0-9])?)*$/i', $domain)) {
            return false;
        }

        return true;
    }

    /**
     * ブロックリストの統計情報を取得
     */
    public function getStats(): array
    {
        $stats = [];
        foreach (array_keys($this->sources) as $category) {
            $cacheKey = "csp_blocklist_{$category}";
            $cached = Cache::get($cacheKey);
            
            $stats[$category] = [
                'name' => $this->sources[$category]['name'],
                'count' => $cached ? count($cached) : 0,
                'cached' => $cached !== null,
                'last_updated' => $cached ? Cache::get("{$cacheKey}_updated") : null,
            ];
        }
        return $stats;
    }

    /**
     * キャッシュをクリア
     */
    public function clearCache(?string $category = null): void
    {
        if ($category) {
            Cache::forget("csp_blocklist_{$category}");
            Cache::forget("csp_blocklist_{$category}_updated");
        } else {
            foreach (array_keys($this->sources) as $cat) {
                Cache::forget("csp_blocklist_{$cat}");
                Cache::forget("csp_blocklist_{$cat}_updated");
            }
        }
    }
}
