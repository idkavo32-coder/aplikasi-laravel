@extends('layouts.app')
@section('content')
<div class="app-shell">
    <header class="topbar"><div><p class="eyebrow">{{ now()->translatedFormat('l, d F Y') }}</p><h1>Halo, {{ Str::before(auth()->user()->name, ' ') }}.</h1></div><div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div></header>
    <main class="content">
        <section class="balance-card"><div class="eyebrow light">TOTAL SALDO</div><div class="balance">{{ number_format($totalBalance, 0, ',', '.') }} <small>{{ auth()->user()->currency }}</small></div><div class="balance-caption">di {{ $accounts->count() }} akun aktif</div></section>
        <section class="summary-grid"><article><span class="summary-icon income">↗</span><p>Pemasukan</p><strong>{{ number_format($incomeThisMonth, 0, ',', '.') }}</strong><small>Bulan ini</small></article><article><span class="summary-icon expense">↘</span><p>Pengeluaran</p><strong>{{ number_format($expenseThisMonth, 0, ',', '.') }}</strong><small>Bulan ini</small></article></section><section class="insight-strip"><a href="{{ route('debts') }}"><span>Hutang belum lunas</span><strong>{{ number_format($unpaidDebtTotal, 0, ',', '.') }}</strong></a><a href="{{ route('budgets') }}"><span>Anggaran bulan ini</span><strong>{{ $activeBudgetCount }} kategori</strong></a></section>@if($dueSoonDebts->isNotEmpty())<a class="due-notice" href="{{ route('debts') }}"><strong>Jatuh tempo minggu ini</strong><span>{{ $dueSoonDebts->first()->person }}{{ $dueSoonDebts->count() > 1 ? ' dan '.($dueSoonDebts->count() - 1).' lainnya' : '' }}</span></a>@endif
        <section class="section-heading"><div><p class="eyebrow">AKTIVITAS</p><h2>Transaksi terbaru</h2></div><a class="text-link" href="{{ route('reports') }}">Lihat laporan</a></section>
        <section class="transaction-list dashboard-transaction-list">@forelse($recentTransactions as $transaction)<a class="transaction-row dashboard-transaction-row transaction-link" href="{{ route('transactions.edit', $transaction) }}"><span class="transaction-icon {{ $transaction->type }}">{{ $transaction->type === 'income' ? '↗' : ($transaction->type === 'expense' ? '↘' : '⇄') }}</span><div><strong>{{ $transaction->note ?: ucfirst($transaction->type) }}</strong><small>{{ $transaction->category?->name ?: ($transaction->type === 'transfer' ? $transaction->account->name.' → '.$transaction->destinationAccount?->name : $transaction->account->name) }} · {{ $transaction->transaction_date->format('d M') }}</small></div><b class="amount-{{ $transaction->type }}">{{ $transaction->type === 'income' ? '+' : '-' }}{{ number_format($transaction->amount, 0, ',', '.') }}</b></a>@empty<div class="empty-state">Belum ada transaksi.<br><span>Tambahkan pemasukan atau pengeluaran pertama.</span></div>@endforelse</section>
    </main>
    <button class="fab" type="button" onclick="document.querySelector('#transaction-sheet').showModal()">+</button>
    <nav class="bottom-nav"><a class="active" href="{{ route('dashboard') }}"><span>⌂</span>Home</a><a href="{{ route('reports') }}"><span>◒</span>Laporan</a><a class="nav-plus" href="#" onclick="document.querySelector('#transaction-sheet').showModal(); return false;">+</a><a href="{{ route('goals') }}"><span>◎</span>Target</a><a href="{{ route('more') }}"><span>•••</span>Pengaturan</a></nav>
</div>
<dialog id="transaction-sheet" class="sheet"><form method="POST" action="{{ route('transactions.store') }}" class="sheet-content">@csrf<button type="button" class="sheet-close" onclick="this.closest('dialog').close()">×</button><p class="eyebrow">TRANSAKSI BARU</p><h2>Catat arus uang</h2><label>Jenis<select name="type" id="transaction-type"><option value="expense">Pengeluaran</option><option value="income">Pemasukan</option><option value="transfer">Transfer</option></select></label><label>Nominal<input type="number" name="amount" min="1" required placeholder="0"></label><label>Dari akun<select name="account_id" required>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></label><label id="destination-field">Ke akun<select name="destination_account_id">@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></label><label>Kategori<select name="category_id"><option value="">Tanpa kategori</option>@foreach(auth()->user()->categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></label><label>Tanggal<input type="date" name="transaction_date" value="{{ now()->format('Y-m-d') }}" required></label><label>Catatan<input name="note" maxlength="255" placeholder="Contoh: makan siang"></label><button class="button button-primary" type="submit">Simpan transaksi</button></form></dialog>
@endsection
@push('scripts')
<script>
    const type = document.querySelector('#transaction-type');
    const accountSelect = document.querySelector('select[name="account_id"]');
    const accountLabel = accountSelect.closest('label');
    const destination = document.querySelector('#destination-field');

    function toggleTransactionFields() {
        destination.hidden = type.value !== 'transfer';
        destination.style.display = type.value === 'transfer' ? 'grid' : 'none';
        accountLabel.firstChild.textContent = type.value === 'income' ? 'Masuk ke akun' : 'Dari akun';
    }

    type.addEventListener('change', toggleTransactionFields);
    toggleTransactionFields();
</script>
@endpush