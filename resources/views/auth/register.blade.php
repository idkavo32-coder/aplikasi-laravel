@extends('layouts.app')
@section('content')
<main class="auth-shell"><section class="auth-card">
    <img class="brand-logo" src="{{ asset('images/finora-logo.png') }}" alt="Finora"><p class="eyebrow">MULAI DENGAN RAPI</p>
    <h1>Bangun kebiasaan finansial yang lebih baik.</h1><p class="muted">Akun pertamamu jadi titik awal yang nyata.</p>
    <form method="POST" action="{{ route('register') }}" class="stack">@csrf
        <label>Nama<input name="name" value="{{ old('name') }}" required></label>
        <label>Email<input type="email" name="email" value="{{ old('email') }}" required></label>
        <label>Password<input type="password" name="password" required></label>
        <label>Ulangi password<input type="password" name="password_confirmation" required></label>
        <div class="form-grid"><label>Nama akun<input name="account_name" value="{{ old('account_name', 'Dompet') }}" required></label><label>Mata uang<select name="currency"><option value="IDR">IDR</option><option value="USD">USD</option><option value="SGD">SGD</option><option value="MYR">MYR</option></select></label></div>
        <label>Saldo awal<input type="number" name="opening_balance" value="{{ old('opening_balance', 0) }}" min="0" required></label>
        <button class="button button-primary" type="submit">Buat akun</button>
    </form>
    <p class="auth-footer">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
</section></main>
@endsection