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
            $table->unsignedInteger('exp')->default(0)->after('level');
            $table->decimal('weight', 5, 2)->nullable()->after('exp');
            $table->decimal('height', 5, 2)->nullable()->after('weight');
            $table->unsignedTinyInteger('age')->nullable()->after('height');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['exp', 'weight', 'height', 'age']);
        });
    }
};
