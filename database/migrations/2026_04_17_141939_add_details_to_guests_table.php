<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->string('keychain_hwid', 20)->unique()->nullable();
            $table->integer('keychain_personal_discount')->nullable();
        });
    }

    public function down()
    {
        Schema::table('guests', function (Blueprint $table) {
            //
        });
    }
};
