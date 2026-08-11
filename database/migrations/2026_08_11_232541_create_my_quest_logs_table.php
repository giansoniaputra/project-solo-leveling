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
        Schema::create('my_quest_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->text('summary');
            $table->unsignedInteger('exp_awarded')->default(0);
            $table->unsignedInteger('str_awarded')->default(0);
            $table->unsignedInteger('agi_awarded')->default(0);
            $table->unsignedInteger('per_awarded')->default(0);
            $table->unsignedInteger('vit_awarded')->default(0);
            $table->unsignedInteger('intelligence_awarded')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('my_quest_logs');
    }
};
