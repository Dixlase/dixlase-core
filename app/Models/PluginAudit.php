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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PluginAudit extends Model
{
    protected $fillable = [
        'plugin_slug',
        'has_mismatches',
        'mismatches',
        'matches_count',
        'total_checked',
        'risk_level',
        'risk_reasons',
        'health_score',
        'health_status',
        'health_issues',
        'owned_tables',
        'files_hash',
        'signature_status',
        'signature_signer',
        'csp_status',
        'csp_requires_inline_js',
        'csp_requires_inline_css',
        'csp_violations',
        'csp_summary',
        'audited_at',
    ];

    protected $casts = [
        'has_mismatches' => 'boolean',
        'mismatches' => 'array',
        'risk_reasons' => 'array',
        'health_score' => 'integer',
        'health_issues' => 'array',
        'owned_tables' => 'array',
        'csp_requires_inline_js' => 'boolean',
        'csp_requires_inline_css' => 'boolean',
        'csp_violations' => 'array',
        'csp_summary' => 'array',
        'audited_at' => 'datetime',
    ];

    /**
     * Get audit result by plugin slug
     */
    public static function getBySlug(string $slug): ?self
    {
        return self::where('plugin_slug', $slug)->first();
    }

    /**
     * Save or update audit result
     */
    public static function saveAuditResult(string $slug, array $result): self
    {
        return self::updateOrCreate(
            ['plugin_slug' => $slug],
            [
                'has_mismatches' => $result['has_mismatches'] ?? false,
                'mismatches' => $result['mismatches'] ?? [],
                'matches_count' => $result['matches_count'] ?? 0,
                'total_checked' => $result['total_checked'] ?? 0,
                'risk_level' => $result['risk_level'] ?? null,
                'risk_reasons' => $result['risk_reasons'] ?? [],
                'health_score' => $result['health_score'] ?? null,
                'health_status' => $result['health_status'] ?? null,
                'health_issues' => $result['health_issues'] ?? [],
                'owned_tables' => $result['owned_tables'] ?? [],
                'files_hash' => $result['files_hash'] ?? null,
                'signature_status' => $result['signature_status'] ?? null,
                'signature_signer' => $result['signature_signer'] ?? null,
                'csp_status' => $result['csp_status'] ?? null,
                'csp_requires_inline_js' => $result['csp_requires_inline_js'] ?? false,
                'csp_requires_inline_css' => $result['csp_requires_inline_css'] ?? false,
                'csp_violations' => $result['csp_violations'] ?? [],
                'csp_summary' => $result['csp_summary'] ?? [],
                'audited_at' => now(),
            ]
        );
    }

    /**
     * Convert scanner CSP status to JS-compatible normalized value
     *
     * Scanner: compatible, csp_ready, inline_required, inline_css_only, unknown
     * Normalized: compliant, inline_required, inline_css_only, unknown
     */
    protected function normalizedCspStatus(): string
    {
        return match ($this->csp_status) {
            'compatible', 'csp_ready' => 'compliant',
            default => $this->csp_status ?? 'unknown',
        };
    }

    /**
     * Get audit result as array
     */
    public function toAuditArray(): array
    {
        return [
            'has_mismatches' => $this->has_mismatches,
            'mismatches' => $this->mismatches ?? [],
            'matches_count' => $this->matches_count,
            'total_checked' => $this->total_checked,
            'risk_level' => $this->risk_level,
            'risk_reasons' => $this->risk_reasons ?? [],
            'health_score' => $this->health_score,
            'health_status' => $this->health_status,
            'health_issues' => $this->health_issues ?? [],
            'owned_tables' => $this->owned_tables ?? [],
            'files_hash' => $this->files_hash,
            'signature_status' => $this->signature_status,
            'signature_signer' => $this->signature_signer,
            'csp_status' => $this->normalizedCspStatus(),
            'csp_requires_inline_js' => $this->csp_requires_inline_js,
            'csp_requires_inline_css' => $this->csp_requires_inline_css,
            'csp_violations' => $this->csp_violations ?? [],
            'csp_summary' => $this->csp_summary ?? [],
            'audited_at' => $this->audited_at?->toDateTimeString(),
        ];
    }
}
