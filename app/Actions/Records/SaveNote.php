<?php

declare(strict_types=1);

namespace App\Actions\Records;

use App\Models\Note;

class SaveNote
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Note $note, array $attributes): Note
    {
        $hasBlocks = isset($attributes['blocks']);
        $blocks = $attributes['blocks'] ?? [];

        unset($attributes['blocks']);

        $note->fill($attributes);
        $note->save();

        if ($hasBlocks) {
            $note->syncBlocks($blocks);
        }

        return $note;
    }
}
