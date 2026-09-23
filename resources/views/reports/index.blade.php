@extends('layouts.app')
@section('content')
<div class="app-shell">
    <header class="topbar"><div><p class="eyebrow">ANALISIS KEUANGAN</p><h1>Laporan</h1></div><a class="avatar avatar-link" href="{{ route('dashboard') }}">⌂</a></header>
    <main class="content">
        <form class="period-bar" method="GET" action="{{ route('reports') }}"><select name="period" onchange="this.form.submit()"><option value="today" @selected($period === 'today')>Hari ini</option><option value="week" @selected($period === 'week')>Minggu ini</option><option value="month" @selected($period === 'month')>Bulan ini</option><option value="year" @selected($period === 'year')>Tahun ini</option><option value="custom" @selected($period === 'custom')>Custom</option></select>@if($period === 'custom')<input type="date" name="start_date" value="{{ request('start_date', $start->toDateString()) }}"><span>sampai</span><input type="date" name="end_date" value="{{ request('end_date', $end->toDateString()) }}"><button class="button button-small" type="submit">Terapkan</button>@endif</form>
        <form class="report-filters" method="GET" action="{{ route('reports') }}"><input type="hidden" name="period" value="{{ $period }}"><input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari catatan atau akun"><select name="type"><option value="">Semua jenis</option><option value="income" @selected(($filters['type'] ?? '') === 'income')>Pemasukan</option><option value="expense" @selected(($filters['type'] ?? '') === 'expense')>Pengeluaran</option><option value="transfer" @selected(($filters['type'] ?? '') === 'transfer')>Transfer</option></select><select name="account_id"><option value="">Semua akun</option>@foreach($filterAccounts as $account)<option value="{{ $account->id }}" @selected((string) ($filters['account_id'] ?? '') === (string) $account->id)>{{ $account->name }}</option>@endforeach</select><button class="button button-small" type="submit">Filter</button><a class="button button-small button-quiet" href="{{ route('reports.export', request()->query()) }}">Export CSV</a></form>
        <p class="report-range">{{ $start->translatedFormat('d M Y') }} - {{ $end->translatedFormat('d M Y') }}</p>
        <section class="report-hero"><p class="eyebrow light">CASH FLOW BERSIH</p><strong>{{ $net >= 0 ? '+' : '' }}{{ number_format($net, 0, ',', '.') }} <small>{{ auth()->user()->currency }}</small></strong><p>{{ $net >= 0 ? 'Arus uangmu positif pada periode ini.' : 'Pengeluaran lebih besar dari pemasukan.' }}</p></section>
        <section class="summary-grid report-summary"><article><span class="summary-icon income">↗</span><p>Pemasukan</p><strong>{{ number_format($income, 0, ',', '.') }}</strong></article><article><span class="summary-icon expense">↘</span><p>Pengeluaran</p><strong>{{ number_format($expense, 0, ',', '.') }}</strong></article></section>
        <section class="report-section"><div class="section-heading"><div><p class="eyebrow">PERGERAKAN HARIAN</p><h2>Cash flow</h2></div><span class="report-count">{{ $dailyFlow->count() }} hari aktif</span></div>
            @if($dailyFlow->isNotEmpty())
                <div class="flow-chart">@foreach($dailyFlow as $date => $day)<div class="flow-day"><div class="flow-bars"><span class="bar income-bar" style="height: {{ max(6, ($day['income'] / $maxDaily) * 100) }}%"></span><span class="bar expense-bar" style="height: {{ max(6, ($day['expense'] / $maxDaily) * 100) }}%"></span></div><small>{{ \Carbon\Carbon::parse($date)->format('d/m') }}</small></div>@endforeach</div><div class="chart-legend"><span><i class="income-dot"></i>Pemasukan</span><span><i class="expense-dot"></i>Pengeluaran</span></div>
            @else
                <div class="empty-state">Belum ada transaksi pada periode ini.</div>
            @endif
        </section>
        <section class="report-section"><div class="section-heading"><div><p class="eyebrow">PENGELUARAN</p><h2>Berdasarkan kategori</h2></div></div>
            @forelse($categoryExpenses as $category => $amount)
                <div class="category-line"><div><strong>{{ $category }}</strong><small>{{ number_format($amount, 0, ',', '.') }}</small></div><div class="progress-track"><span style="width: {{ ($amount / $maxCategory) * 100 }}%"></span></div></div>
            @empty
                <div class="empty-state">Belum ada pengeluaran berkategori.</div>
            @endforelse
        </section>
        <section class="report-section"><div class="section-heading"><div><p class="eyebrow">AKUN</p><h2>Saldo terbesar</h2></div></div>
            @forelse($accounts as $account)
                <div class="account-row report-account"><div><strong>{{ $account['name'] }}</strong><small>Saldo saat ini</small></div><b>{{ number_format($account['balance'], 0, ',', '.') }}</b></div>
            @empty
                <div class="empty-state">Belum ada akun aktif.</div>
            @endforelse
            @if($transfer > 0)
                <p class="transfer-note">Transfer antar akun: {{ number_format($transfer, 0, ',', '.') }}. Tidak dihitung sebagai pemasukan atau pengeluaran.</p>
            @endif
        </section>
    </main>
    <nav class="bottom-nav"><a href="{{ route('dashboard') }}"><span>⌂</span>Home</a><a class="active" href="{{ route('reports') }}"><span>◒</span>Laporan</a><a class="nav-plus" href="{{ route('dashboard') }}#transaction-sheet">+</a><a href="{{ route('goals') }}"><span>◎</span>Target</a><a href="{{ route('more') }}"><span>•••</span>Pengaturan</a></nav>
</div>
@endsection