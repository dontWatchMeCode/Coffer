<?php

declare(strict_types=1);

namespace App\Database;

use App\Database\Schema\Grammars\StrictSQLiteGrammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Database\SQLiteConnection;

class StrictSQLiteConnection extends SQLiteConnection
{
    /**
     * Get the default schema grammar instance.
     */
    protected function getDefaultSchemaGrammar(): SQLiteGrammar
    {
        return new StrictSQLiteGrammar($this);
    }
}
