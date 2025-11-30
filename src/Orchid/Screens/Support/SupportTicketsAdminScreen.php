<?php

declare(strict_types=1);

namespace Hiap\OrchidSupportChat\Orchid\Screens\Support;

use App\Models\User;
use Hiap\OrchidSupportChat\Models\Enum\TicketStatus;
use Hiap\OrchidSupportChat\Models\SupportTicket;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\DateTimeSplit;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class SupportTicketsAdminScreen extends Screen
{
    public function query(): iterable
    {
        $tickets = SupportTicket::filters()
            ->with(['createdBy', 'firstMessage.sentBy'])
            ->defaultSort('created_at', 'desc')
            ->latest('updated_at')
            ->paginate();

        return [
            'tickets' => $tickets,
        ];
    }

    public function permission(): ?iterable
    {
        return ['support.tickets.manage'];
    }

    public function name(): ?string
    {
        return __('All requests (support)');
    }

    public function layout(): iterable
    {
        return [
            Layout::table('tickets', [
                TD::make('number', __('Number')),
                TD::make('status', __('Status'))
                    ->sort()
                    ->filter(TD::FILTER_SELECT)
                    ->filterOptions(TicketStatus::getMapWithLabels())
                    ->render(fn(SupportTicket $t) => match ($t->status) {
                        TicketStatus::NEW => '<i class="text-danger">●</i> ' . __($t->status->value),
                        TicketStatus::IN_PROGRESS => '<i class="text-warning">●</i> ' . __($t->status->value),
                        TicketStatus::RESOLVED => '<i class="text-success">●</i> ' . __($t->status->value),
                        TicketStatus::CLOSED => '<i class="text-secondary">●</i> ' . __($t->status->value),
                    })
                    ->width(200),
                TD::make('created_by', __('User'))
                    ->sort()
                    ->filter(TD::FILTER_SELECT)
                    ->filterOptions($this->getCreatedByList())
                    ->render(fn(SupportTicket $t) => $t->createdBy?->name ?? ('#' . $t->created_by)),
                TD::make('last', __('Last message'))
                    ->render(function (SupportTicket $t) {
                        $firstMessage = $t->firstMessage;
                        if (!$firstMessage) {
                            return '-';
                        }

                        $by = $firstMessage->sentBy?->name ?? ('#' . $firstMessage->sent_by);
                        $at = $firstMessage->created_at?->format('Y-m-d H:i');
                        $text = str(strip_tags($firstMessage->message))->limit(80);

                        return $by . ' · ' . $at . ' · ' . $text;
                    }),
                TD::make('created_at', __('Created'))
                    ->sort()
                    ->usingComponent(DateTimeSplit::class),
                TD::make(__('Actions'))
                    ->render(fn(SupportTicket $t) => Link::make(__('Open'))
                        ->route('platform.hiap.support-ticket.chat', $t->id)
                        ->icon('bs.chat')),
            ]),
        ];
    }

    private function getCreatedByList(): iterable
    {
        return SupportTicket::all()
            ->pluck('created_by')
            ->unique()
            ->map(fn($id) => User::find($id))
            ->filter()
            ->pluck('name', 'id');
    }
}
