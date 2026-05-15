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
        Schema::create('rte_blocks', function (Blueprint $table): void {
            $table->id();
            $table->morphs('blockable');
            $table->string('type');
            $table->unsignedInteger('position')->default(0);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['blockable_type', 'blockable_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rte_blocks');
    }
};
