<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeychainPending extends Model
{
    protected $table = 'keychain_pending';
    protected $guarded=[];

    protected function point()
    {
        return $this->belongsTo(Point::class, 'point_id');
    }
     protected function guest()
     {
         return $this->belongsTo(Guest::class, 'guest_id');
     }
}
