<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyGatewayToken
{
    /**
     * Only requests bearing the shared gateway secret (X-Gateway-Token) may
     * reach the /api/wa endpoints. The comparison is constant-time.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('wa.gateway.token');
        $provided = (string) $request->header('X-Gateway-Token', '');

        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        return $next($request);
    }
}
