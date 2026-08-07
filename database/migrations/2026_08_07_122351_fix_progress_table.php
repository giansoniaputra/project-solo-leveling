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
        Schema::table('progress', function (Blueprint $table) {
            $table->dropColumn(['status', 'completed_at']);
        });

        Schema::table('progress', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
            $table->boolean('status')->default(false)->after('quest_id');
            $table->timestamp('completed_at')->nullable()->after('status');
            $table->unique(['user_id', 'quest_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('progress', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'quest_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'status', 'completed_at']);
        });

        Schema::table('progress', function (Blueprint $table) {
            $table->integer('status');
            $table->integer('completed_at');
        });
    }
};
