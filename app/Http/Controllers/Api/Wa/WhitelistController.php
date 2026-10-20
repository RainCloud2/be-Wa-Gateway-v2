<?php

namespace App\Http\Controllers\Api\Wa;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;

class WhitelistController extends Controller
{
    /**
     * Active employee phone numbers, for the gateway to filter incoming
     * messages before forwarding them to the backend.
     */
    public function index(): JsonResponse
    {
        $numbers = Employee::query()
            ->real()
            ->where('is_active', true)
            ->where('phone', 'not like', Employee::SIMULATION_PHONE_PREFIX.'%')
            ->orderBy('phone')
            ->pluck('phone')
            ->values();

        return response()->json(['numbers' => $numbers]);
    }
}
