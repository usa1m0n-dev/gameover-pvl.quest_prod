<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartyRoom extends Model {
    protected $guarded = ['id'];
    public function point() { return $this->belongsTo(Point::class); }
    
    // Мутаторы денег (копейки <-> рубли)
    public function setPricePerHourAttribute($val) { $this->attributes['price_per_hour'] = $val * 100; }
    public function getPricePerHourAttribute($val) { return $val / 100; }
}
