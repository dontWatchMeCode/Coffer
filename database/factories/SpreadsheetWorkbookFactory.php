<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SpreadsheetWorkbook;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpreadsheetWorkbook>
 */
class SpreadsheetWorkbookFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'title' => fake()->words(3, true),
            'snapshot' => SpreadsheetWorkbook::defaultSnapshot(),
        ];
    }
}
