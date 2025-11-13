<?php

declare(strict_types=1);

namespace Hiap\OrchidSupportChat\Orchid\Screens\Support;

use Hiap\OrchidSupportChat\Models\Enum\TicketStatus;
use Hiap\OrchidSupportChat\Models\SupportTicket;
use Hiap\OrchidSupportChat\Models\SupportTicketMessage;
use Hiap\OrchidSupportChat\Models\SupportTicketStatusLog;
use Hiap\OrchidSupportChat\Notifications\SupportTicketAgentRepliedNotification;
use Hiap\OrchidSupportChat\Notifications\SupportTicketStatusChangedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SupportTicketChatScreen extends Screen
{
    public ?SupportTicket $ticket = null;

    public function query(SupportTicket $ticket): iterable
    {
        $this->authorize('view', $ticket);

        $this->ticket = $ticket->load([
            'messages' => function ($q) {
                $q->orderBy('created_at', 'asc');
            },
            'messages.sentBy',
            'messages.attachments',
            'statusLogs.changedBy',
        ]);

        $msgItems = $this->ticket->messages->map(function ($m) {
            return [
                'type' => 'message',
                'created_at' => $m->created_at,
                'model' => $m,
            ];
        });

        $logItems = $this->ticket->statusLogs
            ->sortBy('created_at')
            ->values()
            ->map(function ($log) {
                return [
                    'type' => 'status',
                    'created_at' => $log->created_at,
                    'model' => $log,
                ];
            });

        $timeline = $msgItems->concat($logItems)
            ->sortBy('created_at')
            ->values();

        return [
            'ticket' => $this->ticket,
            'timeline' => $timeline,
        ];
    }

    public function name(): ?string
    {
        return __('Chat on request') . ' #' . ($this->ticket?->number ?? '');
    }

    /**
     * @return array
     */
    public function commandBar(): array
    {
        if (auth()->user()?->hasAccess('support.tickets.manage')) {
            return [
                Link::make(__('К списку'))
                    ->icon('bs.arrow-left')
                    ->route('platform.hiap.support-tickets-admin'),
            ];
        }

        return [
            Link::make(__('К списку'))
                ->icon('bs.arrow-left')
                ->route('platform.hiap.support-tickets-list-screen'),
        ];
    }

    public function layout(): iterable
    {
        $viewer = auth()->user();
        $isSupport = $viewer?->hasAccess('support.tickets.manage');
        $blockedByStatus = in_array($this->ticket->status, [TicketStatus::RESOLVED, TicketStatus::CLOSED], true);
        $agentMustTake = $isSupport && $this->ticket->status !== TicketStatus::IN_PROGRESS;
        $canWrite = !$blockedByStatus && (!$isSupport || $this->ticket->status === TicketStatus::IN_PROGRESS);

        $blocks = [
            Layout::legend('', [
                Sight::make('status_label', __('Current status of the request'))
                    ->render(fn() => match ($this->ticket->status) {
                        TicketStatus::NEW => '<i class="text-danger">●</i> ' . __($this->ticket->status->value),
                        TicketStatus::IN_PROGRESS => '<i class="text-warning">●</i> ' . __(
                                $this->ticket->status->value
                            ),
                        TicketStatus::RESOLVED => '<i class="text-success">●</i> ' . __($this->ticket->status->value),
                        TicketStatus::CLOSED => '<i class="text-secondary">●</i> ' . __($this->ticket->status->value),
                        default => $this->ticket->status->value,
                    }),
                ...$this->getStatusButtonsSight()
            ]),

            Layout::view('hiap-orchid-support::support.chat', [
                'ticket' => $this->ticket,
                'timeline' => null,
            ]),
        ];

        if ($canWrite) {
            $blocks[] = Layout::rows([
                TextArea::make('message')
                    ->title(__('Message'))
                    ->rows(4)
                    ->placeholder(__('Введите сообщение...')),

                Upload::make('images')
                    ->title(__('Attachments'))
                    ->maxFiles(3)
                    ->acceptedFiles('image/*')
                    ->set('maxCount', 3),

                Button::make(__('Send'))
                    ->icon('bs.send')
                    ->type(Color::PRIMARY)
                    ->method('send'),
            ]);
        } else {
            $hint = $blockedByStatus
                ? __(
                    'Sending messages is unavailable: request in status ":status".',
                    ['status' => __($this->ticket->status->value)]
                )
                : __('To answer, first take the appeal into work.');

            $rows = [
                TextArea::make('disabled_message')
                    ->title(__('Message'))
                    ->rows(3)
                    ->placeholder($hint)
                    ->disabled(),
            ];

            if ($agentMustTake) {
                $rows[] = Button::make(__('Take into work'))
                    ->icon('bs.play')
                    ->type(Color::INFO)
                    ->method('setInProgress');
            }

            $blocks[] = Layout::rows($rows);
        }

        return $blocks;
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function send(Request $request): RedirectResponse
    {
        $isAgent = $request->user()?->hasAccess('support.tickets.manage');

        if (in_array($this->ticket->status, [TicketStatus::RESOLVED, TicketStatus::CLOSED], true)) {
            Toast::warning(
                __(
                    'You cannot send messages for requests with the ":status" status.',
                    ['status' => __($this->ticket->status->value)]
                )
            );
            return back();
        }

        if ($isAgent && $this->ticket->status !== TicketStatus::IN_PROGRESS) {
            Toast::warning(__('To answer, first take the appeal into work.'));
            return back();
        }

        $request->validate([
            'message' => ['required', 'string'],
        ]);

        $msg = SupportTicketMessage::create([
            'ticket_id' => $this->ticket->id,
            'sent_by' => $request->user()->id,
            'message' => strip_tags($request->input('message')),
        ]);

        $msg->attachments()->syncWithoutDetaching($request->input('images', []));

        if ($isAgent && $request->user()?->id !== (int)$this->ticket->created_by) {
            optional($this->ticket->createdBy)?->notify(new SupportTicketAgentRepliedNotification($this->ticket));
        }

        Toast::success(__('Message sent'));

        return back();
    }

    private function updateStatus(TicketStatus $status): void
    {
        if ($status !== TicketStatus::RESOLVED) {
            abort_unless(auth()->user()?->hasAccess('support.tickets.manage'), 403);
        }

        $old = $this->ticket->status->value ?? null;

        $this->ticket->update([
            'status' => $status->value,
            'status_changed_by' => request()->user()?->id,
        ]);

        SupportTicketStatusLog::create([
            'ticket_id' => $this->ticket->id,
            'old_status' => $old,
            'new_status' => $status->value,
            'changed_by' => request()->user()?->id,
            'created_at' => now(),
        ]);

        optional($this->ticket->createdBy)?->notify(new SupportTicketStatusChangedNotification($this->ticket, $status));

        Toast::info(__('Status updated:') . $status->value);
    }

    public function setInProgress(): RedirectResponse
    {
        $this->updateStatus(TicketStatus::IN_PROGRESS);

        return back();
    }

    public function setResolved(): RedirectResponse
    {
        $this->updateStatus(TicketStatus::RESOLVED);

        return back();
    }

    public function setClosed(): RedirectResponse
    {
        $this->updateStatus(TicketStatus::CLOSED);

        return back();
    }

    private function getStatusButtonsSight(): array
    {
        $buttons = null;

        if (auth()->user()?->hasAccess('support.tickets.manage')) {
            if ($this->ticket->status === TicketStatus::IN_PROGRESS) {
                $buttons = Group::make([
                    Button::make(__('Problem solved'))
                        ->icon('bs.check')
                        ->type(Color::SUCCESS)
                        ->method('setResolved'),
                    Button::make(__('Close the appeal'))
                        ->icon('bs.x-circle')
                        ->type(Color::DANGER)
                        ->method('setClosed'),
                ])->autoWidth();
            }
        } elseif ($this->ticket->status !== TicketStatus::RESOLVED) {
            $buttons = Group::make([
                Button::make(__('Problem solved'))
                    ->icon('bs.check')
                    ->type(Color::SUCCESS)
                    ->method('setResolved'),
            ])->autoWidth();
        }

        if ($buttons === null) {
            return [];
        }

        return [
            Sight::make('status_label', __('Change the status of an appeal'))->render(fn() => $buttons)
        ];
    }
}
