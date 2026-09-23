@extends('layouts.app')
@section('content')
<main class="auth-shell"><section class="auth-card">
    <img class="brand-logo" src="{{ asset('images/finora-logo.png') }}" alt="Finora"><div class="auth-intro">
    <h1>Selamat datang</h1><p class="muted">Kelola arus uangmu dengan lebih tenang.</p></div>
    <form method="POST" action="{{ route('login') }}" class="stack">@csrf
        <label>Email<input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
        <label>Password<input type="password" name="password" required></label>
        <label class="check"><input type="checkbox" name="remember"> Ingat saya</label>
        <button class="button button-primary" type="submit">Masuk</button>
    </form>
    <p class="auth-footer">Belum punya akun? <a href="{{ route('register') }}">Buat akun</a></p>
</section></main>
@endsection