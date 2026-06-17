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
    // Таблица записей
    Schema::table('records', function (Blueprint $table) {
        $table->text('comment')->nullable(); // Большой комментарий
        $table->string('note')->nullable();    // Короткая заметка для списка
    });

    // Таблица связка (игры внутри записи)
    Schema::table('record_activities', function (Blueprint $table) {
        // Уровень сложности (light, medium, hard, ultra_hard)
        $table->string('difficulty_level')->nullable();
    });
}

public function down(): void
{
    Schema::table('records', function (Blueprint $table) {
        $table->dropColumn(['comment', 'note']);
    });
    Schema::table('record_activities', function (Blueprint $table) {
        $table->dropColumn('difficulty_level');
    });
}
};
