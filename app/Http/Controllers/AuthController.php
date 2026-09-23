<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau password tidak cocok.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'account_name' => ['required', 'string', 'max:80'],
            'opening_balance' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'in:IDR,USD,SGD,MYR'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'currency' => $data['currency'],
                'onboarding_complete' => true,
            ]);

            $user->accounts()->create([
                'name' => $data['account_name'],
                'opening_balance' => $data['opening_balance'],
                'type' => 'cash',
            ]);

            foreach ([
                ['name' => 'Gaji', 'type' => 'income', 'icon' => 'briefcase'],
                ['name' => 'Bonus', 'type' => 'income', 'icon' => 'sparkles'],
                ['name' => 'Makanan', 'type' => 'expense', 'icon' => 'utensils'],
                ['name' => 'Transportasi', 'type' => 'expense', 'icon' => 'car'],
                ['name' => 'Tagihan', 'type' => 'expense', 'icon' => 'receipt'],
                ['name' => 'Lainnya', 'type' => 'expense', 'icon' => 'circle'],
            ] as $category) {
                $user->categories()->create($category);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}