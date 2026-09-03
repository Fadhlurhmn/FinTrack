<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Services\ExchangeRateService;

class TransactionController extends Controller
{
        public function __construct(private ExchangeRateService $exchangeRateService)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = $request->user()->transactions()->with('category');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('category_id')){
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('start_date') && $request->has('end_date')){
            $query->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }
        
        return $query->orderBy('transaction_date', 'desc')->get();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'type' => 'required|in:income,expense',
            'description' => 'nullable|string',
            'transaction_date' => 'required|date',
        ]);

        $baseCurrency = $request->user()->base_currency;

        try {
            $rate = $this->exchangeRateService->getRate($validated['currency'], $baseCurrency);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        $validated['exchange_rate'] = $rate;
        $validated['converted_amount'] = $validated['amount'] * $rate;

        $transaction = $request->user()->transactions()->create($validated);

        return response()->json($transaction->load('category'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Transaction $transaction)
    {
        $this->authorizeOwner($request, $transaction);
        return $transaction->load('category');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
                $this->authorizeOwner($request, $transaction);

        $validated = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|string|size:3',
            'type' => 'sometimes|required|in:income,expense',
            'description' => 'nullable|string',
            'transaction_date' => 'sometimes|required|date',
        ]);

        $transaction->update($validated);

        return response()->json($transaction->load('category'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction, Request $request)
    {
                $this->authorizeOwner($request, $transaction);

        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted']);
    }

        private function authorizeOwner(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
