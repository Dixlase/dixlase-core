<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\SecuritySetting;
use Illuminate\Support\Facades\Auth;

class SetSessionTable
{
    public function handle(Request $request, Closure $next)
    {
        // Laravelの StartSession ミドルウェアが処理する前に実行される可能性があるため、
        // セッションが開始されていない場合は何もしない
        // 1. StartSession ミドルウェア後に呼ばれるように順序を調整
        // 2. ログイン状態なら members_sessions、未ログインなら sessions

        /*
        if (Auth::guard('member')->check()) {
            config(['session.table' => 'members_sessions']);
        } else {
            config(['session.table' => 'sessions']);
        }
        dump(config('session.table'));
        */

        /*
        if (!session()->isStarted()) {
            return $next($request);
        }

        //セッションIDを取得
        $sessionId = session()->getId();

        // 管理画面のURLを取得
        $adminUrl = SecuritySetting::get('admin_url', config('security.admin_url'));
        */

        return $next($request);
    }
}
