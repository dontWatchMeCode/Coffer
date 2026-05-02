<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('contacts', 'phone_numbers')) {
            Schema::table('contacts', function (Blueprint $table): void {
                $table->json('phone_numbers')->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('contacts', 'email_addresses')) {
            Schema::table('contacts', function (Blueprint $table): void {
                $table->json('email_addresses')->nullable()->after('phone_numbers');
            });
        }

        if (! Schema::hasColumn('contacts', 'email') && ! Schema::hasColumn('contacts', 'phone')) {
            return;
        }

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

        $columns = collect(['email', 'phone'])
            ->filter(fn (string $column): bool => Schema::hasColumn('contacts', $column))
            ->all();

        if ($columns !== []) {
            Schema::table('contacts', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('contacts', 'email')) {
            Schema::table('contacts', function (Blueprint $table): void {
                $table->string('email')->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('contacts', 'phone')) {
            Schema::table('contacts', function (Blueprint $table): void {
                $table->string('phone')->nullable()->after('email');
            });
        }

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

        $columns = collect(['phone_numbers', 'email_addresses'])
            ->filter(fn (string $column): bool => Schema::hasColumn('contacts', $column))
            ->all();

        if ($columns !== []) {
            Schema::table('contacts', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
