<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FastEntry extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'visit_date',
        'user_id',
    ];

    // Приведение типов для удобства работы с датами
    protected $casts = [
        'visit_date' => 'date',
    ];

    /**
     * Связь с оператором, который внес данные
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
