<?php

declare(strict_types=1);

namespace App\Database\Schema\Grammars;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Support\Fluent;

class StrictSQLiteGrammar extends SQLiteGrammar
{
    /**
     * Compile a create table command.
     */
    public function compileCreate(Blueprint $blueprint, Fluent $command): string
    {
        return parent::compileCreate($blueprint, $command).' STRICT';
    }

    /**
     * Compile alter table commands into SQL statements.
     *
     * @return list<string>|string
     */
    public function compileAlter(Blueprint $blueprint, Fluent $command): array|string
    {
        $sql = parent::compileAlter($blueprint, $command);

        return is_array($sql)
            ? array_map($this->addStrictToCreateTable(...), $sql)
            : $this->addStrictToCreateTable($sql);
    }

    protected function typeChar(Fluent $column): string
    {
        return 'text';
    }

    protected function typeString(Fluent $column): string
    {
        return 'text';
    }

    protected function typeFloat(Fluent $column): string
    {
        return 'real';
    }

    protected function typeDouble(Fluent $column): string
    {
        return 'real';
    }

    protected function typeDecimal(Fluent $column): string
    {
        return 'real';
    }

    protected function typeBoolean(Fluent $column): string
    {
        return 'integer';
    }

    protected function typeEnum(Fluent $column): string
    {
        return sprintf(
            'text check (%s in (%s))',
            $this->wrap($column),
            $this->quoteString($column->allowed)
        );
    }

    protected function typeJson(Fluent $column): string
    {
        return 'text';
    }

    protected function typeJsonb(Fluent $column): string
    {
        return 'text';
    }

    protected function typeDate(Fluent $column): string
    {
        parent::typeDate($column);

        return 'text';
    }

    protected function typeTime(Fluent $column): string
    {
        return 'text';
    }

    protected function typeTimestamp(Fluent $column): string
    {
        parent::typeTimestamp($column);

        return 'text';
    }

    protected function typeUuid(Fluent $column): string
    {
        return 'text';
    }

    protected function typeIpAddress(Fluent $column): string
    {
        return 'text';
    }

    protected function typeMacAddress(Fluent $column): string
    {
        return 'text';
    }

    private function addStrictToCreateTable(string $statement): string
    {
        return str_starts_with($statement, 'create table') && ! str_ends_with($statement, ' STRICT')
            ? $statement.' STRICT'
            : $statement;
    }
}
