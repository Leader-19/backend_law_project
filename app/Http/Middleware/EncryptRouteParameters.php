<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EncryptRouteParameters
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if ($route) {
            foreach ($route->parameters as $key => $value) {
                if (is_string($value) && str_starts_with($value, 'enc:')) {
                    try {
                        $decrypted = Crypt::decryptString(Str::after($value, 'enc:'));
                        $route->setParameter($key, $decrypted);
                    } catch (\Throwable) {
                        abort(404);
                    }
                }
            }
        }

        return $next($request);
    }
}
