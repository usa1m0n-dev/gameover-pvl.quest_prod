<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('record_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('records')->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained('activities');
            
            $table->unsignedTinyInteger('players_count')->default(4);
            $table->unsignedInteger('fixed_price')->nullable();
            $table->decimal('discount_multiplier', 5, 2)->default(1.00);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('record_activities'); }
};
