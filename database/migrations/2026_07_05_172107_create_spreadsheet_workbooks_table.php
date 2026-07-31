<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spreadsheet_workbooks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->json('snapshot');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spreadsheet_workbooks');
    }
};
