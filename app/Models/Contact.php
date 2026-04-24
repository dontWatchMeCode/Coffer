<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property list<array{label: string|null, value: string}>|null $phone_numbers
 * @property list<array{label: string|null, value: string}>|null $email_addresses
 */
#[Fillable(['team_id', 'name', 'phone_numbers', 'email_addresses', 'address', 'additional_info'])]
class Contact extends Model
{
    use BelongsToTeam;

    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'phone_numbers' => 'array',
            'email_addresses' => 'array',
        ];
    }
}
