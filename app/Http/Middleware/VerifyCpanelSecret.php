<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCpanelSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('app.cpanel_secret', env('CPANEL_SECRET'));

        if (!$secret) {
            return response()->json(['error' => 'CPANEL_SECRET not configured'], 500);
        }

        if ($request->query('key') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
