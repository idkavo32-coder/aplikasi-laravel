<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        return view('accounts.index', [
            'accounts' => $request->user()->accounts()->where('is_active', true)->get(),
        ]);
    }

    public function edit(Request $request, Account $account)
    {
        abort_unless($account->user_id === $request->user()->id, 403);

        return view('accounts.edit', compact('account'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'custom_name' => ['nullable', 'string', 'max:80'],
            'type' => ['required', 'in:cash,bank,e_wallet,custom'],
            'opening_balance' => ['required', 'integer', 'min:0'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        if ($data['name'] === 'custom') {
            $customName = trim((string) ($data['custom_name'] ?? ''));
            abort_if($customName === '', 422, 'Nama akun custom wajib diisi.');
            $data['name'] = $customName;
        }

        unset($data['custom_name']);
        $request->user()->accounts()->create($data);

        return back()->with('success', 'Akun berhasil ditambahkan.');
    }

    public function destroy(Request $request, Account $account)
    {
        abort_unless($account->user_id === $request->user()->id, 403);
        abort_if($request->user()->accounts()->where('is_active', true)->count() <= 1, 422, 'Akun terakhir tidak dapat dihapus.');

        $account->delete();

        return back()->with('success', 'Akun berhasil dihapus.');
    }

    public function update(Request $request, Account $account)
    {
        abort_unless($account->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'type' => ['required', 'in:cash,bank,e_wallet,custom'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
        $account->update($data);

        return redirect()->route('more')->with('success', 'Akun berhasil diperbarui.');
    }
}