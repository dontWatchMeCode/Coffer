<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('contacts', 'links')) {
            Schema::table('contacts', function (Blueprint $table): void {
                $table->json('links')->nullable()->after('email_addresses');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('contacts', 'links')) {
            Schema::table('contacts', function (Blueprint $table): void {
                $table->dropColumn('links');
            });
        }
    }
};
