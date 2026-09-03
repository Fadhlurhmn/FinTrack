<?php

namespace App\Http\Controllers;

use App\Services\ExchangeRateService;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function __construct(private ExchangeRateService $exchangeRateService)
    {
    }

    public function rate(Request $request)
    {
        $validated = $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);

        try {
            $rate = $this->exchangeRateService->getRate($validated['from'], $validated['to']);
            return response()->json(['from' => $validated['from'], 'to' => $validated['to'], 'rate' => $rate]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }
    }
}