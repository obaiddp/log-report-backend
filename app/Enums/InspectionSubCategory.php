<?php

namespace App\Enums;

enum InspectionSubCategory: string
{
    case InHouse = 'in_house';
    case OutHouse = 'out_house';

    public function label(): string
    {
        return match ($this) {
            self::InHouse => 'In House',
            self::OutHouse => 'Out House',
        };
    }
}
