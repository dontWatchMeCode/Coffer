<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e2ee_record_envelopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('record_type');
            $table->integer('record_id');
            $table->foreignId('epoch_id')->constrained('e2ee_key_epochs')->restrictOnDelete();
            $table->integer('version');
            $table->json('envelope'); // RecordEnvelope v1 (opaque ciphertext)
            $table->boolean('is_tombstone')->default(false);
            // Operational metadata used only for token scope enforcement.
            $table->foreignId('task_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'record_type', 'record_id']);
            $table->index(['team_id', 'is_tombstone']);
            $table->index(['team_id', 'task_project_id']);
            $table->index(['record_type', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e2ee_record_envelopes');
    }
};
