@extends('layouts.app')
@section('content')
<div class="app-shell">
    <header class="topbar"><div><p class="eyebrow">KEUANGAN PRIBADI</p><h1>Hutang & Piutang</h1></div><a class="avatar avatar-link" href="{{ route('dashboard') }}">⌂</a></header>
    <main class="content">
        <section class="debt-summary"><article><small>Belum lunas</small><strong>{{ $unpaidCount }} catatan</strong></article><article><small>Total berjalan</small><strong>{{ number_format($unpaidTotal, 0, ',', '.') }}</strong></article></section>
        <section class="debt-list">
            @forelse($debts as $debt)
                @include('debts._card', ['debt' => $debt])
            @empty
                <div class="empty-state debt-empty"><strong>Belum ada hutang atau piutang.</strong><br><span>Catat pinjaman supaya jatuh tempo dan pelunasannya tidak terlewat.</span></div>
            @endforelse
        </section>
        <button class="button button-primary full-button" type="button" onclick="document.querySelector('#new-debt').showModal()">+ Catat hutang atau piutang</button>
    </main>
    <nav class="bottom-nav"><a href="{{ route('dashboard') }}"><span>⌂</span>Home</a><a href="{{ route('reports') }}"><span>◒</span>Laporan</a><a class="nav-plus" href="{{ route('dashboard') }}#transaction-sheet">+</a><a href="{{ route('goals') }}"><span>◎</span>Target</a><a class="active" href="{{ route('more') }}"><span>•••</span>Pengaturan</a></nav>
</div>
<dialog id="new-debt" class="sheet"><form method="POST" action="{{ route('debts.store') }}" class="sheet-content">@csrf<button type="button" class="sheet-close" onclick="this.closest('dialog').close()">×</button><p class="eyebrow">CATATAN BARU</p><h2>Hutang atau piutang</h2><label>Jenis<select name="type"><option value="debt">Hutang saya</option><option value="receivable">Piutang saya</option></select></label><label>Nama orang / pihak<input name="person" maxlength="80" required placeholder="Contoh: Rina atau Toko ABC"></label><label>Nominal<input type="number" name="amount" min="1" required placeholder="0"></label><label>Jatuh tempo (opsional)<input type="date" name="due_date"></label><label>Catatan<input name="note" maxlength="255" placeholder="Contoh: pinjaman untuk biaya kos"></label><button class="button button-primary" type="submit">Simpan catatan</button></form></dialog>
@endsection
