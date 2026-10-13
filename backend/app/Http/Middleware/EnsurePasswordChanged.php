<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password
            && ! $request->is('api/me', 'api/logout', 'api/change-password')) {
            return response()->json([
                'message' => 'You must change your password before continuing.',
                'code'    => 'password_change_required',
            ], 403);
        }

        return $next($request);
    }
}