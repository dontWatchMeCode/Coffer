<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\BookmarkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'title', 'url', 'description', 'tags', 'notes', 'is_archived'])]
class Bookmark extends Model
{
    use BelongsToTeam;

    /** @use HasFactory<BookmarkFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_archived' => 'boolean',
        ];
    }
}
