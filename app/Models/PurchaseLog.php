<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseLog extends Model
{
    protected $table = 'purchases_log';
    protected $guarded = ['id'];
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    protected function casts(): array
    {
        return [
            'is_expense' => 'boolean',
        ];
    }
}
