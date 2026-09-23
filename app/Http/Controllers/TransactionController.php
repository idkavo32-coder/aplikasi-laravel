<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function edit(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        return view('transactions.edit', [
            'transaction' => $transaction,
            'accounts' => $request->user()->accounts()->where('is_active', true)->get(),
            'categories' => $request->user()->categories()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:income,expense,transfer'],
            'amount' => ['required', 'integer', 'min:1'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'destination_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'transaction_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $user = $request->user();

        abort_unless($user->accounts()->whereKey($data['account_id'])->exists(), 403);

        if ($data['type'] === 'transfer') {
            $request->validate(['destination_account_id' => ['required', 'different:account_id']]);
            abort_unless($user->accounts()->whereKey($data['destination_account_id'])->exists(), 403);
        } else {
            $data['destination_account_id'] = null;
        }

        if (! empty($data['category_id'])) {
            abort_unless($user->categories()->whereKey($data['category_id'])->exists(), 403);
        }

        DB::transaction(fn () => $user->transactions()->create($data));

        return back()->with('success', 'Transaksi berhasil ditambahkan.');
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);
        DB::transaction(fn () => $transaction->delete());

        return back()->with('success', 'Transaksi dihapus.');
    }

    public function update(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'type' => ['required', 'in:income,expense,transfer'],
            'amount' => ['required', 'integer', 'min:1'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'destination_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'transaction_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $user = $request->user();

        abort_unless($user->accounts()->whereKey($data['account_id'])->exists(), 403);

        if ($data['type'] === 'transfer') {
            $request->validate(['destination_account_id' => ['required', 'different:account_id']]);
            abort_unless($user->accounts()->whereKey($data['destination_account_id'])->exists(), 403);
        } else {
            $data['destination_account_id'] = null;
        }

        if (! empty($data['category_id'])) {
            abort_unless($user->categories()->whereKey($data['category_id'])->exists(), 403);
        }

        $transaction->update($data);

        return redirect()->route('dashboard')->with('success', 'Transaksi berhasil diperbarui.');
    }
}