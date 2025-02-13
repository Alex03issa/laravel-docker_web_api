<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiToken;
use Illuminate\Support\Facades\Log;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $headerToken = $request->header('Authorization');
        $queryToken = $request->query('key');

        $token = null;

        if ($headerToken && str_starts_with($headerToken, 'Bearer ')) {
            $tokenValue = substr($headerToken, 7); 
            $token = ApiToken::where('token_value', $tokenValue)->first();
        } 
        elseif ($request->hasHeader('x-api-Key')) {
            $tokenValue = $request->header('x-api-Key');
            $token = ApiToken::where('token_value', $tokenValue)->first();
        } 
        elseif ($queryToken) {
            $tokenValue = $queryToken;
            $token = ApiToken::where('token_value', $tokenValue)->first();
        }

        if (!$token) {
            Log::warning("Неверный API-токен или отсутствует.", ['provided_token' => $tokenValue ?? 'none']);
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid API token or missing authentication.',
            ], 401);
        }

        $request->merge(['account_id' => $token->account_id]);

        Log::info("Аутентификация пройдена", ['account_id' => $token->account_id]);
        return $next($request);
    }

}
