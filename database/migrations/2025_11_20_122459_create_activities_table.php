<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('base_price');
            $table->unsignedInteger('employee_rate');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('activities'); }
};
