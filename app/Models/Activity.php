<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Activity extends Model {
    protected $guarded = ['id'];
    public function setBasePriceAttribute($val) { $this->attributes['base_price'] = $val * 100; }
    public function getBasePriceAttribute($val) { return $val / 100; }
}
