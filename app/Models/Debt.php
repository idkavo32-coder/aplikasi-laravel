<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Debt extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'person', 'amount', 'due_date', 'note', 'status', 'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'due_date' => 'date',
            'settled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function getPaidAmountAttribute(): int
    {
        $paidAmount = $this->relationLoaded('payments')
            ? (int) $this->payments->sum('amount')
            : (int) $this->payments()->sum('amount');

        return $this->status === 'settled' ? max($paidAmount, $this->amount) : $paidAmount;
    }

    public function getRemainingAmountAttribute(): int
    {
        return max(0, $this->amount - $this->paid_amount);
    }
}
