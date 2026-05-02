<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('left_type');
            $table->unsignedBigInteger('left_id');
            $table->string('right_type');
            $table->unsignedBigInteger('right_id');
            $table->timestamps();

            $table->unique(['team_id', 'left_type', 'left_id', 'right_type', 'right_id'], 'record_links_pair_unique');
            $table->index(['left_type', 'left_id']);
            $table->index(['right_type', 'right_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_links');
    }
};
