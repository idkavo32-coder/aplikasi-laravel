<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MoreController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('more.index', [
            'accounts' => $request->user()->accounts()->where('is_active', true)->get(),
            'categories' => $request->user()->categories()->orderBy('type')->orderBy('name')->get(),
            'goalCount' => $request->user()->savingsGoals()->where('is_active', true)->count(),
        ]);
    }

    public function backup(Request $request)
    {
        $user = $request->user();
        $payload = [
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'accounts' => $user->accounts()->get()->toArray(),
            'categories' => $user->categories()->get()->toArray(),
            'transactions' => $user->transactions()->get()->toArray(),
            'savings_goals' => $user->savingsGoals()->with('savingsTransactions')->get()->toArray(),
            'debts' => $user->debts()->with('payments')->get()->toArray(),
            'budgets' => $user->budgets()->get()->toArray(),
            'recurring_transactions' => $user->recurringTransactions()->get()->toArray(),
        ];

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'arus-backup-'.now()->format('Y-m-d').'.json', ['Content-Type' => 'application/json']);
    }

    public function restore(Request $request)
    {
        $request->validate(['backup' => ['required', 'file', 'mimes:json,txt', 'max:10240']]);
        $payload = json_decode(file_get_contents($request->file('backup')->getRealPath()), true);
        abort_unless(is_array($payload), 422, 'File backup tidak valid.');
        $user = $request->user();

        DB::transaction(function () use ($payload, $user) {
            $accountMap = [];
            foreach ($payload['accounts'] ?? [] as $account) {
                $new = $user->accounts()->create(collect($account)->only(['name', 'type', 'opening_balance', 'color', 'icon', 'is_active'])->all());
                $accountMap[$account['id']] = $new->id;
            }
            $categoryMap = [];
            foreach ($payload['categories'] ?? [] as $category) {
                $new = $user->categories()->create(collect($category)->only(['name', 'type', 'icon', 'color'])->all());
                $categoryMap[$category['id']] = $new->id;
            }
            foreach ($payload['transactions'] ?? [] as $transaction) {
                if (! isset($accountMap[$transaction['account_id']])) continue;
                $user->transactions()->create([
                    'account_id' => $accountMap[$transaction['account_id']],
                    'destination_account_id' => isset($transaction['destination_account_id']) && $transaction['destination_account_id'] ? ($accountMap[$transaction['destination_account_id']] ?? null) : null,
                    'category_id' => isset($transaction['category_id']) && $transaction['category_id'] ? ($categoryMap[$transaction['category_id']] ?? null) : null,
                    'type' => $transaction['type'], 'amount' => $transaction['amount'],
                    'transaction_date' => $transaction['transaction_date'], 'note' => $transaction['note'] ?? null,
                ]);
            }
            foreach ($payload['debts'] ?? [] as $debtData) {
                $debt = $user->debts()->create(collect($debtData)->only(['type', 'person', 'amount', 'due_date', 'note', 'status', 'settled_at'])->all());
                foreach ($debtData['payments'] ?? [] as $payment) {
                    $debt->payments()->create(['user_id' => $user->id, 'account_id' => isset($payment['account_id']) ? ($accountMap[$payment['account_id']] ?? null) : null, 'amount' => $payment['amount'], 'paid_at' => $payment['paid_at'], 'note' => $payment['note'] ?? null]);
                }
            }
            foreach ($payload['budgets'] ?? [] as $budget) {
                if (isset($categoryMap[$budget['category_id']])) $user->budgets()->create(['category_id' => $categoryMap[$budget['category_id']], 'month' => $budget['month'], 'limit_amount' => $budget['limit_amount']]);
            }
            foreach ($payload['recurring_transactions'] ?? [] as $recurring) {
                if (isset($accountMap[$recurring['account_id']])) $user->recurringTransactions()->create(['account_id' => $accountMap[$recurring['account_id']], 'category_id' => isset($recurring['category_id']) ? ($categoryMap[$recurring['category_id']] ?? null) : null, 'type' => $recurring['type'], 'amount' => $recurring['amount'], 'note' => $recurring['note'] ?? null, 'frequency' => $recurring['frequency'], 'next_run' => $recurring['next_run'], 'is_active' => $recurring['is_active']]);
            }
        });

        return back()->with('success', 'Backup berhasil dipulihkan. Data lama tetap dipertahankan.');
    }
}