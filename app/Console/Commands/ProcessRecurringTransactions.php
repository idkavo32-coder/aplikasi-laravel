<?php

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessRecurringTransactions extends Command
{
    protected $signature = 'transactions:recurring';
    protected $description = 'Create transactions whose recurring schedule is due';

    public function handle(): int
    {
        RecurringTransaction::query()->where('is_active', true)->whereDate('next_run', '<=', today())->each(function ($recurring) {
            $runDate = Carbon::parse($recurring->next_run);
            $recurring->user->transactions()->create([
                'account_id' => $recurring->account_id,
                'category_id' => $recurring->category_id,
                'type' => $recurring->type,
                'amount' => $recurring->amount,
                'transaction_date' => $runDate,
                'note' => $recurring->note ?: 'Transaksi berulang',
            ]);
            $recurring->update(['next_run' => $recurring->frequency === 'weekly' ? $runDate->addWeek() : $runDate->addMonth()]);
        });

        $this->info('Transaksi berulang yang jatuh tempo sudah diproses.');
        return self::SUCCESS;
    }
}
