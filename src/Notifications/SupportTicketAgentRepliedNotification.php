<?php

declare(strict_types=1);

namespace Hiap\OrchidSupportChat\Notifications;

use Hiap\OrchidSupportChat\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Orchid\Platform\Notifications\DashboardChannel;
use Orchid\Platform\Notifications\DashboardMessage;
use Orchid\Support\Color;

class SupportTicketAgentRepliedNotification extends Notification
{
    use Queueable;

    public function __construct(public SupportTicket $ticket)
    {
    }

    public function via(object $notifiable): array
    {
        return [DashboardChannel::class];
    }

    public function toDashboard(object $notifiable): DashboardMessage
    {
        return (new DashboardMessage())
            ->title(__('New response from support'))
            ->message(
                __('A new message has appeared in the #:number request', [
                    'number' => $this->ticket->number,
                ])
            )
            ->action(route('platform.hiap.support-ticket.chat', $this->ticket->id))
            ->type(Color::PRIMARY);
    }
}
