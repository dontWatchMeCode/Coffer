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
        Schema::table('teams', function (Blueprint $table): void {
            $table->json('default_task_status_options')->nullable()->after('is_personal');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->json('status_options')->nullable()->after('archived');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('status_options');
        });

        Schema::table('teams', function (Blueprint $table): void {
            $table->dropColumn('default_task_status_options');
        });
    }
};
