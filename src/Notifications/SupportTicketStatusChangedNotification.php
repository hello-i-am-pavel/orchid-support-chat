<?php

declare(strict_types=1);

namespace Hiap\OrchidSupportChat\Notifications;

use Hiap\OrchidSupportChat\Models\Enum\TicketStatus;
use Hiap\OrchidSupportChat\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Orchid\Platform\Notifications\DashboardChannel;
use Orchid\Platform\Notifications\DashboardMessage;
use Orchid\Support\Color;

class SupportTicketStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(public SupportTicket $ticket, public TicketStatus $status)
    {
    }

    public function via(object $notifiable): array
    {
        return [DashboardChannel::class];
    }

    public function toDashboard(object $notifiable): DashboardMessage
    {
        return DashboardMessage::make()
            ->title(__('The status of the request has been updated'))
            ->message(
                __('The #:number request is now in the :status status', [
                    'number' => $this->ticket->number,
                    'status' => __($this->status->value),
                ])
            )
            ->action(route('platform.hiap.support-ticket.chat', $this->ticket->id))
            ->type(Color::PRIMARY);
    }
}
