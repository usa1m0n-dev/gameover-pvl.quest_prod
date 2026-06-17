<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Guest extends Model {
    protected $guarded = ['id'];
//    protected $casts =[
//        'birthday' => 'date'
//    ];
    public function records()
    {
        return $this->hasMany(Record::class);
    }
}
