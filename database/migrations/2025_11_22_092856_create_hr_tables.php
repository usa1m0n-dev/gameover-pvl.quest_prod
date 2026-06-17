<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Таблица выплат (Payouts)
        if (!Schema::hasTable('payouts')) {
            Schema::create('payouts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->integer('amount'); // Сумма в копейках
                $table->date('date')->default(now());
                $table->string('comment')->nullable();
                $table->timestamps();
            });
        }

        // 2. Таблица связи Сотрудник <-> Игра (Employee Record Activity)
        // Мы создаем её сразу с полем wage (зарплата)
        if (!Schema::hasTable('employee_record_activities')) {
            Schema::create('employee_record_activities', function (Blueprint $table) {
                $table->id();

                // Связь с сотрудником
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

                // Связь с конкретной игрой в записи (record_activities)
                // Важно: ссылаемся на таблицу record_activities, а не records!
                $table->foreignId('record_activity_id')
                    ->constrained('record_activities')
                    ->cascadeOnDelete();

                $table->integer('role')->default(1); // Роль (1=Ведущий, 2=Помощник)
                $table->integer('wage')->default(0); // Зарплата за эту игру (фиксированная)

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_record_activities');
        Schema::dropIfExists('payouts');
    }
};
