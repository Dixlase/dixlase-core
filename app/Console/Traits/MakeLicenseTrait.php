<?php

namespace App\Console\Traits;

use Illuminate\Support\Facades\File;

trait MakeLicenseTrait
{

    protected function getCoreLicenseInfo(): array
    {
        $licenseChoices = $this->getLicenseOptions();
        $licenseTemplates = $this->getLicenseTemplates();
        $LicenseMaps = $this->getLicenseMap();

        $key = $this->choice('コアファイルのライセンス種別を選んでください', $licenseChoices);

        if ($key === 'ライセンス表記なし') {
            $this->warn("⚠ ライセンス表記なしで生成します。");
            return null;
        }

        $licenseMap = $LicenseMaps[$key];

        $this->info($LicenseMaps[$key]);

        $lisenceTemplate = $licenseTemplates[$licenseMap];

        if (!File::exists(base_path($licenseMap[$key]))) {
            $this->warn("⚠ ライセンスのテンプレートが見つかりません。ライセンス表記なしで生成します。: {$lisenceTemplate}");
            return null;
        }

        return [
            'info' => [
                'software' => 'MySoftware',
                'author' => 'MyName',
                'website' => 'https://example.com',
                'license' => $key,
                'year' => date('Y'),
                'template' => $licenseMap[$key],
            ],
            'template' => File::get(base_path($licenseMap[$key])),
        ];
    }





    /**
     * コマンドのオプションを取得して統一する
     *
     * @return array
     */
    protected function getLicenseOptions(): array
    {
        return [
            '1' => 'GPL-3.0',
            '2' => 'AGPL-3.0',
            '3' => 'MIT',
            '4' => 'Apache-2.0',
            '5' => 'BSD-3-Clause',
            '6' => 'LGPL-3.0',
            '7' => '商用ライセンス',
            '8' => '独自ライセンス',
            '9' => 'ライセンス表記なし',
        ];
    }
    protected function getLicenseMap(): array
    {
        return [
            'GPL-3.0' => 'gpl',
            'AGPL-3.0' => 'agpl',
            'MIT' => 'mit',
            'Apache-2.0' => 'apache',
            'BSD-3-Clause' => 'bsd3',
            'LGPL-3.0' => 'lgpl',
            '商用ライセンス' => 'commercial',
            '独自ライセンス' => 'custom',
            'ライセンス表記なし' => '',
        ];
    }



    /**
     * ライセンスのテンプレートファイルパスを取得
     *
     * @return array
     */
    protected function getLicenseTemplates(): array
    {
        return [
            'GPL-3.0' => 'license-templates/license-gpl.txt',
            'AGPL-3.0' => 'license-templates/license-agpl.txt',
            'MIT' => 'license-templates/license-mit.txt',
            'Apache-2.0' => 'license-templates/license-apache.txt',
            'BSD-3-Clause' => 'license-templates/license-bsd3.txt',
            'LGPL-3.0' => 'license-templates/license-lgpl.txt',
            '商用ライセンス' => 'license-templates/license-commercial.txt',
            '独自ライセンス' => 'license-templates/license-custom.txt',
            'ライセンス表記なし' => '',
        ];
    }

    /**
     * ルートディレクトリの `license-info.json` を取得
     * 存在しない場合は `license-info.sample.json` をコピーして作成を促す
     *
     * @return array|null
     */
    protected function getRootLicenseInfo(): ?array
    {
        $licenseFilePath = base_path('license-info.json');
        $sampleFilePath  = base_path('license-info.sample.json');

        if (!File::exists($licenseFilePath)) {
            if (File::exists($sampleFilePath)) {
                File::copy($sampleFilePath, $licenseFilePath);
                $this->error("エラー: `license-info.json` が存在しません。`license-info.sample.json` を `license-info.json` にリネームして編集してください。");
            } else {
                $this->error("エラー: `license-info.json` が見つかりません。`license-info.sample.json` も存在しません。処理を中止します。");
            }
            return null;
        }

        $content = File::get($licenseFilePath);
        $licenseInfo = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("エラー: `license-info.json` の形式が無効です。修正してください。");
            return null;
        }

        return $licenseInfo;
    }

    /**
     * カスタムディレクトリ用のライセンス情報を取得
     *
     * @param string $licenseKey (例: 'gpl', 'agpl', 'custom')
     * @return array|null
     */
    protected function getLicenseInfo(string $licenseKey): ?array
    {
        $licenseKey = strtolower($licenseKey); // オプション名は小文字に統一
        $licenseMap = $this->getLicenseTemplates();

        if (!isset($licenseMap[$licenseKey])) {
            $this->error("エラー: 無効なライセンス [{$licenseKey}] が選択されました。処理を中止します。");
            return null;
        }

        $licenseFile = $licenseMap[$licenseKey];
        $licensePath = base_path($licenseFile);

        if (!File::exists($licensePath)) {
            $this->error("エラー: ライセンステンプレートファイルが見つかりません [{$licenseFile}]。処理を中止します。");
            return null;
        }

        $rootLicenseInfo = $this->getRootLicenseInfo();
        if (!$rootLicenseInfo) {
            return null; // ルートのライセンス情報がない場合、中止
        }

        return [
            'software' => $rootLicenseInfo['software'] ?? 'MySoftware',
            'author'   => $rootLicenseInfo['author'] ?? 'Unknown Author',
            'website'  => $rootLicenseInfo['website'] ?? 'https://example.com',
            'license'  => $this->getLicenseOptions()[$licenseKey] ?? 'GPL-3.0',
            'template' => $licenseFile,
            'text'     => File::get($licensePath),
        ];
    }

    /**
     * 指定されたプラグインの `license-info.json` を取得
     *
     * @param string $pluginName プラグイン名
     * @return array|null
     */
    protected function getPluginLicenseInfo(string $pluginName): ?array
    {
        $licenseInfoFile = base_path("plugins/{$pluginName}/license-info.json");

        if (!File::exists($licenseInfoFile)) {
            $this->warn("⚠ プラグイン [{$pluginName}] のライセンス情報が見つかりません。ライセンス表記なしで生成します。");
            return [
                'template' => '',
                'info' => [],
            ];
        }

        $content = File::get($licenseInfoFile);
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($decoded['template'])) {
            $this->warn("⚠ プラグイン [{$pluginName}] の `license-info.json` が無効です。ライセンス表記なしで生成します。");
            return [
                'template' => '',
                'info' => [],
            ];
        }

        $licensePath = base_path($decoded['template']);
        if (!File::exists($licensePath)) {
            $this->warn("⚠ プラグイン [{$pluginName}] のライセンステンプレートが見つかりません。ライセンス表記なしで生成します。");
            return [
                'template' => '',
                'info' => [],
            ];
        }

        //作成する年をライセンス情報に追加
        $decoded['year'] = date('Y');

        return [
            'template' => File::get($licensePath),
            'info' => $decoded,
        ];
    }


    /**
     * ライセンス名を取得
     */
    public function getLicenseName(): string
    {
        $licenseInfo = $this->getLicenseInfo();
        return $licenseInfo['license'] ?? 'AGPL-3.0';
    }

    /**
     * ライセンスのプレーンテキスト（placeholders差し替え済み）を取得する
     */
    public function getLicensePlain(string $licenseInfo): string
    {
        // licenseInfo から読み込み
        $licensePath = base_path($licenseInfo['template']);

        if (!$this->files->exists($licensePath)) {
            return '';
        }

        // {software}, {year}, {author}, {website} を差し替え
        $licenseText = $this->files->get($licensePath);
        $licensePlaceholders = [
            '{software}' => $licenseInfo['software'] ?? 'MySoftware',
            '{year}'     => date('Y'),
            '{author}'   => $licenseInfo['author'] ?? 'MyName',
            '{website}'  => $licenseInfo['website'] ?? 'https://example.com',
        ];
        return $this->replacePlaceholders($licenseText, $licensePlaceholders);
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
