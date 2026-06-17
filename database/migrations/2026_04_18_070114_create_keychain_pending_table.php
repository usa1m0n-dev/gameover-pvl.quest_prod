<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('keychain_pending', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_id')->constrained('points')->onDelete('cascade');
            $table->foreignId('guest_id')->constrained('guests')->onDelete('cascade');
            $table->string('hwid', 20)->nullable();
            $table->string('status')->default('Ожидает ответ от считывателя');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('keychain_pending');
    }
};
