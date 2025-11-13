<?php

declare(strict_types=1);

namespace Hiap\OrchidSupportChat\Models\Enum;

enum TicketStatus: string
{
    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
}
