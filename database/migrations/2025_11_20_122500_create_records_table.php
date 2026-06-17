<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('records', function (Blueprint $table) {
            $table->id();
            $table->dateTime('datetime');
            $table->foreignId('guest_id')->constrained('guests');
            $table->foreignId('point_id')->constrained('points');
            $table->foreignId('room_id')->nullable()->constrained('party_rooms')->nullOnDelete();
            
            $table->unsignedTinyInteger('estimated_room_time')->default(0);
            $table->unsignedInteger('fixed_room_price')->nullable();
            
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('prepaid')->default(0);
            
            $table->string('status')->default('new');
            $table->string('source')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('records'); }
};
