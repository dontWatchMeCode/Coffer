<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TABLE "contacts" (
                    "id" integer primary key autoincrement not null,
                    "team_id" integer not null,
                    "name" text not null,
                    "phone_numbers" text,
                    "email_addresses" text,
                    "address" text,
                    "additional_info" text,
                    "created_at" text,
                    "updated_at" text,
                    foreign key("team_id") references "teams"("id") on delete cascade
                ) STRICT;
                CREATE INDEX "contacts_team_id_index" ON "contacts" ("team_id");
                CREATE INDEX "contacts_name_index" ON "contacts" ("name");
            SQL);

            return;
        }

        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('phone_numbers')->nullable();
            $table->json('email_addresses')->nullable();
            $table->text('address')->nullable();
            $table->text('additional_info')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
