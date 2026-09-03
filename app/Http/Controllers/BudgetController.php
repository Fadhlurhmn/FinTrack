<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->budgets()->with('category')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount_limit' => 'required|numeric|min:0',
            'period' => 'required|in:weekly,monthly',
            'start_date' => 'required|date',
        ]);

        $budget = $request->user()->budgets()->create($validated);

        return response()->json($budget->load('category'), 201);
    }

    public function show(Request $request, Budget $budget)
    {
        $this->authorizeOwner($request, $budget);

        return $budget->load('category');
    }

    public function update(Request $request, Budget $budget)
    {
        $this->authorizeOwner($request, $budget);

        $validated = $request->validate([
            'amount_limit' => 'sometimes|required|numeric|min:0',
            'period' => 'sometimes|required|in:weekly,monthly',
            'start_date' => 'sometimes|required|date',
        ]);

        $budget->update($validated);

        return response()->json($budget->load('category'));
    }

    public function destroy(Request $request, Budget $budget)
    {
        $this->authorizeOwner($request, $budget);

        $budget->delete();

        return response()->json(['message' => 'Budget deleted']);
    }

    // Endpoint tambahan: hitung progress pemakaian budget vs limit
    public function status(Request $request, Budget $budget)
    {
        $this->authorizeOwner($request, $budget);

        $periodStart = $budget->period === 'monthly'
            ? Carbon::now()->startOfMonth()
            : Carbon::now()->startOfWeek();

        $spent = $request->user()->transactions()
            ->where('category_id', $budget->category_id)
            ->where('type', 'expense')
            ->where('transaction_date', '>=', $periodStart)
            ->sum('converted_amount');

        return response()->json([
            'budget_id' => $budget->id,
            'category' => $budget->category->name,
            'limit' => (float) $budget->amount_limit,
            'spent' => (float) $spent,
            'remaining' => (float) $budget->amount_limit - $spent,
            'percentage' => $budget->amount_limit > 0
                ? round(($spent / $budget->amount_limit) * 100, 1)
                : 0,
        ]);
    }

    private function authorizeOwner(Request $request, Budget $budget)
    {
        if ($budget->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}