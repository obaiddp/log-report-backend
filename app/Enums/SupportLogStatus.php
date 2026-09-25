<?php

namespace App\Enums;

enum SupportLogStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case IndoorRepair = 'indoor_repair';
    case OutdoorRepair = 'outdoor_repair';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::IndoorRepair => 'Indoor Repair',
            self::OutdoorRepair => 'Outdoor Repair',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Closed, self::Cancelled], true);
    }

    public function canTransitionTo(self $next): bool
    {
        if ($this === $next) {
            return true;
        }

        return match ($this) {
            self::Open => in_array($next, [
                self::InProgress,
                self::IndoorRepair,
                self::OutdoorRepair,
                self::Resolved,
                self::Cancelled,
            ], true),
            self::InProgress => in_array($next, [
                self::IndoorRepair,
                self::OutdoorRepair,
                self::Resolved,
                self::Cancelled,
            ], true),
            self::IndoorRepair => in_array($next, [
                self::InProgress,
                self::OutdoorRepair,
                self::Resolved,
                self::Cancelled,
            ], true),
            self::OutdoorRepair => in_array($next, [
                self::InProgress,
                self::IndoorRepair,
                self::Resolved,
                self::Cancelled,
            ], true),
            self::Resolved => in_array($next, [
                self::InProgress,
                self::IndoorRepair,
                self::OutdoorRepair,
                self::Closed,
                self::Cancelled,
            ], true),
            self::Closed, self::Cancelled => false,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
