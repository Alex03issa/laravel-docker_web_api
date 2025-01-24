<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateSecretToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->query('key');

        if (!$token || $token !== env('SECRET_API_KEY')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing API key.',
            ], 401);
        }

        return $next($request);
    }
}
