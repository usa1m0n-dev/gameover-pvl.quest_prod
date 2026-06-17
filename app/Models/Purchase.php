<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $guarded = ['id'];
    public function point(): BelongsTo
    {
        return $this->belongsTo(Point::class);
    }
}
