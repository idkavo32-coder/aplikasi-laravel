<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $budgets = $request->user()->budgets()->with('category')->where('month', $month)->get();
        $spent = $request->user()->transactions()->where('type', 'expense')->whereBetween('transaction_date', [$month.'-01', $month.'-31'])->get()->groupBy('category_id')->map(fn ($items) => $items->sum('amount'));

        return view('budgets.index', [
            'budgets' => $budgets,
            'spent' => $spent,
            'month' => $month,
            'categories' => $request->user()->categories()->where('type', 'expense')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'month' => ['required', 'date_format:Y-m'],
            'limit_amount' => ['required', 'integer', 'min:1'],
        ]);
        abort_unless($request->user()->categories()->whereKey($data['category_id'])->exists(), 403);
        $request->user()->budgets()->updateOrCreate(
            ['category_id' => $data['category_id'], 'month' => $data['month']],
            ['limit_amount' => $data['limit_amount']],
        );

        return back()->with('success', 'Anggaran berhasil disimpan.');
    }

    public function destroy(Request $request, Budget $budget)
    {
        abort_unless($budget->user_id === $request->user()->id, 403);
        $budget->delete();

        return back()->with('success', 'Anggaran berhasil dihapus.');
    }
}
