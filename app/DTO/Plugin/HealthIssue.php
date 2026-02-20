<?php

namespace App\DTO\Plugin;

use JsonSerializable;

/**
 * 健全性チェックで検出された問題を表すDTO
 */
final readonly class HealthIssue implements JsonSerializable
{
    /**
     * @param  string  $type  問題の種別（getDeductionRules()のキーに対応）
     * @param  string  $severity  重要度（critical, warning, info）
     * @param  string  $description  問題の説明
     * @param  array<array{file?: string, line?: int, match?: string}>  $evidence  検出根拠
     * @param  int  $deduction  減点値（負の整数）
     */
    public function __construct(
        public string $type,
        public string $severity,
        public string $description,
        public array $evidence = [],
        public int $deduction = 0,
    ) {}

    /**
     * 致命的な問題かどうか
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'severity' => $this->severity,
            'description' => $this->description,
            'evidence' => $this->evidence,
            'deduction' => $this->deduction,
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
