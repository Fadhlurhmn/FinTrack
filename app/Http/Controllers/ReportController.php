<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function summary(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now();

        $income = $user->transactions()
            ->where('type', 'income')
            ->whereMonth('transaction_date', $now->month)
            ->whereYear('transaction_date', $now->year)
            ->sum('converted_amount');

        $expense = $user->transactions()
            ->where('type', 'expense')
            ->whereMonth('transaction_date', $now->month)
            ->whereYear('transaction_date', $now->year)
            ->sum('converted_amount');

        $totalBalance = $user->transactions()
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN converted_amount ELSE -converted_amount END) as balance")
            ->value('balance') ?? 0;

        return response()->json([
            'month_income' => (float) $income,
            'month_expense' => (float) $expense,
            'total_balance' => (float) $totalBalance,
        ]);
    }

    public function byCategory(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now();

        $data = $user->transactions()
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.type', 'expense')
            ->whereMonth('transaction_date', $now->month)
            ->whereYear('transaction_date', $now->year)
            ->select('categories.name as category', DB::raw('SUM(converted_amount) as total'))
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();

        return response()->json($data);
    }

    public function trend(Request $request)
    {
        $user = $request->user();

        $data = $user->transactions()
            ->select(
                DB::raw("DATE_FORMAT(transaction_date, '%Y-%m') as month"),
                'type',
                DB::raw('SUM(converted_amount) as total')
            )
            ->where('transaction_date', '>=', Carbon::now()->subMonths(5)->startOfMonth())
            ->groupBy('month', 'type')
            ->orderBy('month')
            ->get();

        // Reshape jadi format yang gampang dipakai Recharts: [{month, income, expense}, ...]
        $grouped = [];
        foreach ($data as $row) {
            if (!isset($grouped[$row->month])) {
                $grouped[$row->month] = ['month' => $row->month, 'income' => 0, 'expense' => 0];
            }
            $grouped[$row->month][$row->type] = (float) $row->total;
        }

        return response()->json(array_values($grouped));
    }
}