<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Record extends Model {
    protected $guarded = ['id'];

    public function guest() { return $this->belongsTo(Guest::class); }
    public function point() { return $this->belongsTo(Point::class); }
    public function room() { return $this->belongsTo(PartyRoom::class); }
    
    // ВАЖНО для Filament Repeater: связь с таблицей record_activities как HasMany
    public function recordActivities() { return $this->hasMany(RecordActivity::class); }
    
    public function setTotalAttribute($val) { $this->attributes['total'] = $val * 100; }
    public function getTotalAttribute($val) { return $val / 100; }
    public function setPrepaidAttribute($val) { $this->attributes['prepaid'] = $val * 100; }
    public function getPrepaidAttribute($val) { return $val / 100; }
}
