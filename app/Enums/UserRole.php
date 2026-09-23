<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Technician = 'technician';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Technician => 'Technician',
            self::User => 'User',
        };
    }
}
