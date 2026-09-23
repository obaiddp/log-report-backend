<?php

namespace App\Enums;

enum AssetType: string
{
    case Laptop = 'laptop';
    case Printer = 'printer';
    case Projector = 'projector';
    case Computer = 'computer';
    case ItSupportEquipment = 'it_support_equipment';

    public function label(): string
    {
        return match ($this) {
            self::Laptop => 'Laptop',
            self::Printer => 'Printer',
            self::Projector => 'Projector',
            self::Computer => 'Computer',
            self::ItSupportEquipment => 'IT Support Equipment',
        };
    }
}
