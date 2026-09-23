<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\DB;

class AccountBalanceService
{
    public function for(Account $account): int
    {
        $outgoing = $account->transactions()->whereIn('type', ['expense', 'transfer'])->sum('amount');
        $income = $account->transactions()->where('type', 'income')->sum('amount');
        $incomingTransfers = $account->destinationTransactions()->where('type', 'transfer')->sum('amount');

        return $account->opening_balance + $income + $incomingTransfers - $outgoing;
    }

    public function totalForUser(int $userId): int
    {
        return Account::query()
            ->where('user_id', $userId)
            ->get()
            ->sum(fn (Account $account) => $this->for($account));
    }
}