<?php

namespace App\Http\Middleware;

use App\Models\DriverRegister;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthenticateToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token not provided.'], 401);
        }

        $token = str_replace('Bearer ', '', $token); // Extract the token from the header

        // Retrieve user with the token
        $driver = DriverRegister::where('bearer_token', $token)->first();

        if (!$driver) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        $request->attributes->set('driver', $driver);

        return $next($request);
    }
}
