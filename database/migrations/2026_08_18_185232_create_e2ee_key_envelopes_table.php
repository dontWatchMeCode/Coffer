<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e2ee_key_envelopes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('epoch_id')->constrained('e2ee_key_epochs')->cascadeOnDelete();
            $table->foreignId('member_key_id')->constrained('e2ee_member_keys')->cascadeOnDelete();
            $table->json('envelope'); // wrapped team key (KeyEnvelope v1)
            $table->timestamps();

            $table->unique(['epoch_id', 'member_key_id']);
            $table->index('member_key_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e2ee_key_envelopes');
    }
};
