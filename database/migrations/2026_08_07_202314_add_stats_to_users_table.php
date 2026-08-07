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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('str')->default(0)->after('age');
            $table->unsignedInteger('agi')->default(0)->after('str');
            $table->unsignedInteger('per')->default(0)->after('agi');
            $table->unsignedInteger('vit')->default(0)->after('per');
            $table->unsignedInteger('intelligence')->default(0)->after('vit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['str', 'agi', 'per', 'vit', 'intelligence']);
        });
    }
};
