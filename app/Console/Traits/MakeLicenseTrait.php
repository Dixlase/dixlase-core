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
        } elseif ($fileType === 'plugin' && $pluginName) {
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
        // For core files, try to get license info from custom/license-info.json
        if ($showNotice) {
            $customLicensePath = base_path('custom/license-info.json');
            
            if (File::exists($customLicensePath)) {
                try {
                    $licenseInfo = json_decode(File::get($customLicensePath), true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // ライセンスキーが存在しないか空の場合はスキップ
                        if (empty($licenseInfo['license'])) {
                            $this->warn(__('command.license.warnings.license_key_missing_or_empty'));
                            return [];
                        }
                        
                        $licenseKey = $licenseInfo['license'];
                        $templatePath = config('license.templates.' . $licenseKey);
                        
                        if (empty($templatePath)) {
                            $this->warn(__('command.license.warnings.invalid_license_key', ['license' => $licenseKey]));
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
                            'template' => File::exists(base_path($templatePath)) ? 
                                File::get(base_path($templatePath)) : 
                                "// License: {$licenseKey}\n",
                        ];
                    }
                } catch (\Exception $e) {
                    $this->warn(__('command.license.failed_to_read_license', ['error' => $e->getMessage()]));
                }
            }
        }

        // Fallback to the original behavior for plugins or if custom file is not found
        $licenseKeys = config('license.keys', []);
        if (empty($licenseKeys)) {
            $this->warn(__('command.license.no_available_choices'));
            return [];
        }

        $licenseLabels = [];
        foreach ($licenseKeys as $key) {
            $label = trans("license.labels.{$key}");
            $licenseLabels[$key] = $label !== "license.labels.{$key}" ? $label : $key;
        }

        $values = array_values($licenseLabels);
        $selectedLabel = $this->choice(__('command.license.prompt'), $values);
        $selectedKey = array_search($selectedLabel, $licenseLabels);

        if ($selectedKey === 'none') {
            return []; // No license
        }

        $templatePath = config('license.templates')[$selectedKey] ?? null;
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
                'template' => $templatePath,
            ],
            'template' => File::get(base_path($templatePath)),
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
     * @return array
     */
    protected function getNewLicenseInfo(bool $showNotice = false): array
    {
        $licenseKeys = config('license.keys', []);
        if (empty($licenseKeys)) {
            $this->warn(__('command.license.no_available_choices'));
            return [];
        }

        $licenseLabels = [];
        foreach ($licenseKeys as $key) {
            $label = trans("license.labels.{$key}");
            $licenseLabels[$key] = $label !== "license.labels.{$key}" ? $label : $key;
        }
        // 警告メッセージは必要な場合のみ表示
        if ($showNotice) {
            $this->warn(__('command.license.warnings.notice'));
        }

        $values = array_values($licenseLabels);
        $selectedLabel = $this->choice(__('command.license.prompt'), $values);
        $selectedKey = array_search($selectedLabel, $licenseLabels);

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
        $plain = $this->getLicensePlain($license);
        if (empty($plain)) {
            return '';
        }

        // Blade用コメントでラップ
        return "{{--\n" . trim($plain) . "\n--}}";
    }
}
