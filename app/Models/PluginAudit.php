<?php

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
        'audited_at',
    ];

    protected $casts = [
        'has_mismatches' => 'boolean',
        'mismatches' => 'array',
        'risk_reasons' => 'array',
        'audited_at' => 'datetime',
    ];

    /**
     * プラグインスラッグで監査結果を取得
     */
    public static function getBySlug(string $slug): ?self
    {
        return self::where('plugin_slug', $slug)->first();
    }

    /**
     * 監査結果を保存または更新
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
            'audited_at' => $this->audited_at?->toDateTimeString(),
        ];
    }
}
