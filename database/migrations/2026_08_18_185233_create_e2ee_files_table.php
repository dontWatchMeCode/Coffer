<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e2ee_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->integer('file_id'); // record id of the file record (type 'file')
            $table->foreignId('epoch_id')->constrained('e2ee_key_epochs')->restrictOnDelete();
            $table->integer('version');
            $table->string('disk');
            $table->string('path'); // ciphertext blob on storage
            $table->unsignedBigInteger('size'); // ciphertext size
            $table->string('chunk_count')->nullable(); // number of ciphertext chunks
            $table->boolean('is_tombstone')->default(false);
            $table->timestamps();

            $table->unique(['team_id', 'file_id']);
            $table->index(['team_id', 'is_tombstone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e2ee_files');
    }
};
