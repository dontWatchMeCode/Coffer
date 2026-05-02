<?php

declare(strict_types=1);

namespace App\Database\Schema\Grammars;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Support\Fluent;
use RuntimeException;

class StrictSQLiteGrammar extends SQLiteGrammar
{
    /**
     * Compile a create table command.
     *
     * @param  Fluent<string, mixed>  $command
     */
    public function compileCreate(Blueprint $blueprint, Fluent $command): string
    {
        return parent::compileCreate($blueprint, $command).' STRICT';
    }

    /**
     * Compile alter table commands into SQL statements.
     *
     * @param  Fluent<string, mixed>  $command
     * @return list<string>|string
     */
    public function compileAlter(Blueprint $blueprint, Fluent $command): array|string
    {
        $sql = parent::compileAlter($blueprint, $command);

        return is_array($sql)
            ? array_map($this->addStrictToCreateTable(...), $sql)
            : $this->addStrictToCreateTable($sql);
    }

    /**
     * @param  Fluent<string, mixed>  $column
     */
    protected function typeChar(Fluent $column): string
    {
        return 'text';
    }

    /**
     * @param  Fluent<string, mixed>  $column
     */
    protected function typeString(Fluent $column): string
    {
        return 'text';
    }

    /**
     * @param  Fluent<string, mixed>  $column
     */
    protected function typeFloat(Fluent $column): string
    {
        return 'real';
    }

    /**
     * @param  Fluent<string, mixed>  $column
     */
    protected function typeDouble(Fluent $column): string
    {
        return 'real';
    }

    /**
     * @param  Fluent<string, mixed>  $column
     */
    protected function typeDecimal(Fluent $column): string
    {
        return 'real';
    }

    /**
     * @param  Fluent<string, mixed>  $column
     */
    protected function typeBoolean(Fluent $column): string
    {
        return 'integer';
    }

    /**
     * @param  Fluent<string, mixed>  $column
     */
    protected function typeEnum(Fluent $column): string
    {
        return sprintf(
            'text check (%s in (%s))',
            $this->wrap($column),
            $this->quoteString($this->allowedValues($column))
        );
    }

    /**
     * @param  Fluent<string, mixed>  $column
     */
    protected function typeJson(Fluent $column): string
    {
        return 'text';
    }

    /**
     * @param  Fluent<string, mixed>  $column
     */
    protected function typeJsonb(Fluent $column): string
    {
        return 'text';
    }

    /**
     * @param  Fluent<string, mixed>  $column
     */
    protected function typeDate(Fluent $column): string
    {
        parent::typeDate($column);

        return 'text';
    }

    /**
     * @param  Fluent<string, mixed>  $column
     */
    protected function typeTime(Fluent $column): string
    {
        return 'text';
    }

    /**
     * @param  Fluent<string, mixed>  $column
     */
    protected function typeTimestamp(Fluent $column): string
    {
        parent::typeTimestamp($column);

        return 'text';
    }

    /**
     * @param  Fluent<string, mixed>  $column
     */
    protected function typeUuid(Fluent $column): string
    {
        return 'text';
    }

    /**
     * @param  Fluent<string, mixed>  $column
     */
    protected function typeIpAddress(Fluent $column): string
    {
        return 'text';
    }

    /**
     * @param  Fluent<string, mixed>  $column
     */
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

    /**
     * @param  Fluent<string, mixed>  $column
     * @return list<string>
     */
    private function allowedValues(Fluent $column): array
    {
        $allowed = $column->get('allowed');

        if (! is_array($allowed) || array_filter($allowed, fn (mixed $value): bool => ! is_string($value)) !== []) {
            throw new RuntimeException('SQLite enum columns require a list of allowed string values.');
        }

        return array_values($allowed);
    }
}
