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

namespace App\Services\Csp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * CSP Blocklist Service
 *
 * Service to retrieve malicious domains and
 * tracking domains from external blocklist sources
 *
 * Blocklist sources can be configured in config/csp.php
 */
class CspBlocklistService
{
    /**
     * Available blocklist sources (loaded from config)
     */
    protected array $sources;

    /**
     * Cache time (seconds)
     */
    protected int $cacheTtl;

    public function __construct()
    {
        $this->sources = config('csp.blocklist_sources', []);
        $this->cacheTtl = config('csp.blocklist_cache_ttl', 86400);
    }

    /**
     * Retrieve available blocklist categories
     */
    public function getAvailableCategories(): array
    {
        $locale = app()->getLocale();
        $categories = [];

        foreach ($this->sources as $key => $source) {
            // Get name and description based on language (with fallback)
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
     * Retrieve blocklist for specified category
     */
    public function getBlocklist(string $category, bool $forceRefresh = false): array
    {
        if (! isset($this->sources[$category])) {
            return [];
        }

        $cacheKey = "csp_blocklist_{$category}";

        if (! $forceRefresh && Cache::has($cacheKey)) {
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

        // Save to cache
        Cache::put($cacheKey, $domains, $this->cacheTtl);

        return $domains;
    }

    /**
     * Retrieve blocklists for all categories
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
     * Retrieve combined domains from selected categories
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
     * Check if specified domains are included in the blocklist
     *
     * @param  array  $domainsToCheck  Array of domains to check
     * @param  array|null  $categories  Categories to check (all active categories if null)
     * @return array Information about matched domains and categories
     */
    public function checkDomainsAgainstBlocklist(array $domainsToCheck, ?array $categories = null): array
    {
        // Retrieve active categories
        $enabledCategories = $categories ?? $this->getEnabledCategories();

        if (empty($enabledCategories)) {
            return [];
        }

        $matches = [];

        foreach ($enabledCategories as $category) {
            $blocklist = $this->getBlocklist($category);

            foreach ($domainsToCheck as $domain) {
                // Normalize domain
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
     * Retrieve active blocklist categories (from security settings)
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
     * Normalize domain (extract host part from URL)
     */
    protected function normalizeDomain(string $domain): string
    {
        // Extract host part if URL
        if (preg_match('/^https?:\/\//', $domain)) {
            $parsed = parse_url($domain);
            $domain = $parsed['host'] ?? $domain;
        }

        // Remove www.
        $domain = preg_replace('/^www\./', '', $domain);

        return strtolower(trim($domain));
    }

    /**
     * Get category name
     */
    protected function getCategoryName(string $category): string
    {
        $locale = app()->getLocale();
        $source = $this->sources[$category] ?? null;

        if (! $source) {
            return $category;
        }

        return $locale === 'en' && isset($source['name_en'])
            ? $source['name_en']
            : ($source['name'] ?? $category);
    }

    /**
     * Whether blocklist matching is enabled
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
     * Get action when blocklist is detected
     *
     * @return string 'warn' or 'block'
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
     * Whether to block when blocklist is detected
     */
    public function shouldBlockOnMatch(): bool
    {
        return $this->getBlocklistAction() === 'block';
    }

    /**
     * Fetch and parse list
     */
    protected function fetchAndParseList(string $url): array
    {
        $response = Http::timeout(30)->get($url);

        if (! $response->successful()) {
            throw new \Exception("HTTP {$response->status()}");
        }

        $content = $response->body();

        return $this->parseHostsFile($content);
    }

    /**
     * Parse hosts file format
     */
    protected function parseHostsFile(string $content): array
    {
        $domains = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comment lines
            if (empty($line) || str_starts_with($line, '#') || str_starts_with($line, '!')) {
                continue;
            }

            // hosts format: 0.0.0.0 domain.com or 127.0.0.1 domain.com
            if (preg_match('/^(?:0\.0\.0\.0|127\.0\.0\.1)\s+(.+)$/i', $line, $matches)) {
                $domain = trim($matches[1]);
                // Remove comment part
                $domain = preg_replace('/#.*$/', '', $domain);
                $domain = trim($domain);

                if ($this->isValidDomain($domain)) {
                    $domains[] = $domain;
                }

                continue;
            }

            // Domain-only format
            if ($this->isValidDomain($line)) {
                $domains[] = $line;
            }
        }

        return $domains;
    }

    /**
     * Check if valid domain name
     */
    protected function isValidDomain(string $domain): bool
    {
        // Exclude empty, localhost, IP addresses
        if (empty($domain) || $domain === 'localhost') {
            return false;
        }

        // Exclude IP addresses
        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            return false;
        }

        // Basic domain format check
        if (! preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]*[a-z0-9])?)*$/i', $domain)) {
            return false;
        }

        return true;
    }

    /**
     * Get blocklist statistics
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
     * Clear cache
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
