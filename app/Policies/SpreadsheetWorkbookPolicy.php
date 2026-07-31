<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SpreadsheetWorkbook;
use App\Models\User;

class SpreadsheetWorkbookPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SpreadsheetWorkbook $spreadsheetWorkbook): bool
    {
        return $this->belongsToUserTeam($user, $spreadsheetWorkbook);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SpreadsheetWorkbook $spreadsheetWorkbook): bool
    {
        return $this->belongsToUserTeam($user, $spreadsheetWorkbook);
    }

    public function delete(User $user, SpreadsheetWorkbook $spreadsheetWorkbook): bool
    {
        return $this->belongsToUserTeam($user, $spreadsheetWorkbook);
    }

    public function restore(User $user, SpreadsheetWorkbook $spreadsheetWorkbook): bool
    {
        return $this->belongsToUserTeam($user, $spreadsheetWorkbook);
    }

    public function forceDelete(User $user, SpreadsheetWorkbook $spreadsheetWorkbook): bool
    {
        return $this->belongsToUserTeam($user, $spreadsheetWorkbook);
    }

    private function belongsToUserTeam(User $user, SpreadsheetWorkbook $spreadsheetWorkbook): bool
    {
        return $spreadsheetWorkbook->team !== null && $user->belongsToTeam($spreadsheetWorkbook->team);
    }
}
