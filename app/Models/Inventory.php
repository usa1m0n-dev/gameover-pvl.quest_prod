<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sell_price',
        'amount',
    ];

    // Связь с логами
    public function logs(): HasMany
    {
        return $this->hasMany(InventoryLog::class, 'inventory_id');
    }

    public function point(): BelongsTo{
        return $this->belongsTo(Point::class, 'point_id');
    }
}
