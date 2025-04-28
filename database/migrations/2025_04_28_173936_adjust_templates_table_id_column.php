<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropPrimary();
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->string('id', 100)->change();
            $table->primary('id');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropPrimary();
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->string('id', 255)->change();
            $table->primary('id');
        });
    }
};
