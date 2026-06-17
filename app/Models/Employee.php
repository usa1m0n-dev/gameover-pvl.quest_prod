<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Employee extends Model {
    protected $guarded = ['id'];


    public function record_activities() {
        return $this->hasMany(EmployeeRecordActivity::class);
    }
    public function shifts() {
        return $this->hasMany(EmployeeRecordActivity::class);
    }

    // Выплаты, которые он получил
    public function payouts() {
        return $this->hasMany(Payout::class);
    }

    // Персональные множители (Вася получает x1.2 за Аниматора)
    public function rates() {
        return $this->hasMany(EmployeeActivityRate::class);
    }

    // Виртуальный атрибут: Баланс (Сколько мы должны)
    public function getBalanceAttribute() {
        $earned = $this->shifts()->sum('wage'); // Заработал
        $paid = $this->payouts()->sum('amount'); // Получил
        return ($earned - $paid) / 100; // В валюте
    }

}
