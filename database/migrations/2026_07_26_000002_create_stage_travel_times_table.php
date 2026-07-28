<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_travel_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_stage_id')->constrained('stages')->cascadeOnDelete();
            $table->foreignId('to_stage_id')->constrained('stages')->cascadeOnDelete();
            $table->unsignedSmallInteger('walk_minutes');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['from_stage_id', 'to_stage_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_travel_times');
    }
};
