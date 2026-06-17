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
        Schema::create('employee_activity_rates', function (Blueprint $table) {
            $table->id();

            // Связи
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();

            // Множитель (например, 1.20)
            $table->decimal('rate_multiplier', 5, 2)->default(1.00);

            $table->timestamps();

            // Опционально: защита от дублей (один сотрудник - одна ставка на игру)
            $table->unique(['employee_id', 'activity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_activity_rates');
    }
};
