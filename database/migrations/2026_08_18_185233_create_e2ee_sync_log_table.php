<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e2ee_sync_log', function (Blueprint $table): void {
            // Append-only monotonic change log. The auto-increment id is used
            // as the sync cursor.
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('change_type'); // record | key_epoch | file
            $table->string('record_type')->nullable();
            $table->integer('record_id')->nullable();
            $table->foreignId('epoch_id')->nullable()->constrained('e2ee_key_epochs')->nullOnDelete();
            $table->integer('version')->nullable();
            $table->json('envelope')->nullable(); // opaque RecordEnvelope or FileEnvelope
            $table->boolean('is_tombstone')->default(false);
            $table->foreignId('file_id')->nullable()->constrained('e2ee_files')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->index(['team_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e2ee_sync_log');
    }
};
