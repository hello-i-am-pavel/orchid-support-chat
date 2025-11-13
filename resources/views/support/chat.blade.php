@php use App\Orchid\Presenters\UserPresenter; @endphp
@php($msgs = $ticket->messages ?? collect())
@php($viewer = auth()->user())
@php($viewerIsSupport = $viewer && method_exists($viewer, 'hasAccess') && $viewer->hasAccess('support.tickets.manage'))
@php($agentAvatarUrl = asset('vendor/orchid-support-chat/technical-support.svg'))

<div class="card">
    <div class="card-body">

        @php($feed = isset($timeline) && $timeline ? $timeline : $msgs->map(fn($m) => ['type' => 'message', 'created_at' => $m->created_at, 'model' => $m]))

        @if(collect($feed)->isEmpty())
            <div class="text-muted">{{ __('There are no messages yet.') }}</div>
        @else
            <div class="vstack gap-3">
                @foreach($feed as $item)
                    @if(($item['type'] ?? null) === 'status')
                        @php($log = $item['model'])
                        @php($rawBy = $log->changedBy?->name ?? ('#'.$log->changed_by))
                        @php($isAgentChange = (int)($log->changed_by ?? 0) !== (int)($ticket->created_by ?? 0))
                        @php($by = $isAgentChange && !$viewerIsSupport ? __('Support agent') : $rawBy)
                        @php($from = $log->old_status ? __($log->old_status) : '-')
                        @php($to = __($log->new_status))
                        <div class="d-flex justify-content-center">
                            <span class="badge bg-light text-muted border">
                                {{ $log->created_at }} · {{ __('Status changed') }}: {{ $from }} → {{ $to }} · {{ $by }}
                            </span>
                        </div>
                        @continue
                    @endif

                    @php($m = $item['model'])
                    @php($userPresenter = new UserPresenter($m->sentBy))
                    @php($mine = (int)$m->sent_by === (int)($viewer->id ?? 0))
                    @php($isAuthor = (int)$m->sent_by === (int)$ticket->created_by)
                    @php($displayName = $m->sentBy->title ?? ('#'.$m->title))
                    @php($isAgent = (!$isAuthor && !$viewerIsSupport))
                    @php($nameForViewer = $isAgent ? __('Support agent') : $userPresenter->title())

                    <div class="d-flex {{ $mine ? 'flex-row-reverse' : '' }} align-items-start gap-2">
                        {{-- Avatar & name using Orchid Persona style --}}
                        <div class="w-100 d-flex {{ $mine ? 'justify-content-end' : 'justify-content-start' }}">
                            <div class="p-3 rounded {{ $mine ? 'bg-primary text-white' : 'bg-light border' }}"
                                 style="max-width: 70%;">
                                <div class="small mb-2 {{ $mine ? 'text-white-50' : 'text-muted' }}">
                                    @include('platform::layouts.persona', [
                                        'title' => $nameForViewer,
                                        'subTitle' => $isAgent ? null : $userPresenter->subTitle(),
                                        'image' => $isAgent ? $agentAvatarUrl : $userPresenter->image(),
                                        'url' => $isAgent ? null : $userPresenter->url(),
                                    ])
                                </div>
                                <div class="mb-2" style="white-space: pre-wrap;">{{ $m->message }}</div>

                                @if($m->attachments && $m->attachments->count())
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        @foreach($m->attachments as $a)
                                            @php($isImage = str_starts_with($a->mime, 'image/'))
                                            @if($isImage)
                                                <a href="{{ $a->url() }}" target="_blank" class="text-decoration-none">
                                                    <img src="{{ $a->url() }}" alt="att" style="max-height:80px;"
                                                         class="img-thumbnail">
                                                </a>
                                            @else
                                                <a href="{{ $a->url() }}" target="_blank"
                                                   class="btn btn-sm btn-outline-secondary">
                                                    {{ $a->original_name ?? __('File') }}
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                                <div class="d-flex {{ $mine ? 'justify-content-end' : 'justify-content-start' }}">
                                    <small class="text-muted mt-1">{{ $m->created_at?->format('d.m.Y H:i') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
