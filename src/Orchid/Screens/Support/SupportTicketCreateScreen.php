<?php

declare(strict_types=1);

namespace Hiap\OrchidSupportChat\Orchid\Screens\Support;

use Hiap\OrchidSupportChat\Models\Enum\TicketStatus;
use Hiap\OrchidSupportChat\Models\SupportTicket;
use Hiap\OrchidSupportChat\Models\SupportTicketMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SupportTicketCreateScreen extends Screen
{
    public function query(): iterable
    {
        return [];
    }

    public function name(): ?string
    {
        return __('New appeal');
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Label::make()->value(__('Describe the problem. You can attach images.')),

                TextArea::make('message')
                    ->title(__('Message'))
                    ->rows(6)
                    ->required(),

                Upload::make('images')
                    ->title(__('Attachments'))
                    ->maxFiles(3)
                    ->acceptedFiles('image/*')
                    ->set('maxCount', 3),

                Button::make(__('Create an appeal'))
                    ->icon('bs.plus-circle')
                    ->type(Color::SUCCESS)
                    ->method('store'),
            ]),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'message' => ['required', 'string'],
        ]);

        $ticket = SupportTicket::create([
            'created_by' => $request->user()->id,
            'number' => 'SUP-' . now()->format('Ymd-His'),
            'status' => TicketStatus::NEW->value,
        ]);

        $msg = SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'sent_by' => $request->user()->id,
            'message' => strip_tags($request->input('message')),
        ]);

        $msg->attachments()->syncWithoutDetaching($request->input('images', []));

        Toast::success(__('The appeal has been created'));

        return redirect()->route('platform.hiap.support-ticket.chat', $ticket->id);
    }
}
