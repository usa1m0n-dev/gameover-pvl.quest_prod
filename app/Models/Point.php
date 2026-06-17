<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Point extends Model {
    protected $guarded = ['id'];
    public function rooms() { return $this->hasMany(PartyRoom::class); }
}
