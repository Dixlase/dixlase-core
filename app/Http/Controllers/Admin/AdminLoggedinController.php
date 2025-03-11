<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminLoggedInController extends AdminController
{
    protected $member;

    public function __construct()
    {
        parent::__construct();

        // ミドルウェアが適用された後に `setMember()` を実行
        $this->middleware(function ($request, $next) {
            $this->setMember();
            return $next($request);
        });
    }

    //管理者情報を取得
    protected function setMember()
    {
        $this->member = Auth::guard('member')->user();
        $this->viewParams['member'] = $this->member;
    }
}
