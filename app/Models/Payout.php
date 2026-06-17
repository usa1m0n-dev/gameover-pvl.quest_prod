<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $guarded = ['id'];
    public function employee() { return $this->belongsTo(Employee::class); }
    public function setAmountAttribute($val) { $this->attributes['amount'] = $val * 100; }
    public function getAmountAttribute($val) { return $val / 100; }
}
