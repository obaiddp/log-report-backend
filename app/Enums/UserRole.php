<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case TechnicalResource = 'technical_resource';

    /**
     * Legacy values are retained for reading existing databases. New API
     * writes only accept Admin and TechnicalResource.
     */
    case Technician = 'technician';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::TechnicalResource, self::Technician, self::User => 'Technical Resource',
        };
    }

    public function canonicalValue(): string
    {
        return match ($this) {
            self::Admin => self::Admin->value,
            self::TechnicalResource, self::Technician, self::User => self::TechnicalResource->value,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function canonicalValues(): array
    {
        return [self::Admin->value, self::TechnicalResource->value];
    }

    public static function normalize(mixed $value): string
    {
        $value = trim((string) $value);
        $role = self::tryFrom($value);

        return $role?->canonicalValue() ?? $value;
    }
}
