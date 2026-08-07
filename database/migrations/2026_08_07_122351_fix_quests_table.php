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
        Schema::table('quests', function (Blueprint $table) {
            $table->dropColumn(['exp_reward', 'date_assigned']);
        });

        Schema::table('quests', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('exp_reward')->default(0)->after('description');
            $table->date('date_assigned')->after('exp_reward');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'exp_reward', 'date_assigned']);
        });

        Schema::table('quests', function (Blueprint $table) {
            $table->string('exp_reward');
            $table->string('date_assigned');
        });
    }
};
