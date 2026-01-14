<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle($request, Closure $next)
    {
        $user = auth('pegawai')->user();

        if (!$user || $user->role_pract !== 'admin') {
            abort(403);
        }

        return $next($request);
    }
}

