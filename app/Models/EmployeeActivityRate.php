<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeActivityRate extends Model
{
    protected $guarded = ['id'];

    public function employee() { return $this->belongsTo(Employee::class); }
    public function activity() { return $this->belongsTo(Activity::class); }
}
