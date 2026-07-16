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
        Schema::create('fast_entries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // Опционально
            $table->string('phone'); // Обязательно (уже отформатированный +7xxxxxxxxxx)
            $table->date('visit_date')->nullable(); // Опционально

            // Кто именно из операторов внес запись (если у вас есть авторизация)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            // Индексы для быстродействия при миллионах записей
            $table->index('phone');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fast_entries');
    }
};
