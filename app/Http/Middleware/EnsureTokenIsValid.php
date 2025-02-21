<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTokenIsValid
{
    public function handle($request, Closure $next) {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Yetkilendirme başarısız'], 401);
        }

        $currentToken = $user->tokens()->latest('id')->first();
        if (!$currentToken || !$currentToken->expires_at) {
            return response()->json(['message' => 'Token bulunamadı veya geçersiz'], 401);
        }

        if (now()->greaterThan($currentToken->expires_at)) {
            return response()->json(['message' => 'Token süreniz doldu, tekrar giriş yapın'], 401);
        }

        return $next($request);
    }
}
