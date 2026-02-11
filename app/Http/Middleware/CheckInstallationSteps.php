<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstallationSteps
{
    /**
     * ルート名からステップ番号を取得
     */
    private function getStepFromRoute($routeName)
    {
        $routeToStep = [
            'install.settings' => 'settings',
            'install.environment' => 'environment',
            'install.environment.store' => 'environment',
            'install.database' => 'database',
            'install.database.store' => 'database',
            'install.mail' => 'mail',
            'install.mail.store' => 'mail',
            'install.confirm' => 'confirm',
            'install.confirm.store' => 'confirm',
        ];

        $stepOrder = [
            'settings', // 1
            'environment', // 2
            'database', // 3
            'mail', // 4
            'confirm', // 5
            'complete', // 6
        ];

        $stepName = $routeToStep[$routeName] ?? null;
        $step = array_search($stepName, $stepOrder);

        return $step !== false ? $step + 1 : 0;
    }

    /**
     * 各ステップで必要なフィールドがセッションに存在するか確認
     */
    private function checkStepFields($stepName, $data)
    {
        $requiredKeys = [
            'settings' => ['site_name', 'admin_account_name', 'admin_email', 'admin_password'],
            'environment' => ['app_env', 'app_url', 'app_timezone', 'admin_url'],
            'database' => ['db_connection', 'db_host', 'db_port', 'db_database', 'db_username'],
            'mail' => [],
        ];

        if (! isset($requiredKeys[$stepName])) {
            return true; // チェック対象外のステップは常に成功とみなす
        }

        foreach ($requiredKeys[$stepName] as $field) {
            if (! isset($data[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 指定したステップまでの全ステップが完了しているか確認
     *
     * @param  int  $step  確認するステップ番号
     * @param  array  $installData  セッションデータ
     * @return array [bool $isCompleted, int|null $firstIncompleteStep] 完了状態と最初の未完了ステップ番号
     */
    private function isStepCompleted($step, $installData)
    {
        $stepOrder = [
            'settings', // 1
            'environment', // 2
            'database', // 3
            'mail', // 4
            'confirm', // 5
            'complete', // 6
        ];

        // Get the actual install data, or an empty array if not set
        $data = $installData['install_data'] ?? [];

        // First, always check if the very first step (settings) is complete.
        // This handles cases where install_data exists but is incomplete.
        if (! $this->checkStepFields('settings', $data)) {
            return [false, 1];
        }

        // 指定されたステップの直前のステップまでをチェック
        for ($i = 0; $i < ($step - 1); $i++) {
            $stepName = $stepOrder[$i];
            if (! $this->checkStepFields($stepName, $data)) {
                return [false, $i + 1];
            }
        }

        return [true, null];
    }

    /**
     * ステップ番号に対応するルート名を取得
     */
    private function getRouteForStep($step)
    {
        $routes = [
            1 => 'install.settings',     // 基本設定
            2 => 'install.environment', // 環境設定
            3 => 'install.database',  // データベース設定
            4 => 'install.mail',  // メール設定
            5 => 'install.confirm',   // 確認画面
            6 => 'install.complete',   // 完了画面
        ];

        return $routes[$step] ?? 'install.index';
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // print_r(session()->all());
        // 現在のルート名を取得
        $currentRoute = $request->route() ? $request->route()->getName() : null;

        if (! $currentRoute) {
            return $next($request);
        }

        $currentRoute = $request->route()->getName();

        // 完了ページはインストール完了フラグがあれば許可
        if ($currentRoute === 'install.complete') {
            return $next($request);
        }

        // インデックスページとモード選択ページは常に許可
        if (in_array($currentRoute, ['install.index', 'install.mode', 'install.mode.store'])) {
            return $next($request);
        }

        // 現在のステップを取得
        $currentStep = $this->getStepFromRoute($currentRoute);

        if ($currentStep > 0) {
            $installData = session()->all();

            // リクエストがPOSTの場合は、バリデーション前にステップチェックをスキップ
            if ($request->isMethod('post')) {
                return $next($request);
            }

            // 現在のステップが1（基本設定）の場合はチェックをスキップ
            if ($currentStep === 1) {
                return $next($request);
            }

            // 現在のステップまでの全ステップが完了しているか確認
            [$allStepsCompleted, $firstIncompleteStep] = $this->isStepCompleted($currentStep, $installData);

            // 未完了のステップがある場合は、最初の未完了ステップにリダイレクト
            if (! $allStepsCompleted) {
                $targetRoute = $this->getRouteForStep($firstIncompleteStep);
                // 現在のルートと異なる場合のみリダイレクト
                if ($currentRoute !== $targetRoute) {
                    return redirect()->route($targetRoute)
                        ->with('error', __('install/common.please_complete_previous_steps'));
                }
            }
        }

        return $next($request);
    }
}
