<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeRecordActivity extends Model
{
    protected $guarded = ['id'];

    // Указываем таблицу явно, чтобы Laravel не искал singular версию
    protected $table = 'employee_record_activities';

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function recordActivity()
    {
        return $this->belongsTo(RecordActivity::class);
    }

    // Мутатор для ЗП (копейки <-> тенге)
    public function setWageAttribute($val) { $this->attributes['wage'] = $val * 100; }
    public function getWageAttribute($val) { return $val / 100; }
}
