<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    #[\Override]
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->name(),
            'phone_numbers' => fake()->optional(0.7)->randomElement([
                [['label' => 'Mobile', 'value' => fake()->phoneNumber()]],
                [['label' => 'Work', 'value' => fake()->phoneNumber()], ['label' => 'Mobile', 'value' => fake()->phoneNumber()]],
                [],
            ]),
            'email_addresses' => fake()->optional(0.7)->randomElement([
                [['label' => 'Work', 'value' => fake()->companyEmail()]],
                [['label' => 'Personal', 'value' => fake()->email()]],
                [['label' => 'Work', 'value' => fake()->companyEmail()], ['label' => 'Personal', 'value' => fake()->email()]],
            ]),
            'links' => fake()->optional(0.7)->randomElement([
                [['label' => 'Website', 'value' => fake()->url()]],
                [['label' => 'LinkedIn', 'value' => 'https://linkedin.com/in/'.fake()->userName()]],
                [],
            ]),
            'address' => fake()->optional()->address(),
            'additional_info' => fake()->optional()->sentence(),
        ];
    }
}
