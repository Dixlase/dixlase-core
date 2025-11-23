<?php

namespace App\Console\Traits;

use Illuminate\Support\Facades\File;

trait MakeLicenseTrait
{

    /**
     * ファイルタイプに応じたライセンス情報を取得
     *
     * @param string $fileType ファイルタイプ（core、plugin、theme）
     * @param string|null $pluginName プラグイン名またはテーマ名（ファイルタイプがpluginまたはthemeの場合のみ使用）
     * @return array ライセンス情報
     */
    protected function getFileTypeLicenseInfo(string $fileType, ?string $pluginName = null): array
    {
        // 共通メソッドを使用して統一的に取得
        if ($fileType === 'core') {
            return $this->getLicenseInfoFromJson('custom', null, true) ?? [];
        } elseif ($fileType === 'plugin' || $fileType === 'custom_plugin' && $pluginName) {
            return $this->getLicenseInfoFromJson('plugins', $pluginName, true) ?? [];
        } elseif ($fileType === 'theme' && $pluginName) {
            return $this->getLicenseInfoFromJson('themes', $pluginName, false) ?? [];
        }
        return [];
    }

    /**
     * 共通のライセンス情報取得メソッド
     * プラグイン、テーマ、カスタムディレクトリから統一的にライセンス情報を取得
     *
     * @param string $type タイプ（plugins、themes、custom）
     * @param string|null $name プラグイン名またはテーマ名（customの場合はnull）
     * @param bool $showNotice 警告メッセージを表示するかどうか
     * @return array|null ライセンス情報（template、infoを含む配列）
     */
    protected function getLicenseInfoFromJson(string $type, ?string $name = null, bool $showNotice = false): ?array
    {
        // パスの構築
        if ($type === 'custom') {
            $basePath = base_path('custom');
            $displayName = 'カスタムディレクトリ';
            
            // コアの場合は dixlase.json を優先的に確認
            $dixlaseJsonPath = base_path('dixlase.json');
            if (file_exists($dixlaseJsonPath)) {
                $jsonData = json_decode(file_get_contents($dixlaseJsonPath), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // dixlase.jsonの情報をlicense-info形式に変換
                    $licenseData = [
                        'license' => $jsonData['license'] ?? 'AGPL-3.0',
                        'year' => date('Y'),
                        'author' => $jsonData['author'] ?? 'Authr Name',
                        'url' => $jsonData['url'] ?? 'https://example.com',
                        'software' => $jsonData['name'] ?? 'Dixlase',
                    ];
                    return $this->formatLicenseInfo($licenseData, $displayName, $showNotice);
                }
            }
        } elseif ($type === 'plugins' && $name) {
            $basePath = base_path("plugins/{$name}");
            $displayName = "プラグイン: {$name}";
        } elseif ($type === 'themes' && $name) {
            $basePath = base_path("themes/{$name}");
            $displayName = "テーマ: {$name}";
        } else {
            return null;
        }

        // theme.jsonまたはplugin.jsonから情報を取得
        $jsonFile = null;
        if ($type === 'themes') {
            $jsonFile = "{$basePath}/theme.json";
        } elseif ($type === 'plugins') {
            $jsonFile = "{$basePath}/plugin.json";
        }

        if ($jsonFile && File::exists($jsonFile)) {
            $jsonData = json_decode(File::get($jsonFile), true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                // JSON形式からlicense-info形式に変換
                $url = $jsonData['url'] ?? 'https://example.com';
                // プレースホルダーの場合はデフォルト値を使用
                if (str_contains($url, '{{') || str_contains($url, '}}')) {
                    $url = 'https://example.com';
                }
                
                $licenseData = [
                    'license' => $jsonData['license'] ?? 'GPL-3.0',
                    'year' => date('Y'),
                    'author' => $jsonData['author'] ?? 'Your Name',
                    'url' => $url,
                    'software' => $jsonData['name'] ?? 'Software',
                ];
                
                return $this->formatLicenseInfo($licenseData, $displayName, $showNotice);
            }
        }

        if ($showNotice) {
            $this->warn("ライセンス情報が見つかりません: {$displayName}");
        }

        return null;
    }

    /**
     * ライセンス情報を統一フォーマットに整形
     *
     * @param array $licenseData ライセンスデータ
     * @param string $displayName 表示名（エラーメッセージ用）
     * @param bool $showNotice 警告メッセージを表示するかどうか
     * @return array|null フォーマット済みライセンス情報
     */
    protected function formatLicenseInfo(array $licenseData, string $displayName, bool $showNotice = false): ?array
    {
        // ライセンスキーの検証
        if (empty($licenseData['license'])) {
            if ($showNotice) {
                $this->warn("ライセンスキーが見つかりません: {$displayName}");
            }
            return null;
        }

        // ライセンステンプレートを取得
        $licenseName = $licenseData['license'];
        
        // GPL-3.0 → gpl, AGPL → agpl のように変換
        $templateFileName = 'license-' . strtolower(str_replace([' ', '.', '-'], ['', '', ''], $licenseName)) . '.txt';
        $templatePath = base_path('license-templates/' . $templateFileName);

        if (!File::exists($templatePath)) {
            // デフォルトのGPLテンプレートを使用
            $templatePath = base_path('license-templates/license-gpl.txt');
        }

        if (!File::exists($templatePath)) {
            if ($showNotice) {
                $this->warn("ライセンステンプレートが見つかりません: {$templatePath}");
            }
            return null;
        }

        $template = File::get($templatePath);

        // 統一フォーマットで返す
        return [
            'template' => $template,
            'info' => $licenseData,
        ];
    }


    /**
     * カスタムディレクトリのライセンス情報を取得（旧実装・削除予定）
     * @deprecated 共通メソッドgetLicenseInfoFromJsonを使用してください
     */
    protected function getCustomFileLicenseInfoOld(bool $showNotice = false): array
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
