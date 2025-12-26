<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeAudit extends Model
{
    protected $fillable = [
        'theme_slug',
        'has_mismatches',
        'mismatches',
        'matches_count',
        'total_checked',
        'risk_level',
        'risk_reasons',
        'signature_status',
        'signature_signer',
        'csp_status',
        'csp_requires_inline_js',
        'csp_requires_inline_css',
        'audited_at',
    ];

    protected $casts = [
        'has_mismatches' => 'boolean',
        'mismatches' => 'array',
        'risk_reasons' => 'array',
        'csp_requires_inline_js' => 'boolean',
        'csp_requires_inline_css' => 'boolean',
        'audited_at' => 'datetime',
    ];

    /**
     * テーマスラッグで監査結果を取得
     */
    public static function getBySlug(string $slug): ?self
    {
        return self::where('theme_slug', $slug)->first();
    }

    /**
     * 監査結果を保存または更新
     */
    public static function saveAuditResult(string $slug, array $result): self
    {
        return self::updateOrCreate(
            ['theme_slug' => $slug],
            [
                'has_mismatches' => $result['has_mismatches'] ?? false,
                'mismatches' => $result['mismatches'] ?? [],
                'matches_count' => $result['matches_count'] ?? 0,
                'total_checked' => $result['total_checked'] ?? 0,
                'risk_level' => $result['risk_level'] ?? null,
                'risk_reasons' => $result['risk_reasons'] ?? [],
                'signature_status' => $result['signature_status'] ?? null,
                'signature_signer' => $result['signature_signer'] ?? null,
                'csp_status' => $result['csp_status'] ?? null,
                'csp_requires_inline_js' => $result['csp_requires_inline_js'] ?? false,
                'csp_requires_inline_css' => $result['csp_requires_inline_css'] ?? false,
                'audited_at' => now(),
            ]
        );
    }

    /**
     * 監査結果を配列で取得
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
            'signature_status' => $this->signature_status,
            'signature_signer' => $this->signature_signer,
            'csp_status' => $this->csp_status,
            'csp_requires_inline_js' => $this->csp_requires_inline_js,
            'csp_requires_inline_css' => $this->csp_requires_inline_css,
            'audited_at' => $this->audited_at?->toDateTimeString(),
        ];
    }
}
