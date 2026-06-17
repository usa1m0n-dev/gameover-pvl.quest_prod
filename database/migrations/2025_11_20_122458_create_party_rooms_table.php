<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('party_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_id')->constrained('points')->cascadeOnDelete();
            $table->unsignedInteger('area');
            $table->unsignedInteger('price_per_hour'); // копейки
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('party_rooms'); }
};
