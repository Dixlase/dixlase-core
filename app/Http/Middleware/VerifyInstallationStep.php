<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class VerifyInstallationStep
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $currentRoute = $request->route()->getName();
        
        // 現在のステップを取得
        $currentStep = $this->getStepFromRoute($currentRoute);
        
        // インデックスページは常に許可
        if ($currentStep === 0) {
            return $next($request);
        }
        
        $installData = session('install_data', []);
        
        // 必要な前のステップが完了しているか確認
        for ($i = 1; $i < $currentStep; $i++) {
            if (!$this->isStepCompleted($i, $installData)) {
                return redirect()->route('install.index');
            }
        }
        
        return $next($request);
    }
    
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
     * 各ステップが完了しているか確認
     */
    private function isStepCompleted($step, $installData)
    {
        switch ($step) {
            case 1: // 基本設定
                return isset($installData['site_name']) && 
                       isset($installData['admin_name']) && 
                       isset($installData['admin_email']) && 
                       isset($installData['admin_password']);
                
            case 2: // 環境設定
                return isset($installData['app_env']) && 
                       isset($installData['app_url']) && 
                       isset($installData['app_timezone']);
                
            case 3: // セキュリティ設定
                return isset($installData['admin_url']);
                
            case 4: // データベース設定
                return isset($installData['db_connection']) && 
                       isset($installData['db_host']) && 
                       isset($installData['db_port']) && 
                       isset($installData['db_database']) && 
                       isset($installData['db_username']);
                
            default:
                return false;
        }
    }
}
