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
                CREATE TABLE "contacts_new" (
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
                CREATE INDEX "contacts_new_team_id_index" ON "contacts_new" ("team_id");
                CREATE INDEX "contacts_new_name_index" ON "contacts_new" ("name");
            SQL);

            DB::unprepared(<<<'SQL'
                INSERT INTO "contacts_new" ("id", "team_id", "name", "phone_numbers", "email_addresses", "address", "additional_info", "created_at", "updated_at")
                SELECT "id", "team_id", "name",
                    CASE WHEN "phone" IS NOT NULL THEN json_array(json_object('label', '', 'value', "phone")) ELSE NULL END,
                    CASE WHEN "email" IS NOT NULL THEN json_array(json_object('label', '', 'value', "email")) ELSE NULL END,
                    "address", "additional_info", "created_at", "updated_at"
                FROM "contacts";
            SQL);

            DB::unprepared('DROP TABLE "contacts";');
            DB::unprepared('ALTER TABLE "contacts_new" RENAME TO "contacts";');

            return;
        }

        Schema::table('contacts', function (Blueprint $table): void {
            $table->json('phone_numbers')->nullable()->after('name');
            $table->json('email_addresses')->nullable()->after('phone_numbers');
        });

        $contacts = DB::table('contacts')->whereNotNull('email')->orWhereNotNull('phone')->get();

        foreach ($contacts as $contact) {
            $phoneNumbers = null;
            $emailAddresses = null;

            if ($contact->phone !== null) {
                $phoneNumbers = json_encode([['label' => '', 'value' => $contact->phone]]);
            }

            if ($contact->email !== null) {
                $emailAddresses = json_encode([['label' => '', 'value' => $contact->email]]);
            }

            DB::table('contacts')
                ->where('id', $contact->id)
                ->update([
                    'phone_numbers' => $phoneNumbers,
                    'email_addresses' => $emailAddresses,
                ]);
        }

        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropColumn('email');
            $table->dropColumn('phone');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TABLE "contacts_new" (
                    "id" integer primary key autoincrement not null,
                    "team_id" integer not null,
                    "name" text not null,
                    "email" text,
                    "phone" text,
                    "address" text,
                    "additional_info" text,
                    "created_at" text,
                    "updated_at" text,
                    foreign key("team_id") references "teams"("id") on delete cascade
                ) STRICT;
                CREATE INDEX "contacts_new_team_id_index" ON "contacts_new" ("team_id");
                CREATE INDEX "contacts_new_name_index" ON "contacts_new" ("name");
            SQL);

            DB::unprepared(<<<'SQL'
                INSERT INTO "contacts_new" ("id", "team_id", "name", "email", "phone", "address", "additional_info", "created_at", "updated_at")
                SELECT "id", "team_id", "name",
                    CASE WHEN "email_addresses" IS NOT NULL THEN json_extract("email_addresses", '$[0].value') ELSE NULL END,
                    CASE WHEN "phone_numbers" IS NOT NULL THEN json_extract("phone_numbers", '$[0].value') ELSE NULL END,
                    "address", "additional_info", "created_at", "updated_at"
                FROM "contacts";
            SQL);

            DB::unprepared('DROP TABLE "contacts";');
            DB::unprepared('ALTER TABLE "contacts_new" RENAME TO "contacts";');

            return;
        }

        Schema::table('contacts', function (Blueprint $table): void {
            $table->string('email')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
        });

        $contacts = DB::table('contacts')->whereNotNull('phone_numbers')->orWhereNotNull('email_addresses')->get();

        foreach ($contacts as $contact) {
            $email = null;
            $phone = null;

            if ($contact->email_addresses !== null) {
                $emails = json_decode($contact->email_addresses, true);
                if (is_array($emails) && $emails !== []) {
                    $email = $emails[0]['value'] ?? null;
                }
            }

            if ($contact->phone_numbers !== null) {
                $phones = json_decode($contact->phone_numbers, true);
                if (is_array($phones) && $phones !== []) {
                    $phone = $phones[0]['value'] ?? null;
                }
            }

            DB::table('contacts')
                ->where('id', $contact->id)
                ->update([
                    'email' => $email,
                    'phone' => $phone,
                ]);
        }

        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropColumn('phone_numbers');
            $table->dropColumn('email_addresses');
        });
    }
};
