<?php

namespace App\Http\Controllers;

use App\Models\RecurringBill;
use Illuminate\Http\Request;

class RecurringBillController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->recurringBills()->with('category')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'due_day' => 'required|integer|min:1|max:28',
        ]);

        $bill = $request->user()->recurringBills()->create($validated);

        return response()->json($bill->load('category'), 201);
    }

    public function show(Request $request, RecurringBill $bill)
    {
        $this->authorizeOwner($request, $bill);

        return $bill->load('category');
    }

    public function update(Request $request, RecurringBill $bill)
    {
        $this->authorizeOwner($request, $bill);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'amount' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|string|size:3',
            'due_day' => 'sometimes|required|integer|min:1|max:28',
            'is_active' => 'sometimes|boolean',
        ]);

        $bill->update($validated);

        return response()->json($bill->load('category'));
    }

    public function destroy(Request $request, RecurringBill $bill)
    {
        $this->authorizeOwner($request, $bill);

        $bill->delete();

        return response()->json(['message' => 'Recurring bill deleted']);
    }

    private function authorizeOwner(Request $request, RecurringBill $bill)
    {
        if ($bill->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}