<?php

namespace App\Console\Traits;

use Illuminate\Support\Facades\File;

trait MakeLicenseTrait
{

    /**
     * ファイルタイプに応じたライセンス情報を取得
     *
     * @param string $fileType ファイルタイプ（coreまたはplugin）
     * @param string|null $pluginName プラグイン名（ファイルタイプがpluginの場合のみ使用）
     * @return array ライセンス情報
     */
    protected function getFileTypeLicenseInfo(string $fileType, ?string $pluginName = null): array
    {
        if ($fileType === 'core') {
            return $this->getCustomFileLicenseInfo(true) ?? [];
        } elseif ($fileType === 'plugin' || $fileType === 'custom_plugin' && $pluginName) {
            return $this->getPluginLicenseInfo($pluginName) ?? [];
        }
        return [];
    }

    
    
    /**
     * カスタムディレクトリのライセンス情報を取得
     *
     * @param bool $showNotice 警告メッセージを表示するかどうか
     * @return array|null
     */

    protected function getCustomFileLicenseInfo(bool $showNotice = false): array
    {
        // カスタムライセンス情報ファイルのパス
        $customLicensePath = base_path('custom/license-info.json');
        
        // カスタムライセンス情報ファイルが存在する場合
        if (File::exists($customLicensePath)) {
            try {
                $licenseInfo = json_decode(File::get($customLicensePath), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('Invalid JSON format in license info file');
                }

                // ライセンスキーの検証
                $licenseKey = $licenseInfo['license'] ?? null;
                if (empty($licenseKey)) {
                    $this->warn(__('command.license.warnings.license_key_missing_or_empty'));
                    return [];
                }
                
                // テンプレートファイルのパスを取得
                $templateFile = config("license.templates.{$licenseKey}");
                if (empty($templateFile)) {
                    $this->warn(__('command.license.warnings.invalid_license_key', ['license' => $licenseKey]));
                    return [];
                }
                
                // テンプレートファイルのフルパス
                $templatePath = base_path("license-templates/{$templateFile}");
                
                if (!File::exists($templatePath)) {
                    $this->warn(__('command.license.template_not_found', ['path' => $templatePath]));
                    return [];
                }
                
                $this->info(__('command.license.using_custom_license', ['license' => $licenseKey]));
                
                return [
                    'info' => [
                        'software' => $licenseInfo['software'] ?? config('license.defaults.software'),
                        'author' => $licenseInfo['author'] ?? config('license.defaults.author'),
                        'website' => $licenseInfo['website'] ?? config('license.defaults.website'),
                        'license' => $licenseKey,
                        'year' => date('Y'),
                        'template' => $templatePath,
                    ],
                    'template' => File::get($templatePath),
                ];
                
            } catch (\Exception $e) {
                $this->warn(__('command.license.failed_to_read_license', ['error' => $e->getMessage()]));
                // フォールバック処理に進む
            }
        }

        // カスタムライセンス情報が利用できない場合のフォールバック
        $licenseKeys = array_keys(config('license.templates', []));
        $licenseKeys = array_filter($licenseKeys, fn($key) => $key !== 'NONE'); // NONEは除外
        
        if (empty($licenseKeys)) {
            $this->warn(__('command.license.no_available_choices'));
            return [];
        }

        // ライセンス選択肢の準備
        $licenseLabels = [];
        foreach ($licenseKeys as $key) {
            $label = trans("license.labels.{$key}");
            $licenseLabels[$key] = $label !== "license.labels.{$key}" ? $label : $key;
        }
        
        // 選択肢に「ライセンスなし」を追加
        $licenseLabels['none'] = 'ライセンスなし';

        // ユーザーにライセンスを選択させる
        $selectedLabel = $this->choice(__('command.license.prompt'), $licenseLabels);
        $selectedKey = array_search($selectedLabel, $licenseLabels);

        if ($selectedKey === 'none') {
            return []; // ライセンスなし
        }

        // 選択されたライセンスのテンプレートファイルを取得
        $templateFile = config("license.templates.{$selectedKey}");
        if (empty($templateFile)) {
            $this->warn(__('command.license.warnings.invalid_license_key', ['license' => $selectedKey]));
            return [];
        }
        
        $templatePath = base_path("license-templates/{$templateFile}");
        
        if (!File::exists($templatePath)) {
            $this->warn(__('command.license.template_not_found', ['path' => $templatePath]));
            return [];
        }

        return [
            'info' => [
                'software' => config('license.defaults.software'),
                'author' => config('license.defaults.author'),
                'website' => config('license.defaults.website'),
                'license' => $selectedKey,
                'year' => date('Y'),
                'template' => $templatePath,
            ],
            'template' => File::get($templatePath),
        ];
    }



    /**
     * プラグインのライセンス情報を取得
     *
     * @param string $pluginName
     * @return array|null
     */
    protected function getPluginLicenseInfo(string $pluginName): ?array
    {
        $licenseInfoFile = base_path("plugins/{$pluginName}/license-info.json");

        if (!File::exists($licenseInfoFile)) {
            $this->warn(__('command.license.warnings.plugin_missing', ['plugin' => $pluginName]));
            return null;
        }

        $info = json_decode(File::get($licenseInfoFile), true);
        $info['year'] = date('Y');

        // ライセンスキーが存在しないか空の場合はスキップ
        if (empty($info['license'])) {
            $this->warn(__('command.license.warnings.license_key_missing_or_empty'));
            return [];
        }

        // コンフィグからテンプレートパスを取得
        $templatePath = config('license.templatesPath') . '/' . config('license.templates.' . $info['license']);
        
        if (empty($templatePath)) {
            $this->warn(__('command.license.warnings.invalid_license_key', ['license' => $info['license']]));
            return [];
        }
        
        // フルパスに変換
        $templatePath = base_path($templatePath);
            
        if (File::exists($templatePath)) {
            $templateContent = File::get($templatePath);
        } else {
            $this->warn(__('command.license.warnings.template_not_found', ['path' => $templatePath]));
            return [];
        }

        return [
            'info' => $info,
            'template' => $templateContent,
        ];
    }


    /**
     * 新規ファイル作成時のライセンス情報を取得
     *
     * @param bool $showNotice 警告メッセージを表示するかどうか
     * @param string|null $licenseKey 指定されたライセンスキー（オプション）
     * @return array
     */
    protected function getNewLicenseInfo(bool $showNotice = false, ?string $licenseKey = null): array
    {
        $licenseKeys = config('license.keys', []);
        if (empty($licenseKeys)) {
            $this->warn(__('command.license.no_available_choices'));
            return [];
        }

        $licenseMap = array_change_key_case(config('license.map', []), CASE_UPPER);
        $licenseLabels = [];
        
        foreach ($licenseKeys as $key) {
            $label = trans("license.labels.{$key}");
            $licenseLabels[$key] = $label !== "license.labels.{$key}" ? $label : $key;
        }
        
        // 警告メッセージは必要な場合のみ表示
        if ($showNotice) {
            $this->warn(__('command.license.warnings.notice'));
        }

        // ライセンスキーが指定されている場合
        if ($licenseKey !== null) {
            $lowerLicenseKey = strtolower($licenseKey);
            $upperLicenseKey = strtoupper($licenseKey);
            
            // マップの値と完全一致するか確認 (gpl, mit, など)
            $mappedKey = array_search($lowerLicenseKey, array_map('strtolower', $licenseMap));
            
            if ($mappedKey !== false) {
                // マップの値で指定された場合 (例: --license=gpl)
                $selectedKey = $mappedKey; // 元のキー（GPL, MITなど）を使用
            }
            // マップのキーと完全一致するか確認 (GPL, MIT, など)
            elseif (isset($licenseMap[$upperLicenseKey])) {
                $selectedKey = $upperLicenseKey; // 元のキーを使用
            }
            // 直接キーが指定されている場合
            elseif (in_array($lowerLicenseKey, array_map('strtolower', $licenseKeys), true)) {
                // 元のケースに合わせる
                $selectedKey = $licenseKeys[array_search($lowerLicenseKey, array_map('strtolower', $licenseKeys))];
            }
            // 無効なライセンスキーの場合
            else {
                $this->warn(__('command.license.invalid_license', ['license' => $licenseKey]));
                $selectedKey = null;
            }
        } 
        // ライセンスキーが指定されていない場合は選択を求める
        else {
            $values = array_values($licenseLabels);
            // 1から始まる連想配列を作成
            $choices = [];
            foreach ($values as $index => $value) {
                $choices[$index + 1] = $value;
            }
            
            // 選択肢を表示（1から始まる番号で表示）
            $selectedNumber = $this->choice(__('command.license.prompt'), $choices);
            
            // 選択された番号から正しい配列のインデックスを計算
            $selectedIndex = array_search($selectedNumber, $choices);
            $selectedLabel = $values[$selectedIndex - 1];
            $selectedKey = array_search($selectedLabel, $licenseLabels);
        }

        if ($selectedKey === 'none') {
            return []; // ライセンスなし
        }

        $templateFile = config('license.templates')[$selectedKey];
        $templatePath = config('license.templatesPath') . '/' . $templateFile;
        if (!$templatePath || !File::exists(base_path($templatePath))) {
            $this->warn(__('command.license.template_missing_core', ['path' => $templatePath]));
            return [];
        }

        return [
            'info' => [
                'software' => config('license.defaults.software'),
                'author' => config('license.defaults.author'),
                'website' => config('license.defaults.website'),
                'license' => $selectedKey,
                'year' => date('Y'),
                'template' => $templateFile,
            ],
            'template' => File::get(base_path($templatePath)),
        ];
    }

    /**
     * PHPファイル用のライセンスをdoc-block 形式にする
     */
    public function embedLicenseForPhp(string $license): string
    {

        $lines = explode("\n", trim($license));
        $formatted = "/**\n";
        foreach ($lines as $line) {
            $formatted .= trim($line) === '' ? " *\n" : " * " . rtrim($line) . "\n";
        }
        $formatted .= " */";

        return $formatted;
    }

    /**
     * Blade 用にライセンスを "{{-- ... --}}" 形式のコメントにする
     */
    public function embedLicenseForBlade(string $license): string
    {
        if (empty(trim($license))) {
            return '';
        }

        // Blade用コメントでラップ
        return "{{--\n" . trim($license) . "\n--}}";
    }

    /**
     * ライセンスキーからライセンス情報を取得
     *
     * @param string $licenseKey ライセンスキー（例：MIT, GPL）
     * @param array $info プレースホルダー置換用の情報
     * @return array ライセンス情報（template, info）
     */
    protected function getLicenseInfoFromKey(string $licenseKey, array $info = []): array
    {
        // ライセンスキーが'NONE'または空の場合
        if (empty($licenseKey) || strtoupper($licenseKey) === 'NONE') {
            return [];
        }

        // ライセンステンプレートファイルのパスを取得
        $upperKey = strtoupper($licenseKey);
        $templateFile = config("license.templates.{$upperKey}");
        
        if (empty($templateFile)) {
            return [];
        }

        $templatePath = base_path(config('license.templatesPath') . '/' . $templateFile);
        
        if (!File::exists($templatePath)) {
            return [];
        }

        // デフォルト情報とマージ
        $licenseInfo = array_merge([
            'software' => config('license.defaults.software'),
            'author' => config('license.defaults.author'),
            'website' => config('license.defaults.website'),
            'year' => date('Y'),
        ], $info);

        return [
            'template' => File::get($templatePath),
            'info' => $licenseInfo,
        ];
    }
}
