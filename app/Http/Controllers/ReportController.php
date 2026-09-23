<?php

namespace App\Http\Controllers;

use App\Services\AccountBalanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __invoke(Request $request, AccountBalanceService $balances)
    {
        $period = $request->validate([
            'period' => ['nullable', 'in:today,week,month,year,custom'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ])['period'] ?? 'month';

        [$start, $end] = $this->range($period, $request);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:80'],
            'type' => ['nullable', 'in:income,expense,transfer'],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ]);
        $transactions = $request->user()->transactions()
            ->with(['category', 'account', 'destinationAccount'])
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('transaction_date')
            ->get();
        $transactions = $transactions->filter(function ($transaction) use ($filters) {
            $matchesSearch = empty($filters['search']) || str_contains(strtolower($transaction->note ?? ''), strtolower($filters['search'])) || str_contains(strtolower($transaction->account->name), strtolower($filters['search']));
            $matchesType = empty($filters['type']) || $transaction->type === $filters['type'];
            $matchesAccount = empty($filters['account_id']) || (int) $transaction->account_id === (int) $filters['account_id'];

            return $matchesSearch && $matchesType && $matchesAccount;
        });

        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');
        $transfer = $transactions->where('type', 'transfer')->sum('amount');
        $categoryExpenses = $transactions->where('type', 'expense')
            ->groupBy(fn ($transaction) => $transaction->category?->name ?? 'Tanpa kategori')
            ->map(fn ($items) => $items->sum('amount'))
            ->sortDesc();
        $dailyFlow = $transactions->whereIn('type', ['income', 'expense'])
            ->groupBy(fn ($transaction) => $transaction->transaction_date->format('Y-m-d'))
            ->map(fn ($items) => [
                'income' => $items->where('type', 'income')->sum('amount'),
                'expense' => $items->where('type', 'expense')->sum('amount'),
            ]);
        $maxCategory = max(1, (int) $categoryExpenses->max());
        $maxDaily = max(1, (int) $dailyFlow->flatMap(fn ($day) => [$day['income'], $day['expense']])->max());

        return view('reports.index', [
            'period' => $period, 'start' => $start, 'end' => $end,
            'income' => $income, 'expense' => $expense, 'net' => $income - $expense,
            'transfer' => $transfer, 'categoryExpenses' => $categoryExpenses,
            'maxCategory' => $maxCategory, 'dailyFlow' => $dailyFlow, 'maxDaily' => $maxDaily,
            'filters' => $filters,
            'filterAccounts' => $request->user()->accounts()->where('is_active', true)->get(),
            'accounts' => $request->user()->accounts()->where('is_active', true)->get()
                ->map(fn ($account) => ['name' => $account->name, 'balance' => $balances->for($account)])
                ->sortByDesc('balance'),
        ]);
    }

    public function export(Request $request)
    {
        $period = $request->input('period', 'month');
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:80'],
            'type' => ['nullable', 'in:income,expense,transfer'],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ]);
        [$start, $end] = $this->range($period, $request);
        $transactions = $request->user()->transactions()->with(['category', 'account', 'destinationAccount'])
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->when(! empty($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->when(! empty($filters['account_id']), fn ($query) => $query->where('account_id', $filters['account_id']))
            ->orderBy('transaction_date')->get();
        if (! empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $transactions = $transactions->filter(fn ($transaction) => str_contains(strtolower($transaction->note ?? ''), $search) || str_contains(strtolower($transaction->account->name), $search));
        }

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Tanggal', 'Jenis', 'Akun', 'Tujuan', 'Kategori', 'Nominal', 'Catatan']);
            foreach ($transactions as $transaction) {
                fputcsv($handle, [$transaction->transaction_date->format('Y-m-d'), $transaction->type, $transaction->account->name, $transaction->destinationAccount?->name, $transaction->category?->name, $transaction->amount, $transaction->note]);
            }
            fclose($handle);
        }, 'arus-transaksi.csv', ['Content-Type' => 'text/csv']);
    }

    private function range(string $period, Request $request): array
    {
        if ($period === 'custom' && $request->filled(['start_date', 'end_date'])) {
            return [Carbon::parse($request->input('start_date'))->startOfDay(), Carbon::parse($request->input('end_date'))->endOfDay()];
        }

        $today = Carbon::today();

        return match ($period) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
            'year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        };
    }
}