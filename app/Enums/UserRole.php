<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Customer = 'customer';

    /**
     * Higher number = more privileged. Used to gate cross-role account management
     * (a user may only manage accounts with a strictly lower level).
     * Steps of 10 leave room to insert future roles without renumbering existing ones.
     */
    public function level(): int
    {
        return match ($this) {
            self::Admin => 20,
            self::Manager => 10,
            self::Customer => 0,
        };
    }
}
