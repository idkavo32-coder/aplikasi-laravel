<?php

namespace App\Http\Controllers;

use App\Models\SavingsGoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavingsGoalController extends Controller
{
    public function index(Request $request)
    {
        return view('goals.index', [
            'goals' => $request->user()->savingsGoals()->withCount('savingsTransactions')->latest()->get(),
            'accounts' => $request->user()->accounts()->where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'target_amount' => ['required', 'integer', 'min:1'],
            'deadline' => ['nullable', 'date'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
        $request->user()->savingsGoals()->create($data);

        return back()->with('success', 'Target tabungan berhasil dibuat.');
    }

    public function contribute(Request $request, SavingsGoal $goal)
    {
        abort_unless($goal->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'type' => ['required', 'in:deposit,withdrawal'],
            'amount' => ['required', 'integer', 'min:1'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'destination_account_id' => ['required', 'integer', 'exists:accounts,id', 'different:account_id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        abort_unless($request->user()->accounts()->whereKey($data['account_id'])->exists(), 403);
        abort_unless($request->user()->accounts()->whereKey($data['destination_account_id'])->exists(), 403);

        DB::transaction(function () use ($goal, $data, $request) {
            $goal = SavingsGoal::query()->whereKey($goal->id)->lockForUpdate()->firstOrFail();
            $newAmount = $data['type'] === 'deposit'
                ? $goal->saved_amount + $data['amount']
                : $goal->saved_amount - $data['amount'];
            abort_if($newAmount < 0, 422, 'Pengurangan melebihi tabungan yang terkumpul.');
            if ($data['type'] === 'deposit') {
                $account = $request->user()->accounts()->findOrFail($data['account_id']);
                abort_if(app(\App\Services\AccountBalanceService::class)->for($account) < $data['amount'], 422, 'Saldo akun tidak cukup untuk setoran ini.');
            }

            $goal->update(['saved_amount' => $newAmount]);
            $goal->savingsTransactions()->create([
                'user_id' => $request->user()->id,
                'account_id' => $data['account_id'],
                'destination_account_id' => $data['destination_account_id'],
                'type' => $data['type'],
                'amount' => $data['amount'],
                'transaction_date' => now()->toDateString(),
                'note' => $data['note'] ?? null,
            ]);
            $request->user()->transactions()->create([
                'account_id' => $data['account_id'],
                'destination_account_id' => $data['destination_account_id'],
                'type' => 'transfer',
                'amount' => $data['amount'],
                'transaction_date' => now()->toDateString(),
                'note' => ($data['type'] === 'deposit' ? 'Setoran tabungan - ' : 'Penarikan tabungan - ').$goal->name,
            ]);
        });

        return back()->with('success', 'Tabungan target berhasil diperbarui.');
    }
}