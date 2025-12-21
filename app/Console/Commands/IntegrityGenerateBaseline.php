<?php

namespace App\Console\Commands;

use App\Models\FileIntegrityAudit;
use App\Services\FileIntegrityService;
use Illuminate\Console\Command;

class IntegrityGenerateBaseline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:integrity:generate-baseline
                            {--force : 既存のベースラインを上書きする}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'コアファイルの整合性チェック用ベースラインを生成します';

    /**
     * Execute the console command.
     */
    public function handle(FileIntegrityService $service): int
    {
        $this->info(__('command.integrity.generating_baseline'));

        // 既存のベースラインをチェック
        if ($service->hasBaseline() && !$this->option('force')) {
            $meta = $service->getBaselineMeta();
            $this->warn(__('command.integrity.baseline_exists', [
                'date' => $meta['generated_at'] ?? 'unknown',
                'version' => $meta['app_version'] ?? 'unknown',
            ]));

            if (!$this->confirm(__('command.integrity.overwrite_confirm'))) {
                $this->info(__('command.integrity.cancelled'));
                return Command::SUCCESS;
            }
        }

        $this->output->write(__('command.integrity.scanning_files'));

        // ベースラインを生成
        $baseline = $service->generateCoreBaseline();

        $this->info(' ' . __('common.done'));

        // 保存
        $this->output->write(__('command.integrity.saving_baseline'));

        if ($service->saveBaselineArray($baseline)) {
            $this->info(' ' . __('common.done'));

            // 監査ログを記録
            FileIntegrityAudit::create([
                'scope' => FileIntegrityAudit::SCOPE_CORE,
                'trigger' => FileIntegrityAudit::TRIGGER_MANUAL,
                'initiated_by_type' => FileIntegrityAudit::INITIATED_BY_CLI,
                'status' => FileIntegrityAudit::STATUS_OK,
                'hash_algo' => 'sha256',
                'baseline_version' => $baseline['meta']['app_version'] ?? null,
                'total_files_scanned' => count($baseline['files']),
                'started_at' => now(),
                'finished_at' => now(),
                'duration_ms' => 0,
                'summary' => __('command.integrity.baseline_generated'),
            ]);

            $this->newLine();
            $this->info(__('command.integrity.baseline_success'));
            $this->table(
                [__('command.integrity.item'), __('command.integrity.value')],
                [
                    [__('command.integrity.files_count'), count($baseline['files'])],
                    [__('command.integrity.app_version'), $baseline['meta']['app_version'] ?? 'unknown'],
                    [__('command.integrity.hash_algo'), $baseline['meta']['hash_algo']],
                    [__('command.integrity.generated_at'), $baseline['meta']['generated_at']],
                ]
            );

            return Command::SUCCESS;
        }

        $this->error(__('command.integrity.baseline_failed'));
        return Command::FAILURE;
    }
}
