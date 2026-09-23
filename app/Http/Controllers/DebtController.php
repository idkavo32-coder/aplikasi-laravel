<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $debts = $request->user()->debts()
            ->with('payments.account')
            ->orderByRaw("CASE WHEN status = 'unpaid' THEN 0 ELSE 1 END")
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->get();

        return view('debts.index', [
            'debts' => $debts,
            'accounts' => $request->user()->accounts()->where('is_active', true)->get(),
            'unpaidCount' => $debts->where('status', 'unpaid')->count(),
            'unpaidTotal' => $debts->where('status', 'unpaid')->sum('amount'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:debt,receivable'],
            'person' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'integer', 'min:1'],
            'due_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $request->user()->debts()->create($data);

        return back()->with('success', 'Hutang atau piutang berhasil dicatat.');
    }

    public function settle(Request $request, Debt $debt)
    {
        abort_unless($debt->user_id === $request->user()->id, 403);

        if ($debt->remaining_amount > 0) {
            $debt->payments()->create([
                'user_id' => $request->user()->id,
                'amount' => $debt->remaining_amount,
                'paid_at' => now()->toDateString(),
                'note' => 'Ditandai lunas',
            ]);
        }

        $debt->update(['status' => 'settled', 'settled_at' => now()]);

        return back()->with('success', 'Catatan ditandai sudah lunas.');
    }

    public function payment(Request $request, Debt $debt)
    {
        abort_unless($debt->user_id === $request->user()->id, 403);

        $remaining = $debt->remaining_amount;
        abort_if($remaining <= 0, 422, 'Catatan ini sudah lunas.');

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:'.$remaining],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'paid_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        abort_unless($request->user()->accounts()->whereKey($data['account_id'])->exists(), 403);

        DB::transaction(function () use ($request, $debt, $data) {
            $debt->payments()->create([
                'user_id' => $request->user()->id,
                ...$data,
            ]);
            $request->user()->transactions()->create([
                'account_id' => $data['account_id'],
                'type' => $debt->type === 'debt' ? 'expense' : 'income',
                'amount' => $data['amount'],
                'transaction_date' => $data['paid_at'],
                'note' => 'Cicilan '.($debt->type === 'debt' ? 'hutang' : 'piutang').' - '.$debt->person,
            ]);
            if ($debt->remaining_amount <= 0) {
                $debt->update(['status' => 'settled', 'settled_at' => now()]);
            }
        });

        return back()->with('success', 'Cicilan berhasil dicatat.');
    }

    public function destroy(Request $request, Debt $debt)
    {
        abort_unless($debt->user_id === $request->user()->id, 403);

        $debt->delete();

        return back()->with('success', 'Catatan berhasil dihapus.');
    }
}
