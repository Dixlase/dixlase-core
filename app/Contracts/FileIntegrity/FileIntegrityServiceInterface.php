<?php

namespace App\Contracts\FileIntegrity;

use App\DTO\FileIntegrity\ScanResultDTO;
use App\DTO\FileIntegrity\ScanTargetDTO;
use App\DTO\FileIntegrity\BaselineDTO;

/**
 * ファイル整合性チェックサービスの契約
 * 
 * コアおよびプラグインのファイル改ざん検知機能を提供します。
 * 
 * @package App\Contracts\FileIntegrity
 */
interface FileIntegrityServiceInterface
{
    /**
     * ベースラインを生成
     * 
     * @param ScanTargetDTO $target スキャン対象
     * @return BaselineDTO
     */
    public function generateBaseline(ScanTargetDTO $target): BaselineDTO;

    /**
     * ベースラインを保存
     * 
     * @param BaselineDTO $baseline ベースライン
     * @param string $filename ファイル名
     * @return bool
     */
    public function saveBaseline(BaselineDTO $baseline, string $filename = 'core_hashes.json'): bool;

    /**
     * ベースラインを読み込み
     * 
     * @param string $filename ファイル名
     * @return BaselineDTO|null
     */
    public function loadBaseline(string $filename = 'core_hashes.json'): ?BaselineDTO;

    /**
     * ファイル整合性スキャンを実行
     * 
     * @param ScanTargetDTO $target スキャン対象
     * @param string $trigger トリガー（manual, schedule, install, update）
     * @param string $initiatedByType 実行者タイプ（system, user）
     * @param int|null $initiatedById 実行者ID
     * @return ScanResultDTO
     */
    public function scan(
        ScanTargetDTO $target,
        string $trigger = 'manual',
        string $initiatedByType = 'system',
        ?int $initiatedById = null
    ): ScanResultDTO;

    /**
     * ベースラインが存在するか
     * 
     * @param string $filename ファイル名
     * @return bool
     */
    public function hasBaseline(string $filename = 'core_hashes.json'): bool;

    /**
     * ベースラインを再生成
     * 
     * @param ScanTargetDTO $target スキャン対象
     * @param string $trigger トリガー
     * @param string $initiatedByType 実行者タイプ
     * @param int|null $initiatedById 実行者ID
     * @return bool
     */
    public function regenerateBaseline(
        ScanTargetDTO $target,
        string $trigger = 'manual',
        string $initiatedByType = 'user',
        ?int $initiatedById = null
    ): bool;
}
