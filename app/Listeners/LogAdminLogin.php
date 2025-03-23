<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use App\Models\Member;

class LogAdminLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {

        // 管理者のみログを残す（必要に応じてガードで条件分岐も可）
        if ($event->user instanceof Member) {
            Log::channel('admin_login')->info('ログイン', [
                'id' => $event->user->id,
                'name' => $event->user->name,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'time' => now()->toDateTimeString(),
            ]);
        }
    }
}
