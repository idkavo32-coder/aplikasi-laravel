<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavingsGoalController;
use App\Http\Controllers\MoreController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SettingsController;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/reports', ReportController::class)->name('reports');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/goals', [SavingsGoalController::class, 'index'])->name('goals');
    Route::post('/goals', [SavingsGoalController::class, 'store'])->name('goals.store');
    Route::post('/goals/{goal}/contributions', [SavingsGoalController::class, 'contribute'])->name('goals.contribute');
    Route::get('/debts', [DebtController::class, 'index'])->name('debts');
    Route::post('/debts', [DebtController::class, 'store'])->name('debts.store');
    Route::patch('/debts/{debt}/settle', [DebtController::class, 'settle'])->name('debts.settle');
    Route::post('/debts/{debt}/payments', [DebtController::class, 'payment'])->name('debts.payment');
    Route::delete('/debts/{debt}', [DebtController::class, 'destroy'])->name('debts.destroy');
    Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets');
    Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
    Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');
    Route::get('/recurring', [RecurringTransactionController::class, 'index'])->name('recurring');
    Route::post('/recurring', [RecurringTransactionController::class, 'store'])->name('recurring.store');
    Route::patch('/recurring/{recurringTransaction}/toggle', [RecurringTransactionController::class, 'toggle'])->name('recurring.toggle');
    Route::delete('/recurring/{recurringTransaction}', [RecurringTransactionController::class, 'destroy'])->name('recurring.destroy');
    Route::get('/more', MoreController::class)->name('more');
    Route::get('/backup', [MoreController::class, 'backup'])->name('backup');
    Route::post('/backup/restore', [MoreController::class, 'restore'])->name('backup.restore');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts');
    Route::get('/accounts/{account}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
    Route::put('/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
    Route::delete('/accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
});
