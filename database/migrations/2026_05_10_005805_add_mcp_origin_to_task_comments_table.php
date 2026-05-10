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
        Schema::table('task_comments', function (Blueprint $table): void {
            $table->string('source')->default('user')->after('body');
            $table->foreignId('mcp_token_id')->nullable()->after('source')->constrained('mcp_tokens')->nullOnDelete();
            $table->string('mcp_token_name')->nullable()->after('mcp_token_id');

            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_comments', function (Blueprint $table): void {
            $table->dropForeign(['mcp_token_id']);
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'mcp_token_id', 'mcp_token_name']);
        });
    }
};
