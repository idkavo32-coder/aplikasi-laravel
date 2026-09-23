<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RecurringTransactionController extends Controller
{
    public function index(Request $request)
    {
        return view('recurring.index', [
            'recurringTransactions' => $request->user()->recurringTransactions()->with(['account', 'category'])->orderBy('next_run')->get(),
            'accounts' => $request->user()->accounts()->where('is_active', true)->get(),
            'categories' => $request->user()->categories()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
            'frequency' => ['required', 'in:weekly,monthly'],
            'next_run' => ['required', 'date'],
        ]);
        abort_unless($request->user()->accounts()->whereKey($data['account_id'])->exists(), 403);
        $request->user()->recurringTransactions()->create($data);

        return back()->with('success', 'Transaksi berulang berhasil disimpan.');
    }

    public function toggle(Request $request, $recurringTransaction)
    {
        $recurring = $request->user()->recurringTransactions()->findOrFail($recurringTransaction);
        $recurring->update(['is_active' => ! $recurring->is_active]);

        return back()->with('success', $recurring->is_active ? 'Jadwal diaktifkan.' : 'Jadwal dijeda.');
    }

    public function destroy(Request $request, $recurringTransaction)
    {
        $request->user()->recurringTransactions()->findOrFail($recurringTransaction)->delete();

        return back()->with('success', 'Jadwal dihapus.');
    }
}
