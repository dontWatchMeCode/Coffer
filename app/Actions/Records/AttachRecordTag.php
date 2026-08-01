<?php

declare(strict_types=1);

namespace App\Actions\Records;

use App\Models\Tag;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class AttachRecordTag
{
    /**
     * @template TRecord of Model
     *
     * @param  TRecord  $record
     * @param  MorphToMany<Tag, TRecord>  $recordTags
     */
    public function execute(Model $record, MorphToMany $recordTags, Tag $tag, ?Model $causer = null): void
    {
        $recordTags->attach($tag->id);
        ActivityLogger::logTagAttached($record, $tag, $causer);
    }
}
