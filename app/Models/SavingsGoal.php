<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsGoal extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'target_amount', 'saved_amount', 'deadline', 'color', 'is_active'];

    protected function casts(): array
    {
        return ['target_amount' => 'integer', 'saved_amount' => 'integer', 'deadline' => 'date', 'is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function savingsTransactions(): HasMany
    {
        return $this->hasMany(SavingsTransaction::class);
    }

    public function getProgressAttribute(): int
    {
        return $this->target_amount > 0 ? min(100, (int) round(($this->saved_amount / $this->target_amount) * 100)) : 0;
    }
}