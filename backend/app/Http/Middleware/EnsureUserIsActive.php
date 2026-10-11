<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            $user->tokens()->delete();   // end every session of a deactivated account

            return response()->json(['message' => 'Your account is deactivated.'], 403);
        }

        return $next($request);
    }
}
