<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use App\Models\Member;

class LogAdminLogout
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
    public function handle(Logout $event): void
    {

        if ($event->user instanceof Member) {
            Log::channel('admin_login')->info('ログアウト', [
                'id' => $event->user->id,
                'name' => $event->user->name,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'time' => now()->toDateTimeString(),
            ]);
        }
    }
}
