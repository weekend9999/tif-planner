<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorite_artists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('artist_name');
            $table->timestamps();

            $table->unique(['user_id', 'artist_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_artists');
    }
};
