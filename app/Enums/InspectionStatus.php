<?php

namespace App\Enums;

enum InspectionStatus: string
{
    case Sold = 'sold';
    case InProgress = 'in_progress';
    case IndoorRepair = 'indoor_repair';
    case OutdoorRepair = 'outdoor_repair';

    public function label(): string
    {
        return match ($this) {
            self::Sold => 'Sold',
            self::InProgress => 'In Progress',
            self::IndoorRepair => 'Indoor Repair',
            self::OutdoorRepair => 'Outdoor Repair',
        };
    }
}
