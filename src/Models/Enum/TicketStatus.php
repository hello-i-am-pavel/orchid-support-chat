<?php

declare(strict_types=1);

namespace Hiap\OrchidSupportChat\Models\Enum;

enum TicketStatus: string
{
    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public static function getMapWithLabels(): array
    {
        $values = [];
        foreach (self::cases() as $case) {
            $values[$case->value] = $case->label();
        }

        return $values;
    }

    public function label(): string
    {
        return match ($this) {
            self::NEW => __('New'),
            self::IN_PROGRESS => __('In progress'),
            self::RESOLVED => __('Resolved'),
            self::CLOSED => __('Closed'),
        };
    }
}
