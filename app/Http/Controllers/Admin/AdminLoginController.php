<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\AdminLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class AdminLoginController extends AdminController
{

    //初期設定を行う
    public function __construct()
    {
        parent::__construct();
        // ログイン済みなら `/admin/dashboard` へリダイレクト
        //$this->middleware('guest:member')->except('logout');

        //セッションIDの出力
        //dump(session()->getId());
        //Log::info('セッションID: ' . session()->getId());
    }
    /**
     * Display the login view.
     */
    public function create()
    {
        $member = Auth::guard('member')->user();

        Log::info('Session ID: ' . session()->getId());
        Log::info('CSRF Token: ' . session()->token());

        dump('Session ID: ' . session()->getId());
        dump('CSRF Token: ' . session()->token());

        if ($member) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login', $this->viewParams);
    }

    public function test()
    {
        $member = Auth::guard('member')->user();

        Log::info('Session ID: ' . session()->getId());
        Log::info('CSRF Token: ' . session()->token());

        dump('Session ID: ' . session()->getId());
        dump('CSRF Token: ' . session()->token());


        if ($member) {
            return redirect()->route('admin.dashboard');
        }


        return view('admin.auth.login', $this->viewParams);
    }


    /**
     * Handle an incoming authentication request.
     */
    public function store(AdminLoginRequest $request): RedirectResponse
    {

        // 1. 入力されたメールアドレスからメンバーを取得
        $member = \App\Models\Member::where('email', $request->email)->first();

        // 2. メンバーが存在し、パスワードが一致するか確認
        if ($member && \Illuminate\Support\Facades\Hash::check($request->password, $member->password)) {

            // 3. メンバーを認証
            Auth::guard('member')->login($member);

            // 4. セッションのテーブルを変更（ログインしたら members_sessions を使用）
            config(['session.table' => 'members_sessions']);

            // 5. セッションの再生成（旧セッション破棄 & 新ID発行）
            $request->session()->regenerate(true);

            // 6. デバッグログ（セッションの中身を確認）
            Log::info('セッションテーブル: ' . config('session.table'));
            Log::info('ログイン成功: ', session()->all());
            Log::info('ログイン後のセッションID: ' . session()->getId());

            return redirect()->intended(route('admin.dashboard'));
        }

        // 認証失敗時の処理
        Log::warning('ログイン失敗: ', ['email' => $request->email]);

        return back()->withErrors([
            'email' => 'メールアドレスまたはパスワードが正しくありません。',
        ])->withInput();

        /*
        //認証

        $request->authenticate('member');



        dd(session()->all());

        // 2) ここで「members_sessions」を使うよう強制
        config(['session.table' => 'members_sessions']);

        //セッションの再生成（旧セッション破棄 & 新ID発行）
        $request->session()->regenerate(true);


        return redirect()->intended(route('admin.dashboard'));
        */
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {

        Auth::guard('member')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();


        return to_route('admin.login');
    }
}
