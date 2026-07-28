<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watch_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('watch_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('priority')->default(1);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['watch_plan_id', 'performance_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_plan_items');
    }
};
