<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="application-name" content="Finora">
    <link rel="icon" type="image/png" href="{{ asset('images/finora-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/finora-logo.png') }}">
    <title>{{ $title ?? 'Finora' }} · Finora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/transactions.css') }}">
    <link rel="stylesheet" href="{{ asset('css/branding.css') }}">
    @if(request()->routeIs('login', 'register')) <link rel="stylesheet" href="{{ asset('css/auth-branding.css') }}"> @endif
    @if(request()->routeIs('dashboard')) <link rel="stylesheet" href="{{ asset('css/dashboard-insights.css') }}"> @endif
    @if(request()->routeIs('dashboard')) <link rel="stylesheet" href="{{ asset('css/due-notice.css') }}"> @endif
    @if(request()->routeIs('dashboard')) <link rel="stylesheet" href="{{ asset('css/dashboard-simple.css') }}"> @endif
    @if(request()->routeIs('dashboard')) <link rel="stylesheet" href="{{ asset('css/dashboard-activity.css') }}"> @endif
    @if(request()->routeIs('dashboard')) <link rel="stylesheet" href="{{ asset('css/dashboard-activity-scroll.css') }}"> @endif
    @if(request()->routeIs('more', 'accounts')) <link rel="stylesheet" href="{{ asset('css/accounts.css') }}"> @endif
    @if(request()->routeIs('reports')) <link rel="stylesheet" href="{{ asset('css/reports.css') }}"> @endif
    @if(request()->routeIs('reports')) <link rel="stylesheet" href="{{ asset('css/report-filters.css') }}"> @endif
    @if(request()->routeIs('goals', 'more', 'accounts', 'categories')) <link rel="stylesheet" href="{{ asset('css/goals.css') }}"> @endif
    @if(request()->routeIs('debts')) <link rel="stylesheet" href="{{ asset('css/debts.css') }}"> @endif
    @if(request()->routeIs('debts')) <link rel="stylesheet" href="{{ asset('css/debt-payments.css') }}"> @endif
    @if(request()->routeIs('budgets')) <link rel="stylesheet" href="{{ asset('css/budgets.css') }}"> @endif
    @if(request()->routeIs('recurring')) <link rel="stylesheet" href="{{ asset('css/recurring.css') }}"> @endif
    @if(request()->routeIs('more')) <link rel="stylesheet" href="{{ asset('css/backup.css') }}"> @endif
    @if(request()->routeIs('more', 'accounts')) <link rel="stylesheet" href="{{ asset('css/account-edit.css') }}"> @endif
    @if(request()->routeIs('more', 'categories')) <link rel="stylesheet" href="{{ asset('css/category-manage.css') }}"> @endif
    @if(request()->routeIs('more')) <link rel="stylesheet" href="{{ asset('css/more-polish.css') }}"> @endif
    @if(request()->routeIs('more', 'accounts', 'categories')) <link rel="stylesheet" href="{{ asset('css/more-compact.css') }}"> @endif
    @if(request()->routeIs('more')) <link rel="stylesheet" href="{{ asset('css/more-settings.css') }}"> @endif
    @if(request()->routeIs('more')) <link rel="stylesheet" href="{{ asset('css/more-settings-label.css') }}"> @endif
    @if(request()->routeIs('more')) <link rel="stylesheet" href="{{ asset('css/profile-settings.css') }}"> @endif
    @if(request()->routeIs('more')) <link rel="stylesheet" href="{{ asset('css/category-dropdown.css') }}"> @endif
    @if(request()->routeIs('more')) <link rel="stylesheet" href="{{ asset('css/account-dropdown.css') }}"> @endif
    @if(request()->routeIs('settings')) <link rel="stylesheet" href="{{ asset('css/settings-page.css') }}"> @endif
</head>
<body>
    @yield('content')
    @if(session('success')) <div class="toast">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="toast toast-error">{{ $errors->first() }}</div> @endif
    @stack('scripts')
</body>
</html>