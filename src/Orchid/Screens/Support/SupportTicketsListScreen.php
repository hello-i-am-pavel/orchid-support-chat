<?php

namespace Hiap\OrchidSupportChat\Orchid\Screens\Support;

use Hiap\OrchidSupportChat\Models\Enum\TicketStatus;
use Hiap\OrchidSupportChat\Models\SupportTicket;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

/**
 * Class FeedbackFormScreen.
 * @package App\Orchid\Screens\Contact
 */
class SupportTicketsListScreen extends Screen
{
    /**
     * @return array
     */
    public function commandBar(): array
    {
        return [
            Link::make(__('New appeal'))
                ->icon('bs.plus-circle')
                ->route('platform.hiap.support-ticket.create'),
        ];
    }

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $tickets = SupportTicket::query()
            ->where('created_by', auth()->id())
            ->with('firstMessage.sentBy')
            ->latest('updated_at')
            ->paginate();

        return [
            'tickets' => $tickets,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('Support requests');
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::table('tickets', [
                TD::make('status', __('Status'))
                    ->render(fn(SupportTicket $t) => match ($t->status) {
                        TicketStatus::NEW => '<i class="text-danger">●</i> ' . __($t->status->value),
                        TicketStatus::IN_PROGRESS => '<i class="text-warning">●</i> ' . __($t->status->value),
                        TicketStatus::RESOLVED => '<i class="text-success">●</i> ' . __($t->status->value),
                        TicketStatus::CLOSED => '<i class="text-secondary">●</i> ' . __($t->status->value),
                    }),
                TD::make('last', __('Text of the appeal'))
                    ->render(function (SupportTicket $t) {
                        $message = $t->firstMessage;
                        if (!$message) {
                            return '-';
                        }

                        return str(strip_tags($message->message))->limit(80);
                    }),
                TD::make('updated_at', __('Updated'))
                    ->render(fn(SupportTicket $t) => $t->updated_at->format('Y-m-d H:i')),
                TD::make(__('Actions'))
                    ->align(TD::ALIGN_CENTER)
                    ->render(fn(SupportTicket $t) => Link::make(__('Open'))
                        ->route('platform.hiap.support-ticket.chat', $t->id)
                        ->icon('bs.chat')),
            ]),
        ];
    }
}

