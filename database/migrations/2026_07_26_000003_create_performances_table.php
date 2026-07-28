<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performances', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->foreignId('stage_id')->constrained('stages')->cascadeOnDelete();
            $table->string('artist_name');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['day', 'starts_at']);
            $table->index('artist_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performances');
    }
};
