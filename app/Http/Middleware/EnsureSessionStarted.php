<?php
// app/Http/Middleware/EnsureSessionStarted.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSessionStarted
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->isStarted()) {
            session()->start();
        }

        return $next($request);
    }
}
