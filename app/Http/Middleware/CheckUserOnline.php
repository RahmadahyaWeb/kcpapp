<?php

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckUserOnline
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $expireTime = Carbon::now()->addSecond(30);
            Cache::put('user-online' . Auth::id(), true, $expireTime);
            User::where('id', Auth::id())->update(['last_seen' => now(), 'isOnline' => 'Y']);
        }
        
        return $next($request);
    }
}
