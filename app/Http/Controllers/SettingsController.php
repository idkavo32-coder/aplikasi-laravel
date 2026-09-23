<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        return view('settings.index');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
            'currency' => ['required', 'in:IDR,USD,SGD,MYR'],
        ]);
        $request->user()->update($data);

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
