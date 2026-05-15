<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Note;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    #[\Override]
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'title' => fake()->sentence(3),
        ];
    }

    public function withTextBlock(?string $content = null): static
    {
        return $this->afterCreating(function (Note $note) use ($content): void {
            $note->blocks()->create([
                'type' => 'text',
                'position' => 0,
                'payload' => ['content' => $content ?? fake()->paragraph()],
            ]);
        });
    }

    public function withExcalidrawBlock(?array $scene = null): static
    {
        return $this->afterCreating(function (Note $note) use ($scene): void {
            $note->blocks()->create([
                'type' => 'excalidraw',
                'position' => 0,
                'payload' => [
                    'scene' => $scene ?? [
                        'type' => 'excalidraw',
                        'version' => 2,
                        'elements' => [],
                        'appState' => ['name' => $note->title],
                        'files' => [],
                    ],
                ],
            ]);
        });
    }
}
