<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('stages')->cascadeOnDelete();
            $table->string('rule_type');
            $table->unsignedSmallInteger('extra_minutes')->default(0);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['stage_id', 'rule_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_rules');
    }
};
