<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['team_id', 'slug']);
            $table->index(['team_id', 'name']);
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->foreignId('subscription_category_id')->nullable()->after('category')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropForeign(['subscription_category_id']);
            $table->dropColumn('subscription_category_id');
        });

        Schema::dropIfExists('subscription_categories');
    }
};
