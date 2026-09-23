<?php

namespace App\Enums;

enum InspectionCategory: string
{
    case NewPurchase = 'new_purchase';
    case Repair = 'repair';

    public function label(): string
    {
        return match ($this) {
            self::NewPurchase => 'New Purchase',
            self::Repair => 'Repair',
        };
    }
}
