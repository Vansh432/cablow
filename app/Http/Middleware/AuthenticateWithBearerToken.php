<?php

namespace App\Http\Middleware;

use App\Models\UserRegister;
use Closure;
use Illuminate\Http\Request;

class AuthenticateWithBearerToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token not provided.'], 401);
        }

        // Retrieve user with the token
        $user = UserRegister::where('bearer_token', $token)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        // Optionally attach user to request
        $request->attributes->set('User', $user);

        return $next($request);
    }
}
