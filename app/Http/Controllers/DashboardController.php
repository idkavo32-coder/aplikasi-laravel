<?php

namespace App\Http\Controllers;

use App\Services\AccountBalanceService;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(AccountBalanceService $balances)
    {
        $user = request()->user();
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();
        $today = Carbon::today();
        $monthly = $user->transactions()->whereBetween('transaction_date', [$start, $end]);

        return view('dashboard', [
            'totalBalance' => $balances->totalForUser($user->id),
            'incomeThisMonth' => (clone $monthly)->where('type', 'income')->sum('amount'),
            'expenseThisMonth' => (clone $monthly)->where('type', 'expense')->sum('amount'),
            'unpaidDebtTotal' => $user->debts()->where('status', 'unpaid')->sum('amount'),
            'activeBudgetCount' => $user->budgets()->where('month', now()->format('Y-m'))->count(),
            'dueSoonDebts' => $user->debts()->where('status', 'unpaid')->whereBetween('due_date', [$today, $today->copy()->addDays(7)])->orderBy('due_date')->get(),
            'accounts' => $user->accounts()->where('is_active', true)->get(),
            'recentTransactions' => $user->transactions()->with(['account', 'category'])->latest('transaction_date')->latest()->limit(8)->get(),
        ]);
    }
}