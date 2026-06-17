<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RecordActivity extends Model {
    protected $guarded = ['id'];
    protected $table = 'record_activities';

    public function activity() { return $this->belongsTo(Activity::class); }
    public function record() { return $this->belongsTo(Record::class); }

    public function setFixedPriceAttribute($val) { $this->attributes['fixed_price'] = $val * 100; }
    public function getFixedPriceAttribute($val) { return $val / 100; }
    public function employeeRecordActivities() {
        return $this->hasMany(EmployeeRecordActivity::class);
    }
}
