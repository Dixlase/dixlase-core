<?php

namespace App\DTO\Plugin;

use App\Enums\PluginHealthStatus;
use JsonSerializable;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * 健全性スコア計算結果DTO
 */
final readonly class HealthScoreResult implements JsonSerializable
{
    /**
     * @param  int  $score  健全性スコア（0-100）
     * @param  PluginHealthStatus  $status  健全性ステータス
     * @param  array<HealthIssue>  $issues  検出された問題のリスト
     * @param  bool  $hasCriticalIssue  致命的な問題があるか
     */
    public function __construct(
        public int $score,
        public PluginHealthStatus $status,
        public array $issues = [],
        public bool $hasCriticalIssue = false,
    ) {}

    /**
     * 健全かどうか
     */
    public function isHealthy(): bool
    {
        return $this->status === PluginHealthStatus::Healthy;
    }

    /**
     * 有効化可能かどうかの簡易チェック
     */
    public function needsAttention(): bool
    {
        return $this->status === PluginHealthStatus::NeedsAttention;
    }

    /**
     * 問題数を取得
     */
    public function issueCount(): int
    {
        return count($this->issues);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'score' => $this->score,
            'status' => $this->status->value,
            'issues' => array_map(fn (HealthIssue $i) => $i->toArray(), $this->issues),
            'has_critical_issue' => $this->hasCriticalIssue,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
