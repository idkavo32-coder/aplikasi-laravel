<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'account_id', 'category_id', 'type', 'amount', 'note', 'frequency', 'next_run', 'is_active'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'next_run' => 'date', 'is_active' => 'boolean'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
}
