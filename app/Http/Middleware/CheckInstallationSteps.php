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
        $steps = [
            'install.index' => 0,
            'install.settings' => 1,
            'install.settings.store' => 1,
            'install.environment' => 2,
            'install.environment.store' => 2,
            'install.security' => 3,
            'install.security.store' => 3,
            'install.database' => 4,
            'install.database.store' => 4,
            'install.confirm' => 5,
            'install.confirm.store' => 5,
            'install.complete' => 6,
        ];
        
        return $steps[$routeName] ?? 0;
    }
    
    /**
     * 各ステップで必要なフィールドがセッションに存在するか確認
     */
    private function checkStepFields($step, $data)
    {

        $stepRequirements = [
            1 => [ // 基本設定
                'site_name',
                'admin_name',
                'admin_email',
                'admin_password'
            ],
            2 => [ // 環境設定
                'app_env',
                'app_url',
                'app_timezone'
            ],
            3 => [ // セキュリティ設定
                'admin_url'
            ],
            4 => [ // データベース設定
                'db_connection',
                'db_host',
                'db_port',
                'db_database',
                'db_username'
            ]
        ];

        if (!isset($stepRequirements[$step])) {
            return false;
        }

        foreach ($stepRequirements[$step] as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 指定したステップまでの全ステップが完了しているか確認
     * @param int $step 確認するステップ番号
     * @param array $installData セッションデータ
     * @return array [bool $isCompleted, int|null $firstIncompleteStep] 完了状態と最初の未完了ステップ番号
     */
    private function isStepCompleted($step, $installData)
    {
        // Check if install_data is set in the session
        if (empty($installData['install_data'])) {
            return [false, 1]; // 基本設定から開始
        }
        
        // Get the actual install data
        $data = $installData['install_data'];
        
        // 指定されたステップまでの全ステップをチェック
        for ($i = 1; $i <= $step; $i++) {
            if (!$this->checkStepFields($i, $data)) {
                return [false, $i];
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
            1 => 'install.index',     // 基本設定
            2 => 'install.environment', // 環境設定
            3 => 'install.security',  // セキュリティ設定
            4 => 'install.database',  // データベース設定
            5 => 'install.confirm',   // 確認画面
            6 => 'install.complete'   // 完了画面
        ];
        
        return $routes[$step] ?? 'install.index';
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {

        //print_r(session()->all());
        // 現在のルート名を取得
        $currentRoute = $request->route() ? $request->route()->getName() : null;
        
        if (!$currentRoute) {
            return $next($request);
        }
        
        $currentStep = $this->getStepFromRoute($currentRoute);
        
        // 完了ページはインストール完了フラグがあれば許可
        if ($currentRoute === 'install.complete' && session('installation_complete')) {
            return $next($request);
        }
        
        // インデックスページは常に許可
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
            list($allStepsCompleted, $firstIncompleteStep) = $this->isStepCompleted($currentStep, $installData);
            
            // 未完了のステップがある場合は、最初の未完了ステップにリダイレクト
            if (!$allStepsCompleted) {
                $targetRoute = $this->getRouteForStep($firstIncompleteStep);
                // 現在のルートと異なる場合のみリダイレクト
                if ($currentRoute !== $targetRoute) {
                    return redirect()->route($targetRoute)
                        ->with('error', __('install.please_complete_previous_steps'));
                }
            }
        }

        return $next($request);
    }
}
