<?php

namespace App\Http\Controllers\Api\Wa;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StatusController extends Controller
{
    public const CACHE_KEY = 'wa.status';

    /**
     * Best-effort connection status pushed by the gateway.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,connecting,close,logged_out,qr'],
            'qr' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        Cache::put(self::CACHE_KEY, [
            'status' => $data['status'],
            'qr' => $data['qr'] ?? null,
            'phone' => $data['phone'] ?? null,
            'updated_at' => now()->toIso8601String(),
        ], now()->addDays(7));

        return response()->json(['status' => 'ok']);
    }
}
