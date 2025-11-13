<?php

declare(strict_types=1);

namespace Hiap\OrchidSupportChat\Policies;

use App\Models\User;
use Hiap\OrchidSupportChat\Models\SupportTicket;

class SupportTicketPolicy
{
    public function view(User $user, SupportTicket $ticket): bool
    {
        if ((int)$user->id === (int)$ticket->created_by) {
            return true;
        }

        return method_exists($user, 'hasAccess')
            ? $user->hasAccess('support.tickets.manage')
            : false;
    }
}
