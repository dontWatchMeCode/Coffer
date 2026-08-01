<?php

declare(strict_types=1);

namespace App\Actions\Records;

use App\Models\RecordLink;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class DeleteRecordLink
{
    public function execute(RecordLink $link, ?Model $causer = null): void
    {
        ActivityLogger::logLinkDestroyed($link, $causer);
        $link->delete();
    }
}
