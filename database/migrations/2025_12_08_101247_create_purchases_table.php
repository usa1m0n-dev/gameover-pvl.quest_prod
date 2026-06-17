<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_id')->constrained('points')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('left_in_stock')->default(0);
            $table->timestamps();
        });
        Schema::create('purchases_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->boolean('is_expense')->default(true);
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('price_per_unit')->default(0);
            $table->string('comment')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('purchases_log');
    }
};
