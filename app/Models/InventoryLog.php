<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    use HasFactory;

    protected $table = 'inventories_log';

    protected $fillable = [
        'inventory_id',
        'is_incoming',
        'amount',
        'comment',
        'price',
        'by_employee',
        'employee_id',
        'guest_id',
    ];

    protected $casts = [
        'is_incoming' => 'boolean',
        'by_employee' => 'boolean',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
